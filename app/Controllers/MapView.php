<?php

namespace App\Controllers;

use App\Models\ProjectModel;
use App\Services\ProjectFilterService;

/**
 * Public-facing project map.
 *
 * GET /map          → full map page
 * GET /map/markers  → JSON feed of geo-tagged published/completed projects
 */
class MapView extends BaseController
{
    private const PUBLIC_STATUSES = ['published', 'completed'];

    private ProjectModel        $projectModel;
    private ProjectFilterService $filterService;

    public function __construct()
    {
        $this->projectModel  = new ProjectModel();
        $this->filterService = new ProjectFilterService();
    }

    // ──────────────────────────────────────────────────────────────────────────
    // Map page
    // ──────────────────────────────────────────────────────────────────────────

    public function index(): string
    {
        // Filter options for the sidebar dropdowns
        $offices = $this->projectModel
            ->db
            ->table('offices')
            ->join('projects', 'projects.office_id = offices.id')
            ->whereIn('projects.status', self::PUBLIC_STATUSES)
            ->whereNotNull('projects.latitude')
            ->whereNotNull('projects.longitude')
            ->select('offices.id, offices.name')
            ->distinct()
            ->orderBy('offices.name', 'ASC')
            ->get()
            ->getResultArray();

        $fiscalYears = $this->projectModel
            ->builder()
            ->select('fiscal_year')
            ->distinct()
            ->whereIn('status', self::PUBLIC_STATUSES)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderBy('fiscal_year', 'DESC')
            ->get()
            ->getResultArray();

        return view('map/index', [
            'title'          => 'Project Map — Kabankalan Budget Portal',
            'metaDescription' => 'Visualize all AIP projects on an interactive map of Kabankalan City. Browse by office or fiscal year.',
            'offices'        => $offices,
            'fiscal_years'   => array_column($fiscalYears, 'fiscal_year'),
        ]);
    }

    // ──────────────────────────────────────────────────────────────────────────
    // JSON markers feed (consumed by Leaflet via fetch)
    // ──────────────────────────────────────────────────────────────────────────

    public function markers()
    {
        $officeId   = (int)  ($this->request->getGet('office_id') ?? 0);
        $fiscalYear = (int)  ($this->request->getGet('fiscal_year') ?? 0);
        $status     = trim((string) ($this->request->getGet('status') ?? ''));

        $model = $this->projectModel
            ->withRelations()
            ->whereNotNull('projects.latitude')
            ->whereNotNull('projects.longitude')
            ->whereIn('projects.status', self::PUBLIC_STATUSES);

        if ($officeId > 0) {
            $model->where('projects.office_id', $officeId);
        }

        if ($fiscalYear > 0) {
            $model->where('projects.fiscal_year', $fiscalYear);
        }

        if ($status !== '' && in_array($status, self::PUBLIC_STATUSES, true)) {
            $model->where('projects.status', $status);
        }

        $projects = $model
            ->orderBy('projects.title', 'ASC')
            ->findAll(500); // hard cap to protect the endpoint

        $features = array_map(function (array $p): array {
            return [
                'type' => 'Feature',
                'geometry' => [
                    'type'        => 'Point',
                    'coordinates' => [(float)$p['longitude'], (float)$p['latitude']],
                ],
                'properties' => [
                    'id'               => $p['id'],
                    'title'            => $p['title'],
                    'project_code'     => $p['project_code'],
                    'status'           => $p['status'],
                    'fiscal_year'      => $p['fiscal_year'],
                    'barangay'         => $p['barangay'] ?? '',
                    'office_name'      => $p['office_name'] ?? '',
                    'allocated_amount' => (float)$p['allocated_amount'],
                    'url'              => site_url('projects/' . $p['id']),
                ],
            ];
        }, $projects);

        return $this->response
            ->setContentType('application/json')
            ->setJSON([
                'type'     => 'FeatureCollection',
                'features' => $features,
            ]);
    }
}
