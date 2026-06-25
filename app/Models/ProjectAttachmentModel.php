<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectAttachmentModel extends Model
{
    protected $table            = 'project_attachments';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'project_id',
        'uploaded_by',
        'original_filename',
        'stored_filename',
        'mime_type',
        'file_size',
        'label',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'          => 'int',
        'project_id'  => 'int',
        'uploaded_by' => '?int',
        'file_size'   => 'int',
    ];

    // Immutable records — only created_at, no updated_at
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'project_id'        => 'required|is_natural_no_zero|is_not_unique[projects.id]',
        'uploaded_by'       => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
        'original_filename' => 'required|max_length[255]',
        'stored_filename'   => 'required|max_length[255]|is_unique[project_attachments.stored_filename]',
        'mime_type'         => 'required|max_length[100]',
        'file_size'         => 'required|is_natural_no_zero',
        'label'             => 'permit_empty|max_length[255]',
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Get all attachments for a project, newest first.
     *
     * @return list<array<string, mixed>>
     */
    public function forProject(int $projectId): array
    {
        return $this->where('project_id', $projectId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll();
    }

    /**
     * @return list<string> MIME types considered images (shown in gallery)
     */
    public static function imageMimes(): array
    {
        return ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    }

    /**
     * @return list<string> MIME types considered documents
     */
    public static function documentMimes(): array
    {
        return ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    }
}
