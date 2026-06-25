<?php

namespace App\Controllers\Admin;

use App\Models\VisionModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * VisionManager — CRUD for development visions / plan periods.
 *
 * Allowed roles: super_admin, admin only.
 * Visions are not office-scoped, so we use requireRole() rather than
 * forcing them through the project-oriented AuthorizationService policy.
 */
class VisionManager extends AdminBaseController
{
    private VisionModel $visionModel;

    public function __construct()
    {
        parent::__construct();
        $this->visionModel = new VisionModel();
    }

    public function index(): string
    {
        $this->requireRole('super_admin', 'admin');

        $visions = $this->visionModel->orderBy('start_year', 'DESC')->findAll();

        return $this->adminView('admin/visions/index', [
            'title'   => 'Manage Visions',
            'visions' => $visions,
        ]);
    }

    public function new(): string
    {
        $this->requireRole('super_admin', 'admin');

        return $this->adminView('admin/visions/form', [
            'title'      => 'New Vision',
            'vision'     => null,
            'validation' => session()->getFlashdata('validation'),
        ]);
    }

    public function store(): ResponseInterface
    {
        // ── Authorization check BEFORE any DB work ──────────────────
        $this->requireRole('super_admin', 'admin');

        $rules = [
            'title'       => 'required|max_length[255]',
            'description' => 'permit_empty|max_length[65535]',
            'start_year'  => 'required|is_natural|greater_than_equal_to[2000]|less_than_equal_to[2100]',
            'end_year'    => 'required|is_natural|greater_than_equal_to[{start_year}]|less_than_equal_to[2100]',
            'is_active'   => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        $this->visionModel->insert([
            'title'       => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'start_year'  => (int) $this->request->getPost('start_year'),
            'end_year'    => (int) $this->request->getPost('end_year'),
            'is_active'   => (int) ($this->request->getPost('is_active') ?? 1),
        ]);

        return redirect()->to(site_url('admin/visions'))
            ->with('success', 'Vision created successfully.');
    }

    public function edit(int $id): string
    {
        $this->requireRole('super_admin', 'admin');

        $vision = $this->findOr404($id);

        return $this->adminView('admin/visions/form', [
            'title'      => 'Edit Vision',
            'vision'     => $vision,
            'validation' => session()->getFlashdata('validation'),
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        // ── Authorization check BEFORE any DB work ──────────────────
        $this->requireRole('super_admin', 'admin');

        $vision = $this->findOr404($id);

        $rules = [
            'title'       => 'required|max_length[255]',
            'description' => 'permit_empty|max_length[65535]',
            'start_year'  => 'required|is_natural|greater_than_equal_to[2000]|less_than_equal_to[2100]',
            'end_year'    => 'required|is_natural|greater_than_equal_to[{start_year}]|less_than_equal_to[2100]',
            'is_active'   => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        $this->visionModel->update($id, [
            'title'       => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'start_year'  => (int) $this->request->getPost('start_year'),
            'end_year'    => (int) $this->request->getPost('end_year'),
            'is_active'   => (int) ($this->request->getPost('is_active') ?? $vision['is_active']),
        ]);

        return redirect()->to(site_url('admin/visions'))
            ->with('success', 'Vision updated successfully.');
    }

    private function findOr404(int $id): array
    {
        $vision = $this->visionModel->find($id);

        if ($vision === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Vision #{$id} not found.");
        }

        return $vision;
    }
}
