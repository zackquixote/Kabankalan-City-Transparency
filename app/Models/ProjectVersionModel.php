<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectVersionModel extends Model
{
    protected $table            = 'project_versions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'project_id',
        'version_number',
        'created_by',
        'change_summary',
        'title',
        'description',
        'status',
        'allocated_amount',
        'obligated_amount',
        'disbursed_amount',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'             => 'int',
        'project_id'     => 'int',
        'version_number' => 'int',
        'created_by'     => '?int',
    ];

    // Immutable snapshots: created_at only, no updated_at column.
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = '';

    protected $validationRules = [
        'project_id'       => 'required|is_natural_no_zero|is_not_unique[projects.id]',
        'version_number'   => 'required|is_natural_no_zero|less_than_equal_to[9999]',
        'created_by'       => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
        'change_summary'   => 'permit_empty|max_length[500]',
        'title'            => 'required|max_length[255]',
        'description'      => 'permit_empty|max_length[65535]',
        'status'           => 'required|max_length[50]|in_list[draft,submitted,under_review,approved,published,completed,cancelled]',
        'allocated_amount' => 'required|decimal|greater_than_equal_to[0]',
        'obligated_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
        'disbursed_amount' => 'permit_empty|decimal|greater_than_equal_to[0]',
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
