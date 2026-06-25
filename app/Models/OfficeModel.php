<?php

namespace App\Models;

use CodeIgniter\Model;

class OfficeModel extends Model
{
    protected $table            = 'offices';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'code',
        'name',
        'description',
        'contact_email',
        'is_active',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [
        'id'        => 'int',
        'is_active' => 'int',
    ];

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'code'          => 'required|alpha_numeric_punct|max_length[20]|is_unique[offices.code,id,{id}]',
        'name'          => 'required|max_length[150]',
        'description'   => 'permit_empty|max_length[65535]',
        'contact_email' => 'permit_empty|valid_email|max_length[255]',
        'is_active'     => 'permit_empty|in_list[0,1]',
    ];

    protected $skipValidation       = false;
    protected $cleanValidationRules = true;
}
