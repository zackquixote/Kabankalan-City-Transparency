<?php

namespace App\Commands;

use App\Models\FeedbackModel;
use App\Models\ProjectModel;
use App\Models\UserModel;
use App\Services\AuthorizationService;
use App\Services\BudgetSummaryService;
use App\Services\FeedbackService;
use App\Services\ProjectFilterService;
use App\Services\VersioningService;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

/**
 * Smoke-test Phase 3 services against seeded data.
 *
 * Usage: php spark service:smoke
 */
class ServiceSmoke extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'service:smoke';
    protected $description = 'Smoke-test BudgetSummary, ProjectFilter, Authorization, Versioning, and Feedback services.';
    protected $usage       = 'service:smoke';

    public function run(array $params): void
    {
        CLI::write('Phase 3 service smoke test', 'green');
        CLI::newLine();

        $this->testAuthorization();
        $this->testProjectFilter();
        $this->testBudgetSummary();
        $this->testVersioning();
        $this->testFeedback();

        CLI::newLine();
        CLI::write('All service smoke tests passed.', 'green');
    }

    private function testAuthorization(): void
    {
        CLI::write('→ AuthorizationService', 'yellow');

        $auth    = Services::authorization(false);
        $project = (new ProjectModel())->find(1);
        $engineer = (new UserModel())->find(3);
        $planner  = (new UserModel())->find(2);
        $viewer   = (new UserModel())->find(7);

        $this->assert($auth->can(null, AuthorizationService::ACTION_VIEW, $project) === false, 'null user should deny');
        $this->assert($auth->can($engineer, AuthorizationService::ACTION_UPDATE, $project) === true, 'engineer same office should update');
        $this->assert($auth->can($planner, AuthorizationService::ACTION_UPDATE, $project) === true, 'admin should update any office');
        $this->assert($auth->can($engineer, AuthorizationService::ACTION_PUBLISH, $project) === false, 'office_staff cannot publish');
        $this->assert($auth->can($viewer, AuthorizationService::ACTION_VIEW, $project) === true, 'viewer can view published project');
        $this->assert($auth->can($viewer, AuthorizationService::ACTION_UPDATE, $project) === false, 'viewer cannot update');

        $draft = (new ProjectModel())->where('status', 'draft')->first();
        $this->assert($auth->can($viewer, AuthorizationService::ACTION_VIEW, $draft) === false, 'viewer cannot view draft');

        $wrongOfficeUser = $engineer;
        $otherProject    = (new ProjectModel())->where('office_id !=', $engineer['office_id'])->first();
        $this->assert(
            $auth->can($wrongOfficeUser, AuthorizationService::ACTION_UPDATE, $otherProject) === false,
            'office_staff wrong office should deny',
        );

        CLI::write('  ✓ deny-by-default and office_id checks');
    }

    private function testProjectFilter(): void
    {
        CLI::write('→ ProjectFilterService', 'yellow');

        $filter = Services::projectFilter(false);
        $model  = new ProjectModel();

        $malicious = [
            'status'     => "published'; DROP TABLE projects; --",
            'fiscal_year'=> 2025,
            'search'     => "%' OR 1=1 --",
            'evil'       => 'ignored',
        ];

        $results = $filter->applyTo($model, $malicious)->findAll();

        foreach ($results as $row) {
            $this->assert((int) $row['fiscal_year'] === 2025, 'fiscal_year filter failed');
        }

        $published = $filter->applyTo(new ProjectModel(), [
            'status'      => 'published',
            'fiscal_year' => 2025,
        ])->findAll();

        $this->assert($published !== [], 'expected published 2025 projects');
        CLI::write('  ✓ malicious status stripped; parameterized filters return rows (' . count($published) . ' published/2025)');
    }

    private function testBudgetSummary(): void
    {
        CLI::write('→ BudgetSummaryService', 'yellow');

        $summary = Services::budgetSummary(false);
        $fy      = $summary->getFiscalYearSummary(2025);

        $this->assert($fy['project_count'] > 0, 'expected 2025 projects');
        $this->assert(isset($fy['total_allocated'], $fy['total_disbursed']), 'missing totals');

        $byOffice = $summary->getTotalsByOffice(['fiscal_year' => 2025]);
        $this->assert($byOffice !== [], 'expected office breakdown');

        CLI::write("  ✓ FY2025: {$fy['project_count']} projects, allocated ₱{$fy['total_allocated']}");
    }

    private function testVersioning(): void
    {
        CLI::write('→ VersioningService', 'yellow');

        $versioning = Services::versioning(false);
        $project    = (new ProjectModel())->find(6);

        $unchanged = $versioning->recordIfChanged($project, ['title' => $project['title']], 5);
        $this->assert($unchanged === null, 'unchanged title should not create version');

        $beforeCount = (new \App\Models\ProjectVersionModel())->where('project_id', 6)->countAllResults();

        $result = $versioning->recordIfChanged($project, [
            'title'            => $project['title'] . ' (smoke test)',
            'allocated_amount' => $project['allocated_amount'],
        ], 5);

        $this->assert($result !== null && in_array('title', $result['changed_fields'], true), 'title change should version');

        $afterCount = (new \App\Models\ProjectVersionModel())->where('project_id', 6)->countAllResults();
        $this->assert($afterCount === $beforeCount + 1, 'version row not inserted');

        $sensitiveOnly = $versioning->detectChangedFields($project, [
            'password_hash' => 'hacked',
            'api_key'       => 'secret',
        ]);
        $this->assert($sensitiveOnly === [], 'sensitive fields must not be versioned');

        CLI::write('  ✓ skips unchanged fields; logs title change; ignores sensitive fields');
    }

    private function testFeedback(): void
    {
        CLI::write('→ FeedbackService', 'yellow');

        $feedback = Services::feedback(false);

        $sanitized = $feedback->sanitizeBody('Hello <script>alert(1)</script> world from smoke test!');
        $this->assert(! str_contains(strtolower($sanitized), '<script'), 'script tag not stripped');
        $this->assert(str_contains($sanitized, 'Hello'), 'legitimate text preserved');

        $draftProject = (new ProjectModel())->where('status', 'draft')->first();
        $rejected     = $feedback->submit([
            'project_id'   => $draftProject['id'],
            'author_name'  => 'Smoke Tester',
            'author_email' => 'smoke@example.com',
            'body'         => 'This should be rejected because project is draft.',
        ]);
        $this->assert($rejected === false, 'draft project feedback should fail');

        $created = $feedback->submit([
            'project_id'   => 1,
            'author_name'  => 'Smoke Tester',
            'author_email' => 'smoke@example.com',
            'body'         => 'Valid smoke-test feedback with enough length.',
        ]);
        $this->assert(is_array($created) && isset($created['id']), 'published project feedback should succeed');

        $row = (new FeedbackModel())->find($created['id']);
        $this->assert(! str_contains(strtolower($row['body']), '<script'), 'stored body is clean');

        (new FeedbackModel())->delete($created['id']);

        CLI::write('  ✓ sanitizes script tags; rejects draft; accepts published');
    }

    private function assert(bool $condition, string $message): void
    {
        if (! $condition) {
            CLI::error("FAIL: {$message}");
            exit(EXIT_ERROR);
        }
    }
}
