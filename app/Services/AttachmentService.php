<?php

namespace App\Services;

use App\Models\ProjectAttachmentModel;
use CodeIgniter\Files\File;
use CodeIgniter\HTTP\Files\UploadedFile;

/**
 * Handles secure project attachment uploads.
 *
 * Security contract:
 *  - MIME type validated with PHP's finfo (not extension sniffing)
 *  - File is renamed to a UUID on disk — original name never touches the filesystem
 *  - Files stored at WRITEPATH . 'uploads/attachments/' (outside public/)
 *  - Served only through a controller that checks project visibility
 */
class AttachmentService
{
    /** Maximum allowed file size in bytes (10 MB) */
    public const MAX_BYTES = 10 * 1024 * 1024;

    /** Allowed MIME types → canonical extension */
    private const ALLOWED_MIMES = [
        'image/jpeg'       => 'jpg',
        'image/png'        => 'png',
        'image/webp'       => 'webp',
        'image/gif'        => 'gif',
        'application/pdf'  => 'pdf',
        // Word documents
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    private string $uploadDir;

    public function __construct(private ?ProjectAttachmentModel $model = null)
    {
        $this->model     = $model ?? model(ProjectAttachmentModel::class);
        $this->uploadDir = WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'attachments' . DIRECTORY_SEPARATOR;
    }

    /**
     * Validate and store an uploaded file, then insert the DB record.
     *
     * @param UploadedFile         $file       The incoming upload
     * @param int                  $projectId
     * @param int|null             $userId     Who uploaded it
     * @param string|null          $label      Optional human label
     *
     * @return array{ok: true,  attachment: array<string, mixed>}
     *       | array{ok: false, error: string}
     */
    public function store(UploadedFile $file, int $projectId, ?int $userId, ?string $label = null): array
    {
        // ── 1. Basic upload validity ──────────────────────────────────
        if (! $file->isValid()) {
            return ['ok' => false, 'error' => 'Upload failed: ' . $file->getErrorString()];
        }

        if ($file->hasMoved()) {
            return ['ok' => false, 'error' => 'File has already been moved.'];
        }

        // ── 2. Size check ──────────────────────────────────────────────
        if ($file->getSizeByUnit('b') > self::MAX_BYTES) {
            $max = round(self::MAX_BYTES / 1024 / 1024);
            return ['ok' => false, 'error' => "File exceeds the maximum allowed size of {$max} MB."];
        }

        // ── 3. MIME validation using finfo (not extension) ─────────────
        $tmpPath  = $file->getTempName();
        $realMime = $this->detectMime($tmpPath);

        if ($realMime === null || ! array_key_exists($realMime, self::ALLOWED_MIMES)) {
            $allowed = implode(', ', array_keys(self::ALLOWED_MIMES));
            return ['ok' => false, 'error' => "File type not allowed. Permitted types: {$allowed}."];
        }

        // ── 4. Generate UUID stored filename ──────────────────────────
        $ext            = self::ALLOWED_MIMES[$realMime];
        $storedFilename = $this->generateUuid() . '.' . $ext;
        $originalName   = $this->sanitizeOriginalName($file->getClientName() ?? 'upload');

        // ── 5. Ensure upload directory exists ──────────────────────────
        if (! is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }

        // ── 6. Move file to secure storage ────────────────────────────
        $file->move($this->uploadDir, $storedFilename, true);

        if (! file_exists($this->uploadDir . $storedFilename)) {
            return ['ok' => false, 'error' => 'Failed to save file to disk.'];
        }

        // ── 7. Insert DB record ───────────────────────────────────────
        $insertId = $this->model->insert([
            'project_id'        => $projectId,
            'uploaded_by'       => $userId,
            'original_filename' => $originalName,
            'stored_filename'   => $storedFilename,
            'mime_type'         => $realMime,
            'file_size'         => $file->getSizeByUnit('b'),
            'label'             => $label ? substr(strip_tags($label), 0, 255) : null,
        ], true);

        if ($insertId === false) {
            // DB insert failed — remove the file we just wrote
            @unlink($this->uploadDir . $storedFilename);
            return ['ok' => false, 'error' => 'Database error while saving attachment record.'];
        }

        $attachment = $this->model->find((int) $insertId);

        return ['ok' => true, 'attachment' => $attachment];
    }

    /**
     * Delete an attachment record and its file from disk.
     *
     * @return array{ok: true}|array{ok: false, error: string}
     */
    public function delete(int $attachmentId): array
    {
        $record = $this->model->find($attachmentId);

        if ($record === null) {
            return ['ok' => false, 'error' => 'Attachment not found.'];
        }

        // Remove from disk first (safe — DB record is the source of truth)
        $diskPath = $this->uploadDir . $record['stored_filename'];
        if (file_exists($diskPath)) {
            @unlink($diskPath);
        }

        $this->model->delete($attachmentId);

        return ['ok' => true];
    }

    /**
     * Return the absolute disk path for a stored filename.
     * Returns null if the file does not exist.
     */
    public function resolveDiskPath(string $storedFilename): ?string
    {
        // Prevent path traversal — filename must be a bare UUID.ext, no slashes
        if (strpbrk($storedFilename, '/\\') !== false) {
            return null;
        }

        $path = $this->uploadDir . $storedFilename;

        return file_exists($path) ? $path : null;
    }

    // ──────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────

    private function detectMime(string $path): ?string
    {
        if (! function_exists('finfo_open')) {
            // Fallback: use PHP's mime_content_type()
            $mime = mime_content_type($path);
            return $mime !== false ? $mime : null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime !== false ? $mime : null;
    }

    private function generateUuid(): string
    {
        // RFC-4122 v4 UUID
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    private function sanitizeOriginalName(string $name): string
    {
        // Strip path components, limit length, keep only safe characters
        $base = basename($name);
        $base = preg_replace('/[^\w.\-]/', '_', $base) ?? 'upload';

        return substr($base, 0, 200);
    }
}
