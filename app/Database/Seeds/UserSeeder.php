<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

/**
 * Seeds staff accounts with PHP password_hash() (bcrypt via PASSWORD_DEFAULT).
 *
 * All seeded users share the test password: Kabankalan2026!
 * Change immediately outside local development.
 */
class UserSeeder extends Seeder
{
    public const SEED_PASSWORD = 'Kabankalan2026!';

    public function run(): void
    {
        $now = Time::now()->toDateTimeString();

        // PASSWORD_DEFAULT → bcrypt (PHP 8.x); never store plain text or md5/sha1.
        $passwordHash = password_hash(self::SEED_PASSWORD, PASSWORD_DEFAULT);

        $data = [
            [
                'office_id'     => null,
                'email'         => 'admin@kabankalan.gov.ph',
                'password_hash' => $passwordHash,
                'full_name'     => 'Maria Lourdes Reyes',
                'role'          => 'super_admin',
                'is_active'     => 1,
                'last_login_at' => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'office_id'     => 2, // CPDO
                'email'         => 'planner@kabankalan.gov.ph',
                'password_hash' => $passwordHash,
                'full_name'     => 'Ramon Villanueva',
                'role'          => 'admin',
                'is_active'     => 1,
                'last_login_at' => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'office_id'     => 3, // CEO
                'email'         => 'engineer@kabankalan.gov.ph',
                'password_hash' => $passwordHash,
                'full_name'     => 'Elena Castillo',
                'role'          => 'office_staff',
                'is_active'     => 1,
                'last_login_at' => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'office_id'     => 4, // CHO
                'email'         => 'health@kabankalan.gov.ph',
                'password_hash' => $passwordHash,
                'full_name'     => 'Dr. Paolo Mendoza',
                'role'          => 'office_staff',
                'is_active'     => 1,
                'last_login_at' => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'office_id'     => 5, // CSWDO
                'email'         => 'social@kabankalan.gov.ph',
                'password_hash' => $passwordHash,
                'full_name'     => 'Grace Ortega',
                'role'          => 'office_staff',
                'is_active'     => 1,
                'last_login_at' => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'office_id'     => 6, // CAO
                'email'         => 'agri@kabankalan.gov.ph',
                'password_hash' => $passwordHash,
                'full_name'     => 'Jose Rizal Dagohoy',
                'role'          => 'office_staff',
                'is_active'     => 1,
                'last_login_at' => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'office_id'     => 7, // CTO
                'email'         => 'treasurer@kabankalan.gov.ph',
                'password_hash' => $passwordHash,
                'full_name'     => 'Analyn Sarmiento',
                'role'          => 'viewer',
                'is_active'     => 1,
                'last_login_at' => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
            [
                'office_id'     => null,
                'email'         => 'inactive@kabankalan.gov.ph',
                'password_hash' => $passwordHash,
                'full_name'     => 'Former Staff Account',
                'role'          => 'office_staff',
                'is_active'     => 0,
                'last_login_at' => null,
                'created_at'    => $now,
                'updated_at'    => $now,
            ],
        ];

        $this->db->table('users')->insertBatch($data);
    }
}
