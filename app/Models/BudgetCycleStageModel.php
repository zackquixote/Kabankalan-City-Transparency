<?php

namespace App\Models;

use CodeIgniter\Model;

class BudgetCycleStageModel extends Model
{
    protected $table            = 'budget_cycle_stages';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'slug',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'         => 'int',
        'sort_order' => 'int',
        'is_active'  => 'int',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'slug'        => 'required|alpha_dash|max_length[50]|is_unique[budget_cycle_stages.slug,id,{id}]',
        'name'        => 'required|max_length[100]',
        'description' => 'permit_empty|max_length[65535]',
        'sort_order'  => 'permit_empty|is_natural|less_than_equal_to[255]',
        'is_active'   => 'permit_empty|in_list[0,1]',
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
