<?php

namespace App\Controllers\Admin;

use App\Models\OfficeModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * OfficeManager — CRUD for government offices.
 *
 * Allowed roles: super_admin ONLY.
 * Offices are the ownership anchor for projects — only the highest-privilege
 * role may create, rename, or deactivate them.
 */
class OfficeManager extends AdminBaseController
{
    private OfficeModel $officeModel;

    public function __construct()
    {
        parent::__construct();
        $this->officeModel = new OfficeModel();
    }

    public function index(): string
    {
        $this->requireRole('super_admin');

        $offices = $this->officeModel->orderBy('name')->findAll();

        return $this->adminView('admin/offices/index', [
            'title'   => 'Manage Offices',
            'offices' => $offices,
        ]);
    }

    public function new(): string
    {
        $this->requireRole('super_admin');

        return $this->adminView('admin/offices/form', [
            'title'      => 'New Office',
            'office'     => null,
            'validation' => session()->getFlashdata('validation'),
        ]);
    }

    public function store(): ResponseInterface
    {
        // ── Authorization check BEFORE any DB work ──────────────────
        $this->requireRole('super_admin');

        $rules = [
            'code'          => 'required|alpha_numeric_punct|max_length[20]|is_unique[offices.code]',
            'name'          => 'required|max_length[150]',
            'description'   => 'permit_empty|max_length[65535]',
            'contact_email' => 'permit_empty|valid_email|max_length[255]',
            'is_active'     => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        $this->officeModel->insert([
            'code'          => strtoupper($this->request->getPost('code')),
            'name'          => $this->request->getPost('name'),
            'description'   => $this->request->getPost('description'),
            'contact_email' => $this->request->getPost('contact_email'),
            'is_active'     => (int) ($this->request->getPost('is_active') ?? 1),
        ]);

        return redirect()->to(site_url('admin/offices'))
            ->with('success', 'Office created successfully.');
    }

    public function edit(int $id): string
    {
        $this->requireRole('super_admin');

        $office = $this->findOr404($id);

        return $this->adminView('admin/offices/form', [
            'title'      => 'Edit Office',
            'office'     => $office,
            'validation' => session()->getFlashdata('validation'),
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        // ── Authorization check BEFORE any DB work ──────────────────
        $this->requireRole('super_admin');

        $office = $this->findOr404($id);

        $rules = [
            'code'          => "required|alpha_numeric_punct|max_length[20]|is_unique[offices.code,id,{$id}]",
            'name'          => 'required|max_length[150]',
            'description'   => 'permit_empty|max_length[65535]',
            'contact_email' => 'permit_empty|valid_email|max_length[255]',
            'is_active'     => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        $this->officeModel->update($id, [
            'code'          => strtoupper($this->request->getPost('code')),
            'name'          => $this->request->getPost('name'),
            'description'   => $this->request->getPost('description'),
            'contact_email' => $this->request->getPost('contact_email'),
            'is_active'     => (int) ($this->request->getPost('is_active') ?? $office['is_active']),
        ]);

        return redirect()->to(site_url('admin/offices'))
            ->with('success', 'Office updated successfully.');
    }

    private function findOr404(int $id): array
    {
        $office = $this->officeModel->find($id);

        if ($office === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Office #{$id} not found.");
        }

        return $office;
    }
}
