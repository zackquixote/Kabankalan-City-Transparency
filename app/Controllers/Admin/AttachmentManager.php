<?php

namespace App\Controllers\Admin;

use App\Models\ProjectAttachmentModel;
use App\Models\ProjectModel;
use App\Services\AttachmentService;
use App\Services\AuthorizationService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * AttachmentManager — upload and delete project attachments.
 *
 * Security:
 *  - AdminAuthFilter already blocks unauthenticated users
 *  - AuthorizationService::can(UPDATE) checked before every write
 *  - AttachmentService validates MIME type via finfo and renames to UUID
 *  - Deletes check that the attachment belongs to the requested project
 */
class AttachmentManager extends AdminBaseController
{
    private ProjectModel           $projectModel;
    private ProjectAttachmentModel $attachmentModel;
    private AttachmentService      $attachmentService;

    public function __construct()
    {
        parent::__construct();
        $this->projectModel      = new ProjectModel();
        $this->attachmentModel   = new ProjectAttachmentModel();
        $this->attachmentService = new AttachmentService($this->attachmentModel);
    }

    // ──────────────────────────────────────────────────────────────────
    // Upload
    // ──────────────────────────────────────────────────────────────────

    public function upload(int $projectId): ResponseInterface
    {
        $project = $this->findProjectOr404($projectId);
        $user    = $this->getFullUser();

        $this->denyUnless(
            $this->authz()->can($user, AuthorizationService::ACTION_UPDATE, $project)
        );

        $file  = $this->request->getFile('attachment');
        $label = $this->request->getPost('label');

        if ($file === null || ! $file->isValid()) {
            return redirect()->back()->with('error', 'No valid file was uploaded.');
        }

        $result = $this->attachmentService->store($file, $projectId, (int) $user['id'], $label);

        if (! $result['ok']) {
            return redirect()->back()->with('error', $result['error']);
        }

        return redirect()->to(site_url('admin/projects/' . $projectId))
            ->with('success', 'Attachment "' . esc($result['attachment']['original_filename']) . '" uploaded successfully.');
    }

    // ──────────────────────────────────────────────────────────────────
    // Delete
    // ──────────────────────────────────────────────────────────────────

    public function delete(int $projectId, int $attachmentId): ResponseInterface
    {
        $project    = $this->findProjectOr404($projectId);
        $attachment = $this->attachmentModel->find($attachmentId);

        if ($attachment === null || (int) $attachment['project_id'] !== $projectId) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Attachment #{$attachmentId} not found for project #{$projectId}.");
        }

        $user = $this->getFullUser();

        $this->denyUnless(
            $this->authz()->can($user, AuthorizationService::ACTION_UPDATE, $project)
        );

        $result = $this->attachmentService->delete($attachmentId);

        if (! $result['ok']) {
            return redirect()->back()->with('error', $result['error']);
        }

        return redirect()->to(site_url('admin/projects/' . $projectId))
            ->with('success', 'Attachment deleted.');
    }

    // ──────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────

    private function findProjectOr404(int $id): array
    {
        $project = $this->projectModel->find($id);

        if ($project === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Project #{$id} not found.");
        }

        return $project;
    }
}
