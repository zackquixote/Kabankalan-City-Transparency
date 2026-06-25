<?php

namespace App\Services;

use App\Models\OfficeModel;
use App\Models\ProjectModel;
use App\Models\VisionModel;
use CodeIgniter\Pager\Pager;

/**
 * Paginated public AIP / project registry (published & completed only).
 */
class AipRegistryService
{
    public const PER_PAGE = 12;

    /** @var list<string> */
    private const PUBLIC_STATUSES = ['published', 'completed'];

    public function __construct(
        private ?ProjectModel $projectModel = null,
        private ?ProjectFilterService $filterService = null,
        private ?OfficeModel $officeModel = null,
        private ?VisionModel $visionModel = null,
    ) {
        $this->projectModel  = $projectModel ?? model(ProjectModel::class);
        $this->filterService = $filterService ?? new ProjectFilterService();
        $this->officeModel   = $officeModel ?? model(OfficeModel::class);
        $this->visionModel   = $visionModel ?? model(VisionModel::class);
    }

    /**
     * @param array<string, mixed> $rawFilters
     *
     * @return array{
     *     projects: list<array<string, mixed>>,
     *     pager: Pager,
     *     filters: array<string, mixed>,
     *     filter_options: array{fiscal_years: list<int>, offices: list<array<string, mixed>>, statuses: list<string>}
     * }
     */
    public function search(array $rawFilters, int $page = 1): array
    {
        $filters = $this->filterService->sanitize($rawFilters);

        if (isset($filters['status']) && ! in_array($filters['status'], self::PUBLIC_STATUSES, true)) {
            unset($filters['status']);
        }

        $table = $this->projectModel->table;
        $model = $this->filterService->applyTo($this->projectModel->withRelations(), $filters);

        if (! isset($filters['status'])) {
            $model->whereIn("{$table}.status", self::PUBLIC_STATUSES);
        }

        $projects = $model
            ->orderBy("{$table}.published_at", 'DESC')
            ->orderBy("{$table}.title", 'ASC')
            ->paginate(self::PER_PAGE, 'aip', max(1, $page));

        $pager = $this->projectModel->pager;
        $pager->only(array_keys($filters));

        return [
            'projects'       => $projects,
            'pager'          => $pager,
            'filters'        => $filters,
            'filter_options' => $this->getFilterOptions(),
        ];
    }

    /**
     * @return array{fiscal_years: list<int>, offices: list<array<string, mixed>>, visions: list<array<string, mixed>>, statuses: list<string>}
     */
    public function getFilterOptions(): array
    {
        $table = $this->projectModel->table;

        $yearRows = $this->projectModel->builder()
            ->select('fiscal_year')
            ->distinct()
            ->whereIn('status', self::PUBLIC_STATUSES)
            ->orderBy('fiscal_year', 'DESC')
            ->get()
            ->getResultArray();

        $officeIds = $this->projectModel->builder()
            ->select('office_id')
            ->distinct()
            ->whereIn('status', self::PUBLIC_STATUSES)
            ->get()
            ->getResultArray();

        $ids = array_map(static fn (array $row): int => (int) $row['office_id'], $officeIds);

        $offices = $ids === []
            ? []
            : $this->officeModel->whereIn('id', $ids)->orderBy('name', 'ASC')->findAll();

        // Visions that have at least one public project
        $visionIds = $this->projectModel->builder()
            ->select('vision_id')
            ->distinct()
            ->whereIn('status', self::PUBLIC_STATUSES)
            ->get()
            ->getResultArray();

        $vIds = array_map(static fn (array $row): int => (int) $row['vision_id'], $visionIds);

        $visions = $vIds === []
            ? []
            : $this->visionModel->whereIn('id', $vIds)->orderBy('title', 'ASC')->findAll();

        return [
            'fiscal_years' => array_map(static fn (array $row): int => (int) $row['fiscal_year'], $yearRows),
            'offices'      => $offices,
            'visions'      => $visions,
            'statuses'     => self::PUBLIC_STATUSES,
        ];
    }
}
