<?php

namespace App\Controllers;

use App\Models\ProjectAttachmentModel;
use App\Models\ProjectModel;
use App\Services\AttachmentService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Serves project attachment files to the public.
 *
 * Security:
 *  - Only serves attachments belonging to published/completed projects
 *  - Resolves disk path through AttachmentService (prevents path traversal)
 *  - Streams file content — never reveals stored filename in URL
 */
class ProjectAttachment extends BaseController
{
    /** @var list<string> */
    private const PUBLIC_STATUSES = ['published', 'completed'];

    public function download(int $attachmentId): ResponseInterface
    {
        $attachmentModel = model(ProjectAttachmentModel::class);
        $attachment      = $attachmentModel->find($attachmentId);

        if ($attachment === null) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Gate: project must be publicly visible
        $projectModel = model(ProjectModel::class);
        $project      = $projectModel->find((int) $attachment['project_id']);

        if ($project === null || ! in_array($project['status'], self::PUBLIC_STATUSES, true)) {
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Resolve the actual disk path safely
        $service  = new AttachmentService($attachmentModel);
        $diskPath = $service->resolveDiskPath($attachment['stored_filename']);

        if ($diskPath === null) {
            // File missing from disk — log and 404
            log_message('error', "Attachment #{$attachmentId} record exists but file not found on disk: {$attachment['stored_filename']}");
            throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
        }

        // Stream the file with correct headers
        $mime     = $attachment['mime_type'];
        $filename = $attachment['original_filename'];

        // Images are shown inline; documents are downloaded
        $isImage     = str_starts_with($mime, 'image/');
        $disposition = $isImage ? 'inline' : 'attachment';

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', "{$disposition}; filename=\"{$filename}\"")
            ->setHeader('Content-Length', (string) filesize($diskPath))
            ->setHeader('Cache-Control', 'private, max-age=86400')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody(file_get_contents($diskPath));
    }
}
