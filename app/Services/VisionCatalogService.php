<?php

namespace App\Services;

use App\Models\ProjectModel;
use App\Models\VisionModel;

/**
 * Public vision listing with related project counts.
 */
class VisionCatalogService
{
    public function __construct(
        private ?VisionModel $visionModel = null,
        private ?ProjectModel $projectModel = null,
    ) {
        $this->visionModel  = $visionModel ?? model(VisionModel::class);
        $this->projectModel = $projectModel ?? model(ProjectModel::class);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getActiveVisions(): array
    {
        $visions = $this->visionModel
            ->where('is_active', 1)
            ->orderBy('start_year', 'DESC')
            ->findAll();

        return array_map(function (array $vision): array {
            $vision['public_project_count'] = $this->countPublicProjectsForVision((int) $vision['id']);

            return $vision;
        }, $visions);
    }

    private function countPublicProjectsForVision(int $visionId): int
    {
        return $this->projectModel
            ->where('vision_id', $visionId)
            ->whereIn('status', ['published', 'completed'])
            ->countAllResults();
    }
}
