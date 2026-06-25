<?php

namespace App\Services;

use App\Models\FeedbackModel;
use App\Models\ProjectAttachmentModel;
use App\Models\ProjectModel;
use App\Models\ProjectVersionModel;

/**
 * Public read-only project detail for citizens.
 */
class ProjectDetailService
{
    /** @var list<string> */
    private const PUBLIC_STATUSES = ['published', 'completed'];

    public function __construct(
        private ?ProjectModel $projectModel = null,
        private ?ProjectVersionModel $versionModel = null,
        private ?FeedbackModel $feedbackModel = null,
        private ?ProjectAttachmentModel $attachmentModel = null,
    ) {
        $this->projectModel    = $projectModel ?? model(ProjectModel::class);
        $this->versionModel    = $versionModel ?? model(ProjectVersionModel::class);
        $this->feedbackModel   = $feedbackModel ?? model(FeedbackModel::class);
        $this->attachmentModel = $attachmentModel ?? model(ProjectAttachmentModel::class);
    }

    /**
     * @return array{
     *     project: array<string, mixed>,
     *     versions: list<array<string, mixed>>,
     *     feedback_count: int,
     *     public_feedback: list<array<string, mixed>>
     * }|null
     */
    public function getPublicDetail(int $projectId): ?array
    {
        if ($projectId < 1) {
            return null;
        }

        $project = $this->projectModel->withRelations()->find($projectId);

        if ($project === null || ! in_array($project['status'], self::PUBLIC_STATUSES, true)) {
            return null;
        }

        $versions = $this->versionModel
            ->where('project_id', $projectId)
            ->orderBy('version_number', 'DESC')
            ->findAll(10);

        $publicFeedback = $this->feedbackModel
            ->where('project_id', $projectId)
            ->whereIn('status', ['reviewed', 'addressed'])
            ->orderBy('created_at', 'DESC')
            ->findAll(20);

        // Related projects: same office, public, not the current one, capped at 3
        $related = $this->projectModel
            ->withRelations()
            ->where('projects.office_id', $project['office_id'])
            ->whereIn('projects.status', self::PUBLIC_STATUSES)
            ->where('projects.id !=', $projectId)
            ->orderBy('projects.published_at', 'DESC')
            ->findAll(3);

        return [
            'project'          => $project,
            'versions'         => $versions,
            'feedback_count'   => $this->feedbackModel->where('project_id', $projectId)->countAllResults(),
            'public_feedback'  => $publicFeedback,
            'related_projects' => $related,
            'attachments'      => $this->attachmentModel->forProject($projectId),
        ];
    }

    /**
     * @return array{project: array<string, mixed>}|null
     */
    public function getForFeedbackForm(int $projectId): ?array
    {
        $detail = $this->getPublicDetail($projectId);

        if ($detail === null) {
            return null;
        }

        return ['project' => $detail['project']];
    }
}
