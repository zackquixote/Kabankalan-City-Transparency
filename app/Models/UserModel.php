<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    public const ROLES = [
        'super_admin',
        'admin',
        'office_staff',
        'viewer',
    ];

    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'office_id',
        'email',
        'password_hash',
        'full_name',
        'role',
        'is_active',
        'last_login_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'        => 'int',
        'office_id' => '?int',
        'is_active' => 'int',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'office_id'     => 'permit_empty|is_natural_no_zero|is_not_unique[offices.id]',
        'email'         => 'required|valid_email|max_length[255]|is_unique[users.email,id,{id}]',
        'password_hash' => 'permit_empty|min_length[60]|max_length[255]',
        'full_name'     => 'required|max_length[150]',
        'role'          => 'required|in_list[super_admin,admin,office_staff,viewer]',
        'is_active'     => 'permit_empty|in_list[0,1]',
        'last_login_at' => 'permit_empty|valid_date',
    ];

    protected $validationMessages = [
        'password_hash' => [
            'min_length' => 'password_hash must be a bcrypt/argon hash, not a plain-text password.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert     = ['requirePasswordHashOnInsert'];

    /**
     * password_hash is required on create but optional on update (permit_empty in rules).
     *
     * @param array{data: array<string, mixed>} $eventData
     *
     * @return array{data: array<string, mixed>}|false
     */
    protected function requirePasswordHashOnInsert(array $eventData): array|false
    {
        if (empty($eventData['data']['password_hash'])) {
            $this->errors = ['password_hash' => 'A hashed password is required when creating a user.'];

            return false;
        }

        return $eventData;
    }

    // Authentication methods

    /**
     * Validates a password against the stored password hash
     *
     * @param string $password The plain text password to validate
     * @param string|array $user The user data containing password_hash, or just the hash string
     * @return bool True if password is valid, false otherwise
     */
    public function validatePassword(string $password, $user): bool
    {
        if (is_array($user)) {
            $passwordHash = $user['password_hash'] ?? '';
        } else {
            $passwordHash = $user;
        }

        if (empty($passwordHash) || empty($password)) {
            return false;
        }

        return password_verify($password, $passwordHash);
    }

    /**
     * Updates the last login timestamp for a user
     *
     * @param int $userId The user ID to update
     * @return bool True if update was successful, false otherwise
     */
    public function updateLastLogin(int $userId): bool
    {
        if ($this->find($userId) === null) {
            return false;
        }

        return $this->skipValidation(true)->update($userId, [
            'last_login_at' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Checks if a user account is active
     *
     * @param array $user The user data array
     * @return bool True if user is active, false otherwise
     */
    public function isActive(array $user): bool
    {
        return !empty($user['is_active']) && $user['is_active'] == 1;
    }

    // Role checking methods

    /**
     * Checks if a user has super_admin role
     *
     * @param array $user The user data array
     * @return bool True if user is super_admin, false otherwise
     */
    public function isSuperAdmin(array $user): bool
    {
        return isset($user['role']) && $user['role'] === 'super_admin';
    }

    /**
     * Checks if a user has admin role
     *
     * @param array $user The user data array
     * @return bool True if user is admin, false otherwise
     */
    public function isAdmin(array $user): bool
    {
        return isset($user['role']) && $user['role'] === 'admin';
    }

    /**
     * Checks if a user has office_staff role
     *
     * @param array $user The user data array
     * @return bool True if user is office_staff, false otherwise
     */
    public function isOfficeStaff(array $user): bool
    {
        return isset($user['role']) && $user['role'] === 'office_staff';
    }

    /**
     * Checks if a user can access a specific office (for office_staff role)
     *
     * @param array $user The user data array
     * @param int $officeId The office ID to check access for
     * @return bool True if user can access the office, false otherwise
     */
    public function canAccessOffice(array $user, int $officeId): bool
    {
        // Super admins and admins can access all offices
        if ($this->isSuperAdmin($user) || $this->isAdmin($user)) {
            return true;
        }

        // Office staff can only access their assigned office
        if ($this->isOfficeStaff($user)) {
            return isset($user['office_id']) && $user['office_id'] == $officeId;
        }

        // Viewers have no office access for admin functions
        return false;
    }
}
