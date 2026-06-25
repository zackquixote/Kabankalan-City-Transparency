<?php

namespace App\Models;

use CodeIgniter\Model;

class ProjectModel extends Model
{
    public const STATUSES = [
        'draft',
        'submitted',
        'under_review',
        'approved',
        'published',
        'completed',
        'cancelled',
    ];

    protected $table            = 'projects';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'vision_id',
        'office_id',
        'budget_cycle_stage_id',
        'created_by',
        'project_code',
        'title',
        'description',
        'status',
        'fiscal_year',
        'barangay',
        'latitude',
        'longitude',
        'allocated_amount',
        'obligated_amount',
        'disbursed_amount',
        'target_completion_date',
        'published_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'                    => 'int',
        'vision_id'             => 'int',
        'office_id'             => 'int',
        'budget_cycle_stage_id' => 'int',
        'created_by'            => '?int',
        'fiscal_year'           => 'int',
        'latitude'              => '?float',
        'longitude'             => '?float',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'vision_id'              => 'required|is_natural_no_zero|is_not_unique[visions.id]',
        'office_id'              => 'required|is_natural_no_zero|is_not_unique[offices.id]',
        'budget_cycle_stage_id'  => 'required|is_natural_no_zero|is_not_unique[budget_cycle_stages.id]',
        'created_by'             => 'permit_empty|is_natural_no_zero|is_not_unique[users.id]',
        'project_code'           => 'required|alpha_numeric_punct|max_length[30]|is_unique[projects.project_code,id,{id}]',
        'title'                  => 'required|max_length[255]',
        'description'            => 'permit_empty|max_length[65535]',
        'status'                 => 'required|in_list[draft,submitted,under_review,approved,published,completed,cancelled]',
        'fiscal_year'            => 'required|is_natural|greater_than_equal_to[2000]|less_than_equal_to[2100]',
        'barangay'               => 'permit_empty|max_length[100]',
        'latitude'               => 'permit_empty|decimal|greater_than_equal_to[-90]|less_than_equal_to[90]',
        'longitude'              => 'permit_empty|decimal|greater_than_equal_to[-180]|less_than_equal_to[180]',
        'allocated_amount'       => 'required|decimal|greater_than_equal_to[0]',
        'obligated_amount'       => 'permit_empty|decimal|greater_than_equal_to[0]',
        'disbursed_amount'       => 'permit_empty|decimal|greater_than_equal_to[0]',
        'target_completion_date' => 'permit_empty|valid_date[Y-m-d]',
        'published_at'           => 'permit_empty|valid_date',
    ];

    protected $validationMessages = [
        'vision_id' => [
            'is_not_unique' => 'The selected vision does not exist.',
        ],
        'office_id' => [
            'is_not_unique' => 'The selected office does not exist.',
        ],
        'budget_cycle_stage_id' => [
            'is_not_unique' => 'The selected budget cycle stage does not exist.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    /**
     * Eager-load vision and office display names via Query Builder joins.
     * All table/column identifiers are fixed — no user input in SQL fragments.
     */
    public function withRelations(): static
    {
        $projects = $this->db->escapeIdentifiers($this->table);

        return $this->select([
            "{$projects}.*",
            'visions.title AS vision_title',
            'offices.name AS office_name',
            'offices.code AS office_code',
        ])
            ->join('visions', "visions.id = {$projects}.vision_id", 'left')
            ->join('offices', "offices.id = {$projects}.office_id", 'left');
    }
}
