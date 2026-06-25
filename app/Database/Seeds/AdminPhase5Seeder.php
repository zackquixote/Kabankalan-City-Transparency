<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * AdminPhase5Seeder
 *
 * Seeds two office_staff accounts (one per office) for the dual-login
 * security test described in the Phase 5 verification plan.
 *
 * Usage:
 *   php spark db:seed AdminPhase5Seeder
 *
 * Test plan:
 *  1. Log in as staff_a@kabankalan.local / Pass@1234
 *     → Can create/edit projects for Office A only.
 *     → Attempting to POST to admin/projects/{Office-B-project-id} → 403.
 *
 *  2. Log in as staff_b@kabankalan.local / Pass@1234
 *     → Can create/edit projects for Office B only.
 *     → Attempting to POST to admin/projects/{Office-A-project-id} → 403.
 *
 * Existing super_admin / admin accounts are NOT modified.
 */
class AdminPhase5Seeder extends Seeder
{
    public function run(): void
    {
        $db = \Config\Database::connect();

        // ── Ensure two offices exist ───────────────────────────────

        $officeA = $this->upsertOffice($db, 'OFFA', 'Office Alpha');
        $officeB = $this->upsertOffice($db, 'OFFB', 'Office Beta');

        // ── Create office_staff accounts ───────────────────────────

        $this->upsertUser($db, [
            'email'         => 'staff_a@kabankalan.local',
            'full_name'     => 'Staff Alpha',
            'role'          => 'office_staff',
            'office_id'     => $officeA,
            'password_hash' => password_hash('Pass@1234', PASSWORD_BCRYPT),
            'is_active'     => 1,
        ]);

        $this->upsertUser($db, [
            'email'         => 'staff_b@kabankalan.local',
            'full_name'     => 'Staff Beta',
            'role'          => 'office_staff',
            'office_id'     => $officeB,
            'password_hash' => password_hash('Pass@1234', PASSWORD_BCRYPT),
            'is_active'     => 1,
        ]);

        echo "AdminPhase5Seeder: seeded 2 office_staff accounts and 2 offices.\n";
        echo "  staff_a@kabankalan.local → Office Alpha (ID: {$officeA})\n";
        echo "  staff_b@kabankalan.local → Office Beta  (ID: {$officeB})\n";
        echo "  Password for both: Pass\@1234\n";
    }

    private function upsertOffice(\CodeIgniter\Database\ConnectionInterface $db, string $code, string $name): int
    {
        $existing = $db->table('offices')->where('code', $code)->get()->getRowArray();

        if ($existing) {
            return (int) $existing['id'];
        }

        $db->table('offices')->insert([
            'code'       => $code,
            'name'       => $name,
            'is_active'  => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return (int) $db->insertID();
    }

    private function upsertUser(\CodeIgniter\Database\ConnectionInterface $db, array $data): void
    {
        $existing = $db->table('users')->where('email', $data['email'])->get()->getRowArray();

        $data['updated_at'] = date('Y-m-d H:i:s');

        if ($existing) {
            // Update password hash and role in case seeder is re-run
            $db->table('users')->where('id', $existing['id'])->update([
                'password_hash' => $data['password_hash'],
                'role'          => $data['role'],
                'office_id'     => $data['office_id'],
                'is_active'     => $data['is_active'],
                'updated_at'    => $data['updated_at'],
            ]);
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $db->table('users')->insert($data);
        }
    }
}
