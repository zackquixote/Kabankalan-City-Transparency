<?php

namespace App\Models;

use CodeIgniter\Model;

class FeedbackModel extends Model
{
    public const STATUSES = [
        'pending',
        'reviewed',
        'addressed',
        'dismissed',
    ];

    protected $table            = 'feedback';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'project_id',
        'user_id',
        'author_name',
        'author_email',
        'body',
        'status',
        'admin_response',
        'responded_by',
        'responded_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'         => 'int',
        'project_id' => 'int',
        'user_id'    => '?int',
        'responded_by' => '?int',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'project_id'     => 'required|is_natural_no_zero|is_not_unique[projects.id]',
        'user_id'        => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
        'author_name'    => 'required|max_length[150]',
        'author_email'   => 'permit_empty|valid_email|max_length[255]',
        'body'           => 'required|min_length[10]|max_length[65535]',
        'status'         => 'permit_empty|in_list[pending,reviewed,addressed,dismissed]',
        'admin_response' => 'permit_empty|max_length[65535]',
        'responded_by'   => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
        'responded_at'   => 'permit_empty|valid_date',
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
