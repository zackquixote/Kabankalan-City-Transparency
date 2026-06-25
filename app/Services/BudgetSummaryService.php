<?php

namespace App\Services;

use App\Models\ProjectModel;

/**
 * Aggregates project budget figures using parameterized Query Builder queries.
 */
class BudgetSummaryService
{
    public function __construct(
        private ?ProjectModel $projectModel = null,
        private ?ProjectFilterService $filterService = null,
    ) {
        $this->projectModel  = $projectModel ?? model(ProjectModel::class);
        $this->filterService = $filterService ?? new ProjectFilterService();
    }

    /**
     * @param array<string, mixed> $filters Optional filters (sanitized internally)
     *
     * @return array{
     *     project_count: int,
     *     total_allocated: string,
     *     total_obligated: string,
     *     total_disbursed: string,
     *     remaining_obligation: string
     * }
     */
    public function getOverallTotals(array $filters = [], bool $publicOnly = false): array
    {
        $builder = $this->filteredBuilder($filters, $publicOnly);

        $row = $builder
            ->select('COUNT(*) AS project_count', false)
            ->selectSum('allocated_amount', 'total_allocated')
            ->selectSum('obligated_amount', 'total_obligated')
            ->selectSum('disbursed_amount', 'total_disbursed')
            ->get()
            ->getRowArray();

        return $this->formatTotalsRow($row);
    }

    /**
     * @return array{
     *     fiscal_year: int,
     *     project_count: int,
     *     total_allocated: string,
     *     total_obligated: string,
     *     total_disbursed: string,
     *     remaining_obligation: string
     * }
     */
    public function getFiscalYearSummary(int $fiscalYear, bool $publicOnly = false): array
    {
        $totals = $this->getOverallTotals(['fiscal_year' => $fiscalYear], $publicOnly);

        return ['fiscal_year' => $fiscalYear] + $totals;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<array{
     *     office_id: int,
     *     office_name: string,
     *     project_count: int,
     *     total_allocated: string,
     *     total_obligated: string,
     *     total_disbursed: string
     * }>
     */
    public function getTotalsByOffice(array $filters = [], bool $publicOnly = false): array
    {
        $builder = $this->filteredBuilder($filters, $publicOnly);

        $rows = $builder
            ->select('projects.office_id')
            ->select('offices.name AS office_name')
            ->select('COUNT(*) AS project_count', false)
            ->selectSum('allocated_amount', 'total_allocated')
            ->selectSum('obligated_amount', 'total_obligated')
            ->selectSum('disbursed_amount', 'total_disbursed')
            ->join('offices', 'offices.id = projects.office_id', 'left')
            ->groupBy('projects.office_id, offices.name')
            ->orderBy('offices.name', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static function (array $row): array {
            return [
                'office_id'       => (int) $row['office_id'],
                'office_name'     => $row['office_name'] ?? 'Unknown Office',
                'project_count'   => (int) ($row['project_count'] ?? 0),
                'total_allocated' => self::formatMoney($row['total_allocated'] ?? '0'),
                'total_obligated' => self::formatMoney($row['total_obligated'] ?? '0'),
                'total_disbursed' => self::formatMoney($row['total_disbursed'] ?? '0'),
            ];
        }, $rows);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<array{
     *     fiscal_year: int,
     *     total_allocated: string,
     *     total_obligated: string,
     *     total_disbursed: string
     * }>
     */
    public function getTotalsByYear(array $filters = [], bool $publicOnly = false): array
    {
        $trendFilters = $filters;
        unset($trendFilters['fiscal_year']);

        $builder = $this->filteredBuilder($trendFilters, $publicOnly);

        $rows = $builder
            ->select('projects.fiscal_year')
            ->selectSum('allocated_amount', 'total_allocated')
            ->selectSum('obligated_amount', 'total_obligated')
            ->selectSum('disbursed_amount', 'total_disbursed')
            ->groupBy('projects.fiscal_year')
            ->orderBy('projects.fiscal_year', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static function (array $row): array {
            return [
                'fiscal_year'     => (int) $row['fiscal_year'],
                'total_allocated' => self::formatMoney($row['total_allocated'] ?? '0'),
                'total_obligated' => self::formatMoney($row['total_obligated'] ?? '0'),
                'total_disbursed' => self::formatMoney($row['total_disbursed'] ?? '0'),
            ];
        }, $rows);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return list<array{status: string, project_count: int}>
     */
    public function getCountsByStatus(array $filters = [], bool $publicOnly = false): array
    {
        $builder = $this->filteredBuilder($filters, $publicOnly);

        $rows = $builder
            ->select('status')
            ->select('COUNT(*) AS project_count', false)
            ->groupBy('status')
            ->orderBy('status', 'ASC')
            ->get()
            ->getResultArray();

        return array_map(static fn (array $row): array => [
            'status'        => (string) $row['status'],
            'project_count' => (int) ($row['project_count'] ?? 0),
        ], $rows);
    }

    /**
     * @param array<string, mixed> $filters
     */
    private function filteredBuilder(array $filters, bool $publicOnly = false)
    {
        $model = new ProjectModel();

        if ($publicOnly) {
            $model->whereIn($model->table . '.status', ['published', 'completed']);
        }

        return $this->filterService
            ->applyTo($model, $filters)
            ->builder();
    }

    /**
     * @param array<string, mixed>|null $row
     *
     * @return array{
     *     project_count: int,
     *     total_allocated: string,
     *     total_obligated: string,
     *     total_disbursed: string,
     *     remaining_obligation: string
     * }
     */
    private function formatTotalsRow(?array $row): array
    {
        $allocated = self::formatMoney($row['total_allocated'] ?? '0');
        $obligated = self::formatMoney($row['total_obligated'] ?? '0');
        $disbursed = self::formatMoney($row['total_disbursed'] ?? '0');

        return [
            'project_count'        => (int) ($row['project_count'] ?? 0),
            'total_allocated'      => $allocated,
            'total_obligated'      => $obligated,
            'total_disbursed'      => $disbursed,
            'remaining_obligation' => self::formatMoney((float) $obligated - (float) $disbursed),
        ];
    }

    private static function formatMoney(mixed $value): string
    {
        return number_format((float) $value, 2, '.', '');
    }
}
