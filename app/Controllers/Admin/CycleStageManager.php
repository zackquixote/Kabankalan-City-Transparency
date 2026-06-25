<?php

namespace App\Controllers\Admin;

use App\Models\BudgetCycleStageModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * CycleStageManager — CRUD for budget cycle stages.
 *
 * Allowed roles: super_admin, admin.
 * Cycle stages are a global reference table, not office-scoped.
 */
class CycleStageManager extends AdminBaseController
{
    private BudgetCycleStageModel $stageModel;

    public function __construct()
    {
        parent::__construct();
        $this->stageModel = new BudgetCycleStageModel();
    }

    public function index(): string
    {
        $this->requireRole('super_admin', 'admin');

        $stages = $this->stageModel->orderBy('sort_order')->findAll();

        return $this->adminView('admin/cycle_stages/index', [
            'title'  => 'Budget Cycle Stages',
            'stages' => $stages,
        ]);
    }

    public function new(): string
    {
        $this->requireRole('super_admin', 'admin');

        return $this->adminView('admin/cycle_stages/form', [
            'title'      => 'New Cycle Stage',
            'stage'      => null,
            'validation' => session()->getFlashdata('validation'),
        ]);
    }

    public function store(): ResponseInterface
    {
        // ── Authorization check BEFORE any DB work ──────────────────
        $this->requireRole('super_admin', 'admin');

        $rules = [
            'slug'        => 'required|alpha_dash|max_length[50]|is_unique[budget_cycle_stages.slug]',
            'name'        => 'required|max_length[100]',
            'description' => 'permit_empty|max_length[65535]',
            'sort_order'  => 'permit_empty|is_natural|less_than_equal_to[255]',
            'is_active'   => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        $this->stageModel->insert([
            'slug'        => $this->request->getPost('slug'),
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'sort_order'  => (int) ($this->request->getPost('sort_order') ?? 0),
            'is_active'   => (int) ($this->request->getPost('is_active') ?? 1),
        ]);

        return redirect()->to(site_url('admin/cycle-stages'))
            ->with('success', 'Cycle stage created successfully.');
    }

    public function edit(int $id): string
    {
        $this->requireRole('super_admin', 'admin');

        $stage = $this->findOr404($id);

        return $this->adminView('admin/cycle_stages/form', [
            'title'      => 'Edit Cycle Stage',
            'stage'      => $stage,
            'validation' => session()->getFlashdata('validation'),
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        // ── Authorization check BEFORE any DB work ──────────────────
        $this->requireRole('super_admin', 'admin');

        $stage = $this->findOr404($id);

        $rules = [
            'slug'        => "required|alpha_dash|max_length[50]|is_unique[budget_cycle_stages.slug,id,{$id}]",
            'name'        => 'required|max_length[100]',
            'description' => 'permit_empty|max_length[65535]',
            'sort_order'  => 'permit_empty|is_natural|less_than_equal_to[255]',
            'is_active'   => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        $this->stageModel->update($id, [
            'slug'        => $this->request->getPost('slug'),
            'name'        => $this->request->getPost('name'),
            'description' => $this->request->getPost('description'),
            'sort_order'  => (int) ($this->request->getPost('sort_order') ?? $stage['sort_order']),
            'is_active'   => (int) ($this->request->getPost('is_active') ?? $stage['is_active']),
        ]);

        return redirect()->to(site_url('admin/cycle-stages'))
            ->with('success', 'Cycle stage updated successfully.');
    }

    private function findOr404(int $id): array
    {
        $stage = $this->stageModel->find($id);

        if ($stage === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Cycle stage #{$id} not found.");
        }

        return $stage;
    }
}
