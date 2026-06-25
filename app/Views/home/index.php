<?php

/**
 * @var int   $active_vision_count
 * @var int   $public_project_count
 * @var array $fiscal_year_summary
 * @var array $recent_projects
 */
?>
<?php ob_start(); ?>
<section class="hero-kb py-5">
    <div class="container py-4">
        <h1 class="display-5 fw-bold mb-3">Kabankalan City Budget Transparency</h1>
        <p class="lead mb-4 col-lg-8">Browse approved programs, track allocations, and share feedback — no account needed.</p>
        <div class="d-flex flex-wrap gap-2">
            <a class="btn btn-light btn-lg" href="<?= site_url('aip') ?>">Browse AIP Registry</a>
            <a class="btn btn-outline-light btn-lg" href="<?= site_url('visions') ?>">View City Visions</a>
        </div>
    </div>
</section>

<div class="container py-5">
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="card stat-card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase">Active Visions</h2>
                    <p class="display-6 fw-bold mb-0"><?= esc((string) $active_vision_count) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase">Public Projects</h2>
                    <p class="display-6 fw-bold mb-0"><?= esc((string) $public_project_count) ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card stat-card h-100 shadow-sm">
                <div class="card-body">
                    <h2 class="h6 text-muted text-uppercase">FY <?= esc((string) ($fiscal_year_summary['fiscal_year'] ?? date('Y'))) ?> Allocated</h2>
                    <p class="display-6 fw-bold mb-0">₱<?= esc(number_format((float) ($fiscal_year_summary['total_allocated'] ?? 0), 2)) ?></p>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="h4 mb-0">Recently Published Projects</h2>
        <a href="<?= site_url('aip') ?>" class="btn btn-sm btn-outline-primary">View all</a>
    </div>

    <?php if ($recent_projects === []): ?>
        <p class="text-muted">No published projects yet.</p>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($recent_projects as $project): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <span class="badge bg-success badge-status mb-2"><?= esc($project['status']) ?></span>
                            <h3 class="h6 card-title"><?= esc($project['title']) ?></h3>
                            <p class="small text-muted mb-1"><?= esc($project['office_name'] ?? '') ?> · FY <?= esc((string) $project['fiscal_year']) ?></p>
                            <p class="small mb-3">₱<?= esc(number_format((float) $project['allocated_amount'], 2)) ?> allocated</p>
                            <a href="<?= site_url('projects/' . $project['id']) ?>" class="btn btn-sm btn-primary">View details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
echo view('layouts/public', [
    'title'           => 'Home — Kabankalan Budget Portal',
    'metaDescription' => 'Browse Kabankalan City budget projects, visions, and citizen feedback.',
    'content'         => $content,
]);
