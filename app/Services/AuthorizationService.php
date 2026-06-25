<?php

namespace App\Services;

use App\Models\UserModel;

/**
 * Role- and office-scoped authorization. Denies by default when input is
 * missing, ambiguous, or does not satisfy policy.
 */
class AuthorizationService
{
    public const ACTION_VIEW              = 'view';
    public const ACTION_CREATE            = 'create';
    public const ACTION_UPDATE            = 'update';
    public const ACTION_DELETE            = 'delete';
    public const ACTION_SUBMIT            = 'submit';
    public const ACTION_PUBLISH           = 'publish';
    public const ACTION_MODERATE_FEEDBACK = 'moderate_feedback';

    /** @var list<string> */
    public const ACTIONS = [
        self::ACTION_VIEW,
        self::ACTION_CREATE,
        self::ACTION_UPDATE,
        self::ACTION_DELETE,
        self::ACTION_SUBMIT,
        self::ACTION_PUBLISH,
        self::ACTION_MODERATE_FEEDBACK,
    ];

    /** @var list<string> */
    private const VIEWER_VISIBLE_STATUSES = ['published', 'completed'];

    /**
     * @param array<string, mixed>|null $user    Must include id, role, is_active; office_id for office_staff
     * @param array<string, mixed>|null $project Must include office_id; id + status when required by action
     */
    public function can(?array $user, string $action, ?array $project = null): bool
    {
        if (! $this->isActiveUser($user)) {
            return false;
        }

        if (! in_array($action, self::ACTIONS, true)) {
            return false;
        }

        $role = $user['role'] ?? null;

        if (! is_string($role) || ! in_array($role, UserModel::ROLES, true)) {
            return false;
        }

        return match ($role) {
            'super_admin'  => $this->canSuperAdmin($action, $project),
            'admin'        => $this->canAdmin($action, $project),
            'office_staff' => $this->canOfficeStaff($user, $action, $project),
            'viewer'       => $this->canViewer($action, $project),
            default        => false,
        };
    }

    private function isActiveUser(?array $user): bool
    {
        if ($user === null || $user === []) {
            return false;
        }

        if (! isset($user['id']) || (int) $user['id'] < 1) {
            return false;
        }

        if (! array_key_exists('is_active', $user)) {
            return false;
        }

        return (int) $user['is_active'] === 1;
    }

    private function canSuperAdmin(string $action, ?array $project): bool
    {
        if ($action === self::ACTION_CREATE) {
            return $this->hasOfficeId($project);
        }

        if ($this->actionRequiresExistingProject($action)) {
            return $this->hasExistingProject($project);
        }

        return false;
    }

    private function canAdmin(string $action, ?array $project): bool
    {
        if ($action === self::ACTION_CREATE) {
            return $this->hasOfficeId($project);
        }

        if ($this->actionRequiresExistingProject($action)) {
            return $this->hasExistingProject($project);
        }

        return false;
    }

    private function canOfficeStaff(array $user, string $action, ?array $project): bool
    {
        $userOfficeId = $this->normalizeOfficeId($user['office_id'] ?? null);

        if ($userOfficeId === null) {
            return false;
        }

        if ($action === self::ACTION_CREATE) {
            if (! $this->hasOfficeId($project)) {
                return false;
            }

            return $userOfficeId === $this->normalizeOfficeId($project['office_id'] ?? null);
        }

        if (! $this->hasExistingProject($project)) {
            return false;
        }

        $projectOfficeId = $this->normalizeOfficeId($project['office_id'] ?? null);

        if ($projectOfficeId === null || $userOfficeId !== $projectOfficeId) {
            return false;
        }

        return match ($action) {
            self::ACTION_VIEW,
            self::ACTION_UPDATE,
            self::ACTION_SUBMIT,
            self::ACTION_MODERATE_FEEDBACK => true,
            self::ACTION_DELETE            => $this->isDraftProject($project),
            self::ACTION_PUBLISH           => false,
            default                        => false,
        };
    }

    private function canViewer(string $action, ?array $project): bool
    {
        if ($action !== self::ACTION_VIEW) {
            return false;
        }

        if (! $this->hasExistingProject($project)) {
            return false;
        }

        $status = $project['status'] ?? null;

        if (! is_string($status) || $status === '') {
            return false;
        }

        return in_array($status, self::VIEWER_VISIBLE_STATUSES, true);
    }

    private function actionRequiresExistingProject(string $action): bool
    {
        return in_array($action, [
            self::ACTION_VIEW,
            self::ACTION_UPDATE,
            self::ACTION_DELETE,
            self::ACTION_SUBMIT,
            self::ACTION_PUBLISH,
            self::ACTION_MODERATE_FEEDBACK,
        ], true);
    }

    private function hasExistingProject(?array $project): bool
    {
        if ($project === null || $project === []) {
            return false;
        }

        if (! isset($project['id']) || (int) $project['id'] < 1) {
            return false;
        }

        return $this->hasOfficeId($project);
    }

    private function hasOfficeId(?array $project): bool
    {
        if ($project === null) {
            return false;
        }

        return $this->normalizeOfficeId($project['office_id'] ?? null) !== null;
    }

    private function normalizeOfficeId(mixed $officeId): ?int
    {
        if ($officeId === null || $officeId === '') {
            return null;
        }

        if (! is_numeric($officeId)) {
            return null;
        }

        $normalized = (int) $officeId;

        return $normalized > 0 ? $normalized : null;
    }

    private function isDraftProject(array $project): bool
    {
        return ($project['status'] ?? null) === 'draft';
    }
}
