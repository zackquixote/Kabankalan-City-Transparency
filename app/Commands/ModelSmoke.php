<?php

namespace App\Commands;

use App\Models\BudgetCycleStageModel;
use App\Models\FeedbackModel;
use App\Models\OfficeModel;
use App\Models\ProjectModel;
use App\Models\ProjectVersionModel;
use App\Models\UserModel;
use App\Models\VisionModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Quick smoke test for Phase 2 models (fetch, validate, insert, update).
 *
 * Usage: php spark model:smoke
 */
class ModelSmoke extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'model:smoke';
    protected $description = 'Smoke-test all domain models against the seeded database.';
    protected $usage       = 'model:smoke';

    public function run(array $params): void
    {
        CLI::write('Phase 2 model smoke test', 'green');
        CLI::newLine();

        $this->testFetch();
        $this->testProjectWithRelations();
        $this->testValidationRejectsBadData();
        $this->testInsertUpdateDelete();
        $this->testMassAssignmentGuard();

        CLI::newLine();
        CLI::write('All smoke tests passed.', 'green');
    }

    private function testFetch(): void
    {
        CLI::write('→ Fetch seeded records', 'yellow');

        $checks = [
            'VisionModel'          => (new VisionModel())->find(1)['title'] ?? null,
            'OfficeModel'          => (new OfficeModel())->find(1)['code'] ?? null,
            'BudgetCycleStageModel' => (new BudgetCycleStageModel())->find(1)['slug'] ?? null,
            'UserModel'            => (new UserModel())->find(1)['email'] ?? null,
            'ProjectModel'         => (new ProjectModel())->find(1)['project_code'] ?? null,
            'ProjectVersionModel'  => (new ProjectVersionModel())->where('project_id', 1)->first()['version_number'] ?? null,
            'FeedbackModel'        => (new FeedbackModel())->find(1)['author_name'] ?? null,
        ];

        foreach ($checks as $model => $value) {
            $this->assert($value !== null, "{$model} fetch failed");
            CLI::write("  ✓ {$model}: {$value}");
        }
    }

    private function testProjectWithRelations(): void
    {
        CLI::write('→ ProjectModel::withRelations()', 'yellow');

        $project = (new ProjectModel())->withRelations()->find(1);

        $this->assert($project !== null, 'Project with relations not found');
        $this->assert(isset($project['vision_title']), 'vision_title missing from join');
        $this->assert(isset($project['office_name']), 'office_name missing from join');
        $this->assert(isset($project['office_code']), 'office_code missing from join');

        CLI::write("  ✓ vision_title: {$project['vision_title']}");
        CLI::write("  ✓ office_name: {$project['office_name']} ({$project['office_code']})");
    }

    private function testValidationRejectsBadData(): void
    {
        CLI::write('→ Validation rejects invalid insert', 'yellow');

        $vision = new VisionModel();
        $id     = $vision->insert([
            'title'      => str_repeat('x', 300),
            'start_year' => 2025,
            'end_year'   => 2024,
            'is_active'  => 1,
        ]);

        $this->assert($id === false, 'VisionModel should reject invalid data');
        CLI::write('  ✓ VisionModel rejected bad title/end_year');
    }

    private function testInsertUpdateDelete(): void
    {
        CLI::write('→ Insert / update / delete round-trip', 'yellow');

        $visionModel = new VisionModel();
        $visionId    = $visionModel->insert([
            'title'       => 'Smoke Test Vision',
            'description' => 'Temporary record for model smoke test.',
            'start_year'  => 2026,
            'end_year'    => 2027,
            'is_active'   => 1,
        ], true);

        $this->assert(is_numeric($visionId), 'Vision insert failed');

        $updated = $visionModel->update($visionId, ['title' => 'Smoke Test Vision (updated)']);
        $this->assert($updated === true, 'Vision update failed');

        $row = $visionModel->find($visionId);
        $this->assert($row['title'] === 'Smoke Test Vision (updated)', 'Vision title not updated');
        $this->assert($row['created_at'] !== null && $row['updated_at'] !== null, 'Timestamps not set');

        $visionModel->delete($visionId);
        $this->assert($visionModel->find($visionId) === null, 'Vision delete failed');

        CLI::write('  ✓ VisionModel insert/update/delete with timestamps');

        $userModel = new UserModel();
        $updated   = $userModel->update(1, ['full_name' => 'Maria Lourdes Reyes']);
        $this->assert($updated === true, 'User update without password_hash failed');
        CLI::write('  ✓ UserModel update without re-supplying password_hash');
    }

    private function testMassAssignmentGuard(): void
    {
        CLI::write('→ Mass-assignment guard (protectFields)', 'yellow');

        $officeModel = new OfficeModel();
        $officeId    = $officeModel->insert([
            'code'         => 'SMK',
            'name'         => 'Smoke Test Office',
            'is_active'    => 1,
            'created_at'   => '2000-01-01 00:00:00',
            'evil_column'  => 'must be stripped',
        ], true);

        $this->assert(is_numeric($officeId), 'Office insert failed');

        $row = $officeModel->find($officeId);
        $this->assert(! str_starts_with($row['created_at'], '2000'), 'created_at was mass-assigned');
        $this->assert(! array_key_exists('evil_column', $row), 'unknown column leaked into row');

        $officeModel->delete($officeId);
        CLI::write('  ✓ protected columns blocked from mass assignment');
    }

    private function assert(bool $condition, string $message): void
    {
        if (! $condition) {
            CLI::error("FAIL: {$message}");
            exit(EXIT_ERROR);
        }
    }
}
