<?php

namespace App\Controllers\Admin;

use App\Models\OfficeModel;
use App\Models\ProjectModel;
use App\Services\BudgetSummaryService;
use App\Services\ProjectFilterService;

/**
 * Reports Controller — Admin reporting and CSV exports.
 *
 * Security contract:
 *  - AdminAuthFilter guarantees a valid session.
 *  - Role-scoping is strictly enforced: office_staff only see and export reports for projects
 *    belonging to their own office.
 */
class Reports extends AdminBaseController
{
    private BudgetSummaryService $budgetSummary;
    private ProjectModel $projectModel;
    private OfficeModel $officeModel;

    public function __construct()
    {
        parent::__construct();
        $this->budgetSummary = new BudgetSummaryService();
        $this->projectModel  = new ProjectModel();
        $this->officeModel   = new OfficeModel();
    }

    public function index(): string
    {
        $user       = $this->getCurrentUser();
        $rawFilters = $this->request->getGet() ?? [];

        $filters = [];
        if (isset($rawFilters['fiscal_year']) && $rawFilters['fiscal_year'] !== '') {
            $filters['fiscal_year'] = (int) $rawFilters['fiscal_year'];
        }
        if (isset($rawFilters['status']) && $rawFilters['status'] !== '') {
            $filters['status'] = $rawFilters['status'];
        }

        // Enforce office scope
        if ($user['role'] === 'office_staff') {
            $filters['office_id'] = (int) $user['office_id'];
        } elseif (isset($rawFilters['office_id']) && $rawFilters['office_id'] !== '') {
            $filters['office_id'] = (int) $rawFilters['office_id'];
        }

        // Fetch admin metrics (publicOnly = false)
        $totals         = $this->budgetSummary->getOverallTotals($filters, false);
        $totalsByOffice = $this->budgetSummary->getTotalsByOffice($filters, false);
        $countsByStatus = $this->budgetSummary->getCountsByStatus($filters, false);
        $totalsByYear   = $this->budgetSummary->getTotalsByYear($filters, false);

        // Filter options for dropdowns
        $fiscalYears = $this->projectModel->builder()
            ->select('fiscal_year')
            ->distinct()
            ->orderBy('fiscal_year', 'DESC')
            ->get()
            ->getResultArray();
        $fiscalYears = array_map(static fn ($r) => (int) $r['fiscal_year'], $fiscalYears);

        if ($user['role'] === 'office_staff') {
            $offices = $this->officeModel->where('id', $user['office_id'])->findAll();
        } else {
            $offices = $this->officeModel->orderBy('name', 'ASC')->findAll();
        }

        return $this->adminView('admin/reports/index', [
            'title'          => 'System Reports',
            'totals'         => $totals,
            'totalsByOffice' => $totalsByOffice,
            'countsByStatus' => $countsByStatus,
            'totalsByYear'   => $totalsByYear,
            'filters'        => $filters,
            'offices'        => $offices,
            'fiscalYears'    => $fiscalYears,
            'statuses'       => ProjectModel::STATUSES,
        ]);
    }

    public function export()
    {
        set_time_limit(120);

        $user       = $this->getCurrentUser();
        $rawFilters = $this->request->getGet() ?? [];

        $filters = [];
        if (isset($rawFilters['fiscal_year']) && $rawFilters['fiscal_year'] !== '') {
            $filters['fiscal_year'] = (int) $rawFilters['fiscal_year'];
        }
        if (isset($rawFilters['status']) && $rawFilters['status'] !== '') {
            $filters['status'] = $rawFilters['status'];
        }

        // Enforce office scope
        if ($user['role'] === 'office_staff') {
            $filters['office_id'] = (int) $user['office_id'];
        } elseif (isset($rawFilters['office_id']) && $rawFilters['office_id'] !== '') {
            $filters['office_id'] = (int) $rawFilters['office_id'];
        }

        // Fetch projects
        $filterService = new ProjectFilterService();
        $model         = $this->projectModel->withRelations();
        $model         = $filterService->applyTo($model, $filters);

        $projects = $model
            ->orderBy($this->projectModel->table . '.id', 'DESC')
            ->findAll();

        $filename = 'admin_reports_export_' . date('Ymd_His') . '.csv';

        $response = $this->response;
        $response->setHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Expires', '0');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, [
            'Project ID',
            'Project Code',
            'Title',
            'Description',
            'Barangay',
            'Office Name',
            'Vision Title',
            'Fiscal Year',
            'Allocated Amount',
            'Obligated Amount',
            'Disbursed Amount',
            'Status',
            'Created At',
            'Updated At'
        ]);

        foreach ($projects as $p) {
            fputcsv($output, [
                $p['id'],
                $p['project_code'],
                $p['title'],
                $p['description'] ?? '',
                $p['barangay'] ?? '',
                $p['office_name'] ?? '',
                $p['vision_title'] ?? '',
                $p['fiscal_year'],
                $p['allocated_amount'],
                $p['obligated_amount'] ?? '0.00',
                $p['disbursed_amount'] ?? '0.00',
                $p['status'],
                $p['created_at'],
                $p['updated_at']
            ]);
        }

        fclose($output);

        return $response;
    }
}
