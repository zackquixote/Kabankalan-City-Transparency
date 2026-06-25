<?php

/** @var array<string, mixed> $project */
?>
<?php ob_start(); ?>
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8 text-center">
            <div class="card shadow-sm">
                <div class="card-body py-5">
                    <h1 class="h3 text-success mb-3">Thank you!</h1>
                    <p class="lead mb-4"><?= esc(session()->getFlashdata('success') ?? 'Your feedback has been received.') ?></p>
                    <p class="text-muted">Your comments on <strong><?= esc($project['title']) ?></strong> will be reviewed by the concerned office.</p>
                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                        <a href="<?= site_url('projects/' . $project['id']) ?>" class="btn btn-primary">Back to project</a>
                        <a href="<?= site_url('aip') ?>" class="btn btn-outline-secondary">Browse more projects</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
echo view('layouts/public', [
    'title'   => 'Feedback received — Kabankalan Budget Portal',
    'content' => $content,
]);
