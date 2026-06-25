<?php

namespace App\Controllers\Admin;

use App\Models\BudgetCycleStageModel;
use App\Models\OfficeModel;
use App\Models\ProjectAttachmentModel;
use App\Models\ProjectModel;
use App\Models\VisionModel;
use App\Services\AuthorizationService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * ProjectManager — CRUD + workflow actions for projects.
 *
 * Security contract:
 *  - AdminAuthFilter guarantees a valid session before any method runs.
 *  - Every state-changing method (store, update, delete, submit, publish)
 *    calls AuthorizationService::can() with a DB-reloaded user BEFORE
 *    touching the database.  The check is NOT limited to form rendering
 *    so direct POST attacks are blocked at the endpoint itself.
 *  - office_staff users can only access projects belonging to their office.
 */
class ProjectManager extends AdminBaseController
{
    private ProjectModel $projectModel;
    private OfficeModel $officeModel;
    private VisionModel $visionModel;
    private BudgetCycleStageModel $stageModel;
    private ProjectAttachmentModel $attachmentModel;

    public function __construct()
    {
        parent::__construct();
        $this->projectModel    = new ProjectModel();
        $this->officeModel     = new OfficeModel();
        $this->visionModel     = new VisionModel();
        $this->stageModel      = new BudgetCycleStageModel();
        $this->attachmentModel = new ProjectAttachmentModel();
    }

    // ──────────────────────────────────────────────────────────────────
    // List
    // ──────────────────────────────────────────────────────────────────

