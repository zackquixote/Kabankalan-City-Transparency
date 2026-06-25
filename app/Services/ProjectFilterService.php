<?php

namespace App\Services;

use App\Models\ProjectModel;

/**
 * Applies validated, whitelisted filters to a ProjectModel query via the
 * Query Builder — user input is never interpolated into SQL strings.
 */
class ProjectFilterService
{
    /** @var list<string> */
    private const ALLOWED_FILTER_KEYS = [
        'status',
        'fiscal_year',
        'office_id',
        'vision_id',
        'budget_cycle_stage_id',
        'barangay',
        'search',
        'budget_min',
        'budget_max',
    ];

    /**
     * @param array<string, mixed> $filters Raw request/query filters
     */
    public function applyTo(ProjectModel $model, array $filters): ProjectModel
    {
        $filters = $this->sanitize($filters);

        if (isset($filters['status'])) {
            $model->where($model->table . '.status', $filters['status']);
        }

        if (isset($filters['fiscal_year'])) {
            $model->where($model->table . '.fiscal_year', $filters['fiscal_year']);
        }

        if (isset($filters['office_id'])) {
            $model->where($model->table . '.office_id', $filters['office_id']);
        }

        if (isset($filters['vision_id'])) {
            $model->where($model->table . '.vision_id', $filters['vision_id']);
        }

        if (isset($filters['budget_cycle_stage_id'])) {
            $model->where($model->table . '.budget_cycle_stage_id', $filters['budget_cycle_stage_id']);
        }

        if (isset($filters['barangay'])) {
            $model->where($model->table . '.barangay', $filters['barangay']);
        }

        if (isset($filters['budget_min'])) {
            $model->where($model->table . '.allocated_amount >=', $filters['budget_min']);
        }

        if (isset($filters['budget_max'])) {
            $model->where($model->table . '.allocated_amount <=', $filters['budget_max']);
        }

        if (isset($filters['search']) && $filters['search'] !== '') {
            $table = $model->table;

            $model->groupStart()
                ->like("{$table}.title", $filters['search'])
                ->orLike("{$table}.project_code", $filters['search'])
                ->orLike("{$table}.barangay", $filters['search'])
                ->orLike("{$table}.description", $filters['search'])
                ->groupEnd();
        }

        return $model;
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     */
    public function sanitize(array $filters): array
    {
        $clean = [];

        foreach ($filters as $key => $value) {
            if (! in_array($key, self::ALLOWED_FILTER_KEYS, true)) {
                continue;
            }

            $normalized = $this->normalizeFilterValue($key, $value);

            if ($normalized !== null) {
                $clean[$key] = $normalized;
            }
        }

        return $clean;
    }

    private function normalizeFilterValue(string $key, mixed $value): mixed
    {
        return match ($key) {
            'status' => $this->normalizeStatus($value),
            'fiscal_year', 'office_id', 'vision_id', 'budget_cycle_stage_id' => $this->normalizePositiveInt($value),
            'barangay' => $this->normalizeBarangay($value),
            'search'   => $this->normalizeSearch($value),
            'budget_min', 'budget_max' => $this->normalizeAmount($value),
            default    => null,
        };
    }

    private function normalizeStatus(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return in_array($value, ProjectModel::STATUSES, true) ? $value : null;
    }

    private function normalizePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }

    private function normalizeBarangay(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || strlen($value) > 100) {
            return null;
        }

        return $value;
    }

    private function normalizeSearch(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || strlen($value) > 200) {
            return null;
        }

        return $value;
    }

    private function normalizeAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $float = (float) $value;

        return $float >= 0 ? $float : null;
    }
}
