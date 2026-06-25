<?php

namespace App\Controllers;

use App\Models\ProjectModel;
use App\Services\ProjectFilterService;

class AipRegistry extends BaseController
{
    public function index(): string
    {
        $page = (int) ($this->request->getGet('page') ?? 1);

        $data = service('aipRegistry')->search(
            $this->request->getGet() ?? [],
            $page,
        );

        return view('aip_registry/index', $data);
    }

    public function export()
    {
        set_time_limit(120);

        $rawFilters = $this->request->getGet() ?? [];
        $filterService = new ProjectFilterService();
        $filters = $filterService->sanitize($rawFilters);

        if (isset($filters['status']) && ! in_array($filters['status'], ['published', 'completed'], true)) {
            unset($filters['status']);
        }

        $projectModel = new ProjectModel();
        $model = $projectModel->withRelations();

        $model = $filterService->applyTo($model, $filters);

        if (! isset($filters['status'])) {
            $model->whereIn($projectModel->table . '.status', ['published', 'completed']);
        }

        $projects = $model
            ->orderBy($projectModel->table . '.published_at', 'DESC')
            ->orderBy($projectModel->table . '.title', 'ASC')
            ->findAll();

        $filename = 'aip_registry_export_' . date('Ymd_His') . '.csv';

        $response = $this->response;
        $response->setHeader('Content-Type', 'text/csv; charset=utf-8');
        $response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $response->setHeader('Pragma', 'no-cache');
        $response->setHeader('Expires', '0');

        $output = fopen('php://output', 'w');
        // UTF-8 BOM
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, [
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
            'Published Date'
        ]);

        foreach ($projects as $p) {
            fputcsv($output, [
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
                $p['published_at'] ?? ''
            ]);
        }

        fclose($output);

        return $response;
    }
}

