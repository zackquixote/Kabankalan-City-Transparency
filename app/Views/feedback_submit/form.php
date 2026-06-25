<?php

$config = config('Honeypot');

/** @var array<string, mixed> $project */
/** @var array<string, string>|null $errors */
$errors = session()->getFlashdata('errors') ?? [];
?>
<?php ob_start(); ?>
<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= site_url('projects/' . $project['id']) ?>"><?= esc($project['project_code']) ?></a></li>
            <li class="breadcrumb-item active" aria-current="page">Feedback</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h1 class="h3 mb-1">Submit feedback</h1>
            <p class="text-muted mb-4">Share your comments on <strong><?= esc($project['title']) ?></strong>. No login required.</p>

            <?php if ($errors !== []): ?>
                <div class="alert alert-danger" role="alert">
                    <ul class="mb-0">
                        <?php foreach ($errors as $message): ?>
                            <li><?= esc($message) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <?= form_open(site_url('feedback/' . $project['id']), ['method' => 'post']) ?>
                        <?= csrf_field() ?>

                        <div class="honeypot-wrap" aria-hidden="true">
                            <label for="hp-field"><?= esc($config->label) ?></label>
                            <input type="text" name="<?= esc($config->name, 'attr') ?>" id="hp-field" value="" tabindex="-1" autocomplete="off">
                        </div>

                        <div class="mb-3">
                            <label for="author_name" class="form-label">Your name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="author_name" name="author_name" required maxlength="150"
                                   value="<?= esc(old('author_name') ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="author_email" class="form-label">Email (optional)</label>
                            <input type="email" class="form-control" id="author_email" name="author_email" maxlength="255"
                                   value="<?= esc(old('author_email') ?? '') ?>">
                        </div>

                        <div class="mb-3">
                            <label for="body" class="form-label">Message <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="body" name="body" rows="6" required minlength="10" maxlength="5000"><?= esc(old('body') ?? '') ?></textarea>
                            <div class="form-text">Minimum 10 characters. HTML and scripts are not allowed.</div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Send feedback</button>
                            <a href="<?= site_url('projects/' . $project['id']) ?>" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    <?= form_close() ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
$content = ob_get_clean();
echo view('layouts/public', [
    'title'   => 'Submit feedback — Kabankalan Budget Portal',
    'content' => $content,
]);
