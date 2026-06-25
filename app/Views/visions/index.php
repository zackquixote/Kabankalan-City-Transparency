<?php

/** @var list<array<string, mixed>> $visions */
?>
<?php ob_start(); ?>
<div class="container py-5">
    <h1 class="mb-2">City Development Visions</h1>
    <p class="text-muted mb-4">Strategic priorities guiding Kabankalan's annual investment programs.</p>

    <?php if ($visions === []): ?>
        <p class="text-muted">No active visions are published at this time.</p>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($visions as $vision): ?>
                <div class="col-lg-6">
                    <article class="card h-100 shadow-sm">
                        <div class="card-body">
                            <h2 class="h5 card-title"><?= esc($vision['title']) ?></h2>
                            <p class="text-muted small mb-2"><?= esc((string) $vision['start_year']) ?> – <?= esc((string) $vision['end_year']) ?></p>
                            <p class="card-text"><?= esc($vision['description'] ?? '') ?></p>
                            <p class="small mb-3"><strong><?= esc((string) $vision['public_project_count']) ?></strong> public project(s) linked</p>
                            <a href="<?= site_url('aip?vision_id=' . (int) $vision['id']) ?>" class="btn btn-outline-primary btn-sm">View related projects</a>
                        </div>
                    </article>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php
$content = ob_get_clean();
echo view('layouts/public', [
    'title'   => 'Visions — Kabankalan Budget Portal',
    'content' => $content,
]);
