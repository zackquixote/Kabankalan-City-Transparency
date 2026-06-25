<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AdminAuthFilter
 *
 * Applied to every /admin/* route via Config/Filters.php.
 * Deny-by-default: any request that does not carry a valid, non-expired
 * admin session is redirected to the login page.
 *
 * Checks performed (in order):
 *  1. Session key 'logged_in' must be true and 'user_id' must be present.
 *  2. Session age must not exceed the configured session expiration window
 *     (checked against login_time stored at login, as a secondary guard
 *     alongside PHP's native session TTL).
 *  3. User role must be one of the permitted admin roles.
 */
class AdminAuthFilter implements FilterInterface
{
    /** Roles that are permitted to access the admin area. */
    private const ALLOWED_ROLES = ['super_admin', 'admin', 'office_staff'];

    /**
     * @param RequestInterface $request
     * @param list<string>|null $arguments
     * @return ResponseInterface|string|void
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();

        // 1. Must have an active authenticated session
        if (! $session->has('user_id') || $session->get('logged_in') !== true) {
            log_message('info', 'AdminAuthFilter: unauthenticated access blocked — no session.');

            return redirect()
                ->to(site_url('auth/login'))
                ->with('error', 'Please log in to access the admin area.');
        }

        // 2. Enforce session age timeout as a belt-and-suspenders guard.
        //    CI's file handler already expires the session file, but we also
        //    check login_time so an attacker who somehow keeps a file alive
        //    cannot outlast our intended window.
        $loginTime  = (int) ($session->get('login_time') ?? 0);
        $expiration = (int) (config('Session')->expiration ?? 7200);

        if ($loginTime > 0 && (time() - $loginTime) > $expiration) {
            log_message('info', 'AdminAuthFilter: session timed out for user_id=' . $session->get('user_id'));

            $session->destroy();

            return redirect()
                ->to(site_url('auth/login'))
                ->with('error', 'Your session has expired. Please log in again.');
        }

        // 3. Role must be allowed
        $role = $session->get('user_role');

        if (! in_array($role, self::ALLOWED_ROLES, true)) {
            log_message('warning', 'AdminAuthFilter: role "' . $role . '" denied admin access.');

            $session->destroy();

            return redirect()
                ->to(site_url('auth/login'))
                ->with('error', 'You do not have permission to access the admin area.');
        }
    }

    /**
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param list<string>|null $arguments
     * @return ResponseInterface|void
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do after the response.
    }
}
