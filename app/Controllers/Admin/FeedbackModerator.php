<?php

namespace App\Controllers\Admin;

use App\Models\FeedbackModel;
use App\Models\ProjectModel;
use App\Services\AuthorizationService;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * FeedbackModerator — review and respond to citizen feedback.
 *
 * Security contract:
 *  - Loads the parent project of each feedback item and calls
 *    AuthorizationService::can($user, 'moderate_feedback', $project)
 *    before any write, so office_staff can only moderate feedback on
 *    their own office's projects.
 *  - Response and dismiss actions both hit the auth check at the
 *    POST endpoint — not only when rendering the form.
 */
class FeedbackModerator extends AdminBaseController
{
    private FeedbackModel $feedbackModel;
    private ProjectModel  $projectModel;

    public function __construct()
    {
        parent::__construct();
        $this->feedbackModel = new FeedbackModel();
        $this->projectModel  = new ProjectModel();
    }

    // ──────────────────────────────────────────────────────────────────
    // Moderation queue
    // ──────────────────────────────────────────────────────────────────

    public function index(): string
    {
        $user = $this->getCurrentUser();

        $builder = $this->feedbackModel
            ->select('feedback.*, projects.title AS project_title, projects.office_id, projects.id AS project_id')
            ->join('projects', 'projects.id = feedback.project_id', 'left')
            ->orderBy('feedback.created_at', 'DESC');

        // office_staff see only their office's feedback
        if ($user['role'] === 'office_staff') {
            $builder->where('projects.office_id', $user['office_id']);
        }

        $feedback = $builder->paginate(25);

        return $this->adminView('admin/feedback/index', [
            'title'    => 'Feedback Moderation',
            'feedback' => $feedback,
            'pager'    => $this->feedbackModel->pager,
            'statuses' => FeedbackModel::STATUSES,
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Show single feedback item + respond form
    // ──────────────────────────────────────────────────────────────────

    public function show(int $id): string
    {
        $item    = $this->findFeedbackOr404($id);
        $project = $this->findProjectOr404((int) $item['project_id']);
        $user    = $this->getFullUser();

        $this->denyUnless(
            $this->authz()->can($user, AuthorizationService::ACTION_MODERATE_FEEDBACK, $project)
        );

        return $this->adminView('admin/feedback/show', [
            'title'      => 'Review Feedback',
            'item'       => $item,
            'project'    => $project,
            'validation' => session()->getFlashdata('validation'),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────
    // Respond
    // ──────────────────────────────────────────────────────────────────

    public function respond(int $id): ResponseInterface
    {
        $item    = $this->findFeedbackOr404($id);
        $project = $this->findProjectOr404((int) $item['project_id']);
        $user    = $this->getFullUser();

        // ── Authorization check BEFORE any DB work ──────────────────
        $this->denyUnless(
            $this->authz()->can($user, AuthorizationService::ACTION_MODERATE_FEEDBACK, $project)
        );

        $rules = [
            'admin_response' => 'required|min_length[5]|max_length[65535]',
            'status'         => 'required|in_list[reviewed,addressed]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()
                ->with('validation', $this->validator->getErrors());
        }

        $this->feedbackModel->skipValidation(true)->update($id, [
            'admin_response' => $this->request->getPost('admin_response'),
            'status'         => $this->request->getPost('status'),
            'responded_by'   => $user['id'],
            'responded_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('admin/feedback'))
            ->with('success', 'Response saved.');
    }

    // ──────────────────────────────────────────────────────────────────
    // Dismiss (mark as dismissed without a response)
    // ──────────────────────────────────────────────────────────────────

    public function dismiss(int $id): ResponseInterface
    {
        $item    = $this->findFeedbackOr404($id);
        $project = $this->findProjectOr404((int) $item['project_id']);
        $user    = $this->getFullUser();

        // ── Authorization check BEFORE any DB work ──────────────────
        $this->denyUnless(
            $this->authz()->can($user, AuthorizationService::ACTION_MODERATE_FEEDBACK, $project)
        );

        $this->feedbackModel->skipValidation(true)->update($id, [
            'status'       => 'dismissed',
            'responded_by' => $user['id'],
            'responded_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('admin/feedback'))
            ->with('success', 'Feedback dismissed.');
    }

    // ──────────────────────────────────────────────────────────────────
    // Private helpers
    // ──────────────────────────────────────────────────────────────────

    private function findFeedbackOr404(int $id): array
    {
        $item = $this->feedbackModel->find($id);

        if ($item === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Feedback #{$id} not found.");
        }

        return $item;
    }

    private function findProjectOr404(int $id): array
    {
        $project = $this->projectModel->find($id);

        if ($project === null) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException("Project #{$id} not found.");
        }

        return $project;
    }
}
