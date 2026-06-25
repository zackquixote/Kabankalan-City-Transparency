<?php

namespace App\Commands;

use App\Models\FeedbackModel;
use App\Models\ProjectModel;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * End-to-end smoke test for citizen-facing pages (no login).
 *
 * Usage: php spark citizen:smoke
 */
class CitizenSmoke extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'citizen:smoke';
    protected $description = 'Verify public pages load and core citizen flows work.';
    protected $usage       = 'citizen:smoke';

    public function run(array $params): void
    {
        CLI::write('Phase 4 citizen smoke test', 'green');
        CLI::newLine();

        $this->testServices();
        $this->testPaginationUsesLimit();

        CLI::newLine();
        CLI::write('Service/data checks passed. Also open in browser:', 'yellow');
        CLI::write('  http://localhost:8080/');
        CLI::write('  http://localhost:8080/aip');
        CLI::write('  http://localhost:8080/projects/1');
        CLI::write('  http://localhost:8080/feedback/1');
        CLI::newLine();
        CLI::write('All citizen smoke tests passed.', 'green');
    }

    private function testServices(): void
    {
        CLI::write('→ Citizen services', 'yellow');

        $home = service('citizenHome')->getDashboard();
        $this->assert($home['public_project_count'] > 0, 'no public projects on home');

        $visions = service('visionCatalog')->getActiveVisions();
        $this->assert($visions !== [], 'no active visions');

        $registry = service('aipRegistry')->search(['fiscal_year' => 2025], 1);
        $this->assert($registry['projects'] !== [], 'registry empty for FY2025');
        $this->assert($registry['pager']->getPageCount('aip') >= 1, 'pager missing');

        $detail = service('projectDetail')->getPublicDetail(1);
        $this->assert($detail !== null && isset($detail['project']['vision_title']), 'project detail missing joins');

        $draft = service('projectDetail')->getPublicDetail(
            (int) (new ProjectModel())->where('status', 'draft')->first()['id'],
        );
        $this->assert($draft === null, 'draft project must not be public');

        $result = service('feedback')->submit([
            'project_id'   => 1,
            'author_name'  => 'CLI Citizen Test',
            'author_email' => 'cli@example.com',
            'body'         => 'Automated citizen smoke test feedback message.',
        ]);
        $this->assert(is_array($result) && isset($result['id']), 'feedback submit failed');
        (new FeedbackModel())->delete($result['id']);

        CLI::write('  ✓ home, visions, registry, detail, feedback services');
    }

    private function testPaginationUsesLimit(): void
    {
        CLI::write('→ DB pagination (LIMIT/OFFSET)', 'yellow');

        $registry = service('aipRegistry')->search([], 1);

        $this->assert(
            count($registry['projects']) <= \App\Services\AipRegistryService::PER_PAGE,
            'page returned more rows than per-page limit',
        );
        $this->assert($registry['pager']->getPageCount('aip') >= 1, 'pager page count missing');

        CLI::write('  ✓ paginate() caps rows per page (' . \App\Services\AipRegistryService::PER_PAGE . ') via Query Builder');
    }

    private function assert(bool $condition, string $message): void
    {
        if (! $condition) {
            CLI::error("FAIL: {$message}");
            exit(EXIT_ERROR);
        }
    }
}
