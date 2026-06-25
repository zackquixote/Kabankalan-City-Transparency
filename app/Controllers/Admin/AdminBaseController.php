<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Services\AuthorizationService;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * AdminBaseController
 *
 * All admin controllers extend this class.
 *
 * Responsibilities:
 *  - Provide `getCurrentUser()` from session (fast, no DB hit).
 *  - Provide `getFullUser()` reloaded from DB (includes `is_active` required by AuthorizationService).
 *  - Lazy-load `AuthorizationService` via `authz()`.
 *  - `denyUnless(bool $allowed)` — throws 403 if false.
 */
abstract class AdminBaseController extends BaseController
{
    private ?AuthorizationService $authzService = null;
    private ?array $fullUser = null;

    public function __construct()
    {
        helper(['form', 'url']);
    }

    // ──────────────────────────────────────────────────────────────────
    // Session helpers
    // ──────────────────────────────────────────────────────────────────

    /**
     * Returns the lightweight user array stored in session.
     * AdminAuthFilter guarantees this is always set on admin routes.
     */
    protected function getCurrentUser(): array
    {
        return [
            'id'        => (int) session()->get('user_id'),
            'email'     => session()->get('user_email'),
            'full_name' => session()->get('user_name'),
            'role'      => session()->get('user_role'),
            'office_id' => session()->get('office_id'),
            'is_active' => 1,   // Filter already verified active status at login
            'login_time' => session()->get('login_time'),
        ];
    }

    /**
     * Reloads the full user row from the DB.
     *
     * AuthorizationService::can() requires `is_active` as stored in the DB
     * (not just the session copy) so it catches deactivated-while-logged-in
     * accounts on the very next admin action.
     *
     * Result is cached per request.
     */
    protected function getFullUser(): array
    {
        if ($this->fullUser !== null) {
            return $this->fullUser;
        }

        $userId = (int) session()->get('user_id');
        $user   = (new UserModel())->find($userId);

        if ($user === null) {
            // User deleted from DB while session is alive — force logout
            session()->destroy();
            throw new PageNotFoundException('User account no longer exists.');
        }

        $this->fullUser = $user;

        return $this->fullUser;
    }

    // ──────────────────────────────────────────────────────────────────
    // Authorization helpers
    // ──────────────────────────────────────────────────────────────────

    /** Lazy-loaded AuthorizationService instance. */
    protected function authz(): AuthorizationService
    {
        if ($this->authzService === null) {
            $this->authzService = new AuthorizationService();
        }

        return $this->authzService;
    }

    /**
     * Throws a 403 PageNotFoundException (CI's standard mechanism for
     * non-200 termination) unless $allowed is true.
     *
     * Always check authz at the save/delete endpoint, not only on the form
     * render, so direct POST attacks are rejected just as firmly.
     */
    protected function denyUnless(bool $allowed, string $message = 'You are not authorised to perform this action.'): void
    {
        if (! $allowed) {
            throw new PageNotFoundException($message);
        }
    }

    /**
     * Quick role-only gate for non-project resources (visions, offices,
     * cycle stages) where there is no per-office scoping requirement.
     */
    protected function requireRole(string ...$roles): void
    {
        $user = $this->getCurrentUser();

        if (! in_array($user['role'], $roles, true)) {
            throw new PageNotFoundException('You are not authorised to access this section.');
        }
    }

    // ──────────────────────────────────────────────────────────────────
    // View helpers
    // ──────────────────────────────────────────────────────────────────

    /**
     * Render a view inside the shared admin layout.
     */
    protected function adminView(string $view, array $data = []): string
    {
        $data['currentUser'] = $this->getCurrentUser();
        $data['content']     = view($view, $data);

        return view('admin/layout/main', $data);
    }
}
