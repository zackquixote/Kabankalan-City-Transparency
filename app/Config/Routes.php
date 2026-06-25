<?php

use CodeIgniter\Router\RouteCollection;

/** @var RouteCollection $routes */

// ── Public routes ───────────────────────────────────────────────────
$routes->get('/', 'Home::index');
$routes->get('visions', 'Visions::index');
$routes->get('aip', 'AipRegistry::index');
$routes->get('aip/export', 'AipRegistry::export');
$routes->get('transparency', 'Transparency::index');
$routes->get('map', 'MapView::index');
$routes->get('map/markers', 'MapView::markers');
$routes->get('projects/(:num)', 'ProjectDetail::show/$1');
$routes->get('feedback/(:num)', 'FeedbackSubmit::form/$1');
$routes->post('feedback/(:num)', 'FeedbackSubmit::store/$1', ['filter' => ['csrf', 'honeypot']]);
$routes->get('feedback/success/(:num)', 'FeedbackSubmit::success/$1');

// Public attachment download (permission-gated by controller, no direct disk URL)
$routes->get('attachments/(:num)', 'ProjectAttachment::download/$1');

// ── Chatbot routes ───────────────────────────────────────────────────
$routes->get('chatbot',        'Chatbot::index');
$routes->post('chatbot/send',  'Chatbot::send');
$routes->post('chatbot/clear', 'Chatbot::clearHistory');

// ── Auth routes ──────────────────────────────────────────────────────
$routes->get('auth/login',         'Auth::login');
$routes->post('auth/authenticate', 'Auth::authenticate', ['filter' => 'csrf']);
$routes->get('auth/logout',        'Auth::logout');

// ── Admin routes ─────────────────────────────────────────────────────
//
// AdminAuthFilter in Config/Filters.php applies to admin/* automatically
// (deny-by-default). No individual route needs a ['filter' => 'admin_auth']
// annotation — adding a new admin route here is enough.
//
$routes->group('admin', static function (RouteCollection $routes): void {

    // Dashboard — root /admin redirect + dashboard page
    $routes->get('',          'Admin\Dashboard::index');   // /admin → dashboard
    $routes->get('dashboard', 'Admin\Dashboard::index');

    // ── Projects ─────────────────────────────────────────────────────
    $routes->get('projects',                  'Admin\ProjectManager::index');
    $routes->get('projects/new',              'Admin\ProjectManager::new');
    $routes->post('projects',                 'Admin\ProjectManager::store');
    $routes->get('projects/(:num)',           'Admin\ProjectManager::edit/$1');
    $routes->post('projects/(:num)',          'Admin\ProjectManager::update/$1');
    $routes->post('projects/(:num)/delete',   'Admin\ProjectManager::delete/$1');
    $routes->post('projects/(:num)/submit',   'Admin\ProjectManager::submit/$1');
    $routes->post('projects/(:num)/publish',  'Admin\ProjectManager::publish/$1');

    // ── Attachments ───────────────────────────────────────────────────
    $routes->post('projects/(:num)/attachments',            'Admin\AttachmentManager::upload/$1');
    $routes->post('projects/(:num)/attachments/(:num)/delete', 'Admin\AttachmentManager::delete/$1/$2');

    // ── Visions ──────────────────────────────────────────────────────
    $routes->get('visions',          'Admin\VisionManager::index');
    $routes->get('visions/new',      'Admin\VisionManager::new');
    $routes->post('visions',         'Admin\VisionManager::store');
    $routes->get('visions/(:num)',   'Admin\VisionManager::edit/$1');
    $routes->post('visions/(:num)',  'Admin\VisionManager::update/$1');

    // ── Offices ───────────────────────────────────────────────────────
    $routes->get('offices',          'Admin\OfficeManager::index');
    $routes->get('offices/new',      'Admin\OfficeManager::new');
    $routes->post('offices',         'Admin\OfficeManager::store');
    $routes->get('offices/(:num)',   'Admin\OfficeManager::edit/$1');
    $routes->post('offices/(:num)',  'Admin\OfficeManager::update/$1');

    // ── Budget Cycle Stages ───────────────────────────────────────────
    $routes->get('cycle-stages',          'Admin\CycleStageManager::index');
    $routes->get('cycle-stages/new',      'Admin\CycleStageManager::new');
    $routes->post('cycle-stages',         'Admin\CycleStageManager::store');
    $routes->get('cycle-stages/(:num)',   'Admin\CycleStageManager::edit/$1');
    $routes->post('cycle-stages/(:num)',  'Admin\CycleStageManager::update/$1');

    // ── Feedback moderation ───────────────────────────────────────────
    $routes->get('feedback',                   'Admin\FeedbackModerator::index');
    $routes->get('feedback/(:num)',            'Admin\FeedbackModerator::show/$1');
    $routes->post('feedback/(:num)/respond',   'Admin\FeedbackModerator::respond/$1');
    $routes->post('feedback/(:num)/dismiss',   'Admin\FeedbackModerator::dismiss/$1');

    // ── Reports ──────────────────────────────────────────────────────
    $routes->get('reports',                    'Admin\Reports::index');
    $routes->get('reports/export',             'Admin\Reports::export');
});
