<?php

namespace App\Models;

use CodeIgniter\Model;

class VisionModel extends Model
{
    protected $table            = 'visions';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'title',
        'description',
        'start_year',
        'end_year',
        'is_active',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'         => 'int',
        'start_year' => 'int',
        'end_year'   => 'int',
        'is_active'  => 'int',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'title'       => 'required|max_length[255]',
        'description' => 'permit_empty|max_length[65535]',
        'start_year'  => 'required|is_natural|greater_than_equal_to[2000]|less_than_equal_to[2100]',
        'end_year'    => 'required|is_natural|greater_than_equal_to[{start_year}]|less_than_equal_to[2100]',
        'is_active'   => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'end_year' => [
            'greater_than_equal_to' => 'End year must be greater than or equal to the start year.',
        ],
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
