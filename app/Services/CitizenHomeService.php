<?php

namespace App\Services;

use App\Models\ProjectModel;
use App\Models\VisionModel;

/**
 * Data for the public home page dashboard.
 */
class CitizenHomeService
{
    public function __construct(
        private ?VisionModel $visionModel = null,
        private ?ProjectModel $projectModel = null,
        private ?BudgetSummaryService $budgetSummary = null,
    ) {
        $this->visionModel   = $visionModel ?? model(VisionModel::class);
        $this->projectModel  = $projectModel ?? model(ProjectModel::class);
        $this->budgetSummary = $budgetSummary ?? new BudgetSummaryService();
    }

    /**
     * @return array{
     *     active_vision_count: int,
     *     public_project_count: int,
     *     fiscal_year_summary: array<string, mixed>,
     *     recent_projects: list<array<string, mixed>>
     * }
     */
    public function getDashboard(): array
    {
        $currentYear = (int) date('Y');

        return [
            'active_vision_count'  => $this->visionModel->where('is_active', 1)->countAllResults(),
            'public_project_count' => $this->countPublicProjects(),
            'fiscal_year_summary'    => $this->budgetSummary->getFiscalYearSummary($currentYear, true),
            'recent_projects'      => $this->getRecentPublicProjects(),
        ];
    }

    private function countPublicProjects(): int
    {
        return $this->projectModel
            ->whereIn('status', ['published', 'completed'])
            ->countAllResults();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function getRecentPublicProjects(int $limit = 5): array
    {
        return $this->projectModel
            ->withRelations()
            ->whereIn($this->projectModel->table . '.status', ['published', 'completed'])
            ->orderBy($this->projectModel->table . '.published_at', 'DESC')
            ->findAll($limit);
    }
}