    public function index(): string
    {
        $user = $this->getCurrentUser();

        $builder = $this->projectModel->withRelations()->orderBy('projects.id', 'DESC');

        // office_staff only see their own office's projects
        if ($user['role'] === 'office_staff') {
            $builder->where('projects.office_id', $user['office_id']);
        }

        $projects = $builder->paginate(20);

        return $this->adminView('admin/projects/index', [
            'title'    => 'Manage Projects',
            'projects' => $projects,
            'pager'    => $this->projectModel->pager,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Create
    // ──────────────────────────────────────────────────────────────────

    public function new(): string
    {
        $user = $this->getFullUser();

        // Show form pre-scoped to the user's office (office_staff) or let them pick
        $officeId = ($user['role'] === 'office_staff') ? (int) $user['office_id'] : null;

        // Quick gate: can the user create a project at all?
        $this->denyUnless(
            $this->authz()->can($user, AuthorizationService::ACTION_CREATE, [
                'office_id' => $officeId ?? 1,  // synthetic check — full validation in store()
            ])
        );

        return $this->adminView('admin/projects/form', [
            'title'       => 'New Project',
            'project'     => null,
            'offices'     => $this->officeModel->where('is_active', 1)->orderBy('name')->findAll(),
            'visions'     => $this->visionModel->where('is_active', 1)->orderBy('title')->findAll(),
            'stages'      => $this->stageModel->where('is_active', 1)->orderBy('sort_order')->findAll(),
            'lockedOffice'=> ($user['role'] === 'office_staff') ? $officeId : null,
            'validation'  => session()->getFlashdata('validation'),
        ]);
    }

    public function store(): ResponseInterface
    {
        $user     = $this->getFullUser();
        $officeId = (int) $this->request->getPost('office_id');

        // ── Authorization check BEFORE any DB work ──────────────────
        $this->denyUnless(
            $this->authz()->can($user, AuthorizationService::ACTION_CREATE, ['office_id' => $officeId])
        );

        $rules = $this->buildValidationRules();

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        $data = $this->collectFormData();
        $data['created_by'] = $user['id'];
        $data['status']     = 'draft';

        $this->projectModel->insert($data);

        return redirect()->to(site_url('admin/projects'))
            ->with('success', 'Project created successfully.');
    }

    // ──────────────────────────────────────────────────────────────────
    // Update
    // ──────────────────────────────────────────────────────────────────

    public function edit(int $id): string
    {
        $project = $this->findProjectOr404($id);
        $user    = $this->getFullUser();

        $this->denyUnless(
            $this->authz()->can($user, AuthorizationService::ACTION_UPDATE, $project)
        );

        return $this->adminView('admin/projects/form', [
            'title'       => 'Edit Project',
            'project'     => $project,
            'offices'     => $this->officeModel->where('is_active', 1)->orderBy('name')->findAll(),
            'visions'     => $this->visionModel->where('is_active', 1)->orderBy('title')->findAll(),
            'stages'      => $this->stageModel->where('is_active', 1)->orderBy('sort_order')->findAll(),
            'lockedOffice'=> ($user['role'] === 'office_staff') ? (int) $user['office_id'] : null,
            'validation'  => session()->getFlashdata('validation'),
            'attachments' => $this->attachmentModel->forProject($id),
        ]);
    }

    public function update(int $id): ResponseInterface
    {
        $project  = $this->findProjectOr404($id);
        $user     = $this->getFullUser();

        // ── Authorization check BEFORE any DB work ──────────────────
        $this->denyUnless(
            $this->authz()->can($user, AuthorizationService::ACTION_UPDATE, $project)
        );

        $rules = $this->buildValidationRules($id);

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        $data = $this->collectFormData();

        $this->projectModel->update($id, $data);

        return redirect()->to(site_url('admin/projects'))
            ->with('success', 'Project updated successfully.');
    }

    // ──────────────────────────────────────────────────────────────────
    // Delete
    // ──────────────────────────────────────────────────────────────────

    public function delete(int $id): ResponseInterface
    {
        $project = $this->findProjectOr404($id);
        $user    = $this->getFullUser();

        // ── Authorization check BEFORE any DB work ──────────────────
        // can() for DELETE on office_staff requires status === 'draft'
        $this->denyUnless(
            $this->authz()->can($user, AuthorizationService::ACTION_DELETE, $project)
        );

        $this->projectModel->delete($id);

        return redirect()->to(site_url('admin/projects'))
            ->with('success', 'Project deleted.');
    }

    // ──────────────────────────────────────────────────────────────────
    // Workflow transitions
    // ──────────────────────────────────────────────────────────────────

    /** office_staff / admin submit a project for review */
    public function submit(int $id): ResponseInterface
    {
        $project = $this->findProjectOr404($id);
        $user    = $this->getFullUser();

        // ── Authorization check BEFORE any DB work ──────────────────
        $this->denyUnless(
            $this->authz()->can($user, AuthorizationService::ACTION_SUBMIT, $project)
        );

        $this->projectModel->skipValidation(true)->update($id, ['status' => 'submitted']);

        return redirect()->to(site_url('admin/projects'))
            ->with('success', 'Project submitted for review.');
    }

    /** admin / super_admin publish a project */
    public function publish(int $id): ResponseInterface
    {
        $project = $this->findProjectOr404($id);
        $user    = $this->getFullUser();

        // ── Authorization check BEFORE any DB work ──────────────────
        $this->denyUnless(
            $this->authz()->can($user, AuthorizationService::ACTION_PUBLISH, $project)
        );

        $this->projectModel->skipValidation(true)->update($id, [
            'status'       => 'published',
            'published_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('admin/projects'))
            ->with('success', 'Project published.');
    }

    // ──────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────

    private function findProjectOr404(int $id): array
    {
        $project = $this->projectModel->find($id);

        if ($project === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Project #{$id} not found.");
        }

        return $project;
    }

    private function collectFormData(): array
    {
        return [
            'vision_id'              => (int) $this->request->getPost('vision_id'),
            'office_id'              => (int) $this->request->getPost('office_id'),
            'budget_cycle_stage_id'  => (int) $this->request->getPost('budget_cycle_stage_id'),
            'project_code'           => $this->request->getPost('project_code'),
            'title'                  => $this->request->getPost('title'),
            'description'            => $this->request->getPost('description'),
            'fiscal_year'            => (int) $this->request->getPost('fiscal_year'),
            'barangay'               => $this->request->getPost('barangay'),
            'latitude'               => $this->request->getPost('latitude') !== '' ? (float) $this->request->getPost('latitude') : null,
            'longitude'              => $this->request->getPost('longitude') !== '' ? (float) $this->request->getPost('longitude') : null,
            'allocated_amount'       => $this->request->getPost('allocated_amount'),
            'obligated_amount'       => $this->request->getPost('obligated_amount') ?: null,
            'disbursed_amount'       => $this->request->getPost('disbursed_amount') ?: null,
            'target_completion_date' => $this->request->getPost('target_completion_date') ?: null,
        ];
    }

    private function buildValidationRules(?int $id = null): array
    {
        $uniqueSuffix = $id ? ",id,{$id}" : '';

        return [
            'vision_id'              => 'required|is_natural_no_zero|is_not_unique[visions.id]',
            'office_id'              => 'required|is_natural_no_zero|is_not_unique[offices.id]',
            'budget_cycle_stage_id'  => 'required|is_natural_no_zero|is_not_unique[budget_cycle_stages.id]',
            'project_code'           => "required|alpha_numeric_punct|max_length[30]|is_unique[projects.project_code,id{$uniqueSuffix}]",
            'title'                  => 'required|max_length[255]',
            'description'            => 'permit_empty|max_length[65535]',
            'fiscal_year'            => 'required|is_natural|greater_than_equal_to[2000]|less_than_equal_to[2100]',
            'barangay'               => 'permit_empty|max_length[100]',
            'latitude'               => 'permit_empty|decimal|greater_than_equal_to[-90]|less_than_equal_to[90]',
            'longitude'              => 'permit_empty|decimal|greater_than_equal_to[-180]|less_than_equal_to[180]',
            'allocated_amount'       => 'required|decimal|greater_than_equal_to[0]',
            'obligated_amount'       => 'permit_empty|decimal|greater_than_equal_to[0]',
            'disbursed_amount'       => 'permit_empty|decimal|greater_than_equal_to[0]',
            'target_completion_date' => 'permit_empty|valid_date[Y-m-d]',
        ];
    }
}
