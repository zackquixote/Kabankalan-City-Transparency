<?php

namespace App\Services;

use App\Models\ProjectVersionModel;

/**
 * Records immutable project version snapshots when tracked fields change.
 */
class VersioningService
{
    /** @var list<string> */
    public const VERSIONED_FIELDS = [
        'title',
        'description',
        'status',
        'allocated_amount',
        'obligated_amount',
        'disbursed_amount',
    ];

    /**
     * Never written to version history, even if present in $after data.
     *
     * @var list<string>
     */
    public const SENSITIVE_FIELDS = [
        'password',
        'password_hash',
        'api_key',
        'secret',
        'token',
        'encryption_key',
        'OPENMODEL_API_KEY',
    ];

    public function __construct(
        private ?ProjectVersionModel $versionModel = null,
    ) {
        $this->versionModel = $versionModel ?? model(ProjectVersionModel::class);
    }

    /**
     * @param array<string, mixed> $before Current persisted project row
     * @param array<string, mixed> $after  Incoming changes (may be partial)
     *
     * @return array{version_id: int, changed_fields: list<string>}|null
     */
    public function recordIfChanged(array $before, array $after, ?int $userId = null): ?array
    {
        $projectId = (int) ($before['id'] ?? 0);

        if ($projectId < 1) {
            return null;
        }

        $changedFields = $this->detectChangedFields($before, $after);

        if ($changedFields === []) {
            return null;
        }

        $snapshot = $this->buildSnapshot($before, $after);
        $versionNumber = $this->nextVersionNumber($projectId);

        $versionId = $this->versionModel->insert([
            'project_id'       => $projectId,
            'version_number'   => $versionNumber,
            'created_by'       => $userId,
            'change_summary'   => $this->buildChangeSummary($changedFields),
            'title'            => $snapshot['title'],
            'description'      => $snapshot['description'],
            'status'           => $snapshot['status'],
            'allocated_amount' => $snapshot['allocated_amount'],
            'obligated_amount' => $snapshot['obligated_amount'],
            'disbursed_amount' => $snapshot['disbursed_amount'],
        ], true);

        if ($versionId === false) {
            return null;
        }

        return [
            'version_id'      => (int) $versionId,
            'changed_fields'  => $changedFields,
        ];
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     *
     * @return list<string>
     */
    public function detectChangedFields(array $before, array $after): array
    {
        $changed = [];

        foreach (self::VERSIONED_FIELDS as $field) {
            if ($this->isSensitiveField($field)) {
                continue;
            }

            if (! array_key_exists($field, $after)) {
                continue;
            }

            $oldValue = $before[$field] ?? null;
            $newValue = $after[$field];

            if (! $this->valuesAreEquivalent($field, $oldValue, $newValue)) {
                $changed[] = $field;
            }
        }

        return $changed;
    }

    /**
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     *
     * @return array<string, mixed>
     */
    private function buildSnapshot(array $before, array $after): array
    {
        $snapshot = [];

        foreach (self::VERSIONED_FIELDS as $field) {
            if ($this->isSensitiveField($field)) {
                continue;
            }

            if (array_key_exists($field, $after)) {
                $snapshot[$field] = $after[$field];
            } else {
                $snapshot[$field] = $before[$field] ?? null;
            }
        }

        return $snapshot;
    }

    private function nextVersionNumber(int $projectId): int
    {
        $latest = $this->versionModel
            ->where('project_id', $projectId)
            ->selectMax('version_number', 'max_version')
            ->first();

        return (int) ($latest['max_version'] ?? 0) + 1;
    }

    /**
     * @param list<string> $changedFields
     */
    private function buildChangeSummary(array $changedFields): string
    {
        return 'Updated ' . implode(', ', $changedFields);
    }

    private function isSensitiveField(string $field): bool
    {
        return in_array($field, self::SENSITIVE_FIELDS, true);
    }

    private function valuesAreEquivalent(string $field, mixed $oldValue, mixed $newValue): bool
    {
        if (in_array($field, ['allocated_amount', 'obligated_amount', 'disbursed_amount'], true)) {
            return $this->normalizeMoney($oldValue) === $this->normalizeMoney($newValue);
        }

        if ($field === 'description') {
            return $this->normalizeNullableString($oldValue) === $this->normalizeNullableString($newValue);
        }

        return (string) ($oldValue ?? '') === (string) ($newValue ?? '');
    }

    private function normalizeMoney(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function normalizeNullableString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        return trim((string) $value);
    }
}
