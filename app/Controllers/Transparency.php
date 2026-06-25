<?php

namespace App\Controllers;

use App\Services\AipRegistryService;
use App\Services\BudgetSummaryService;

class Transparency extends BaseController
{
    private BudgetSummaryService $budgetSummary;
    private AipRegistryService $aipRegistry;

    public function __construct()
    {
        $this->budgetSummary = new BudgetSummaryService();
        $this->aipRegistry   = new AipRegistryService();
    }

    public function index(): string
    {
        $rawFilters = $this->request->getGet() ?? [];
        $filterOptions = $this->aipRegistry->getFilterOptions();

        $filters = [];
        if (isset($rawFilters['fiscal_year']) && $rawFilters['fiscal_year'] !== '') {
            $filters['fiscal_year'] = (int) $rawFilters['fiscal_year'];
        }
        if (isset($rawFilters['office_id']) && $rawFilters['office_id'] !== '') {
            $filters['office_id'] = (int) $rawFilters['office_id'];
        }

        // Fetch public metrics (publicOnly = true)
        $totals         = $this->budgetSummary->getOverallTotals($filters, true);
        $totalsByOffice = $this->budgetSummary->getTotalsByOffice($filters, true);
        $countsByStatus = $this->budgetSummary->getCountsByStatus($filters, true);
        $totalsByYear   = $this->budgetSummary->getTotalsByYear($filters, true);

        return view('transparency/index', [
            'totals'         => $totals,
            'totalsByOffice' => $totalsByOffice,
            'countsByStatus' => $countsByStatus,
            'totalsByYear'   => $totalsByYear,
            'filters'        => $filters,
            'filter_options' => $filterOptions,
        ]);
    }
}
