<?php
/**
 * @var array   $item     feedback row
 * @var array   $project  parent project row
 * @var array|null $validation
 * @var array   $currentUser
 */
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('admin/feedback') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h5 mb-0">Review Feedback #<?= esc($item['id']) ?></h2>
</div>

<?php if (! empty($validation)): ?>
<div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($validation as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Feedback detail -->
    <div class="col-lg-7">
        <div class="admin-card p-4">
            <h6 class="text-muted small mb-1">Project</h6>
            <p class="fw-600 mb-3"><?= esc($project['title']) ?> <code class="small"><?= esc($project['project_code']) ?></code></p>

            <h6 class="text-muted small mb-1">Submitted by</h6>
            <p class="mb-1"><?= esc($item['author_name']) ?>
                <?php if ($item['author_email']): ?>
                    &lt;<?= esc($item['author_email']) ?>&gt;
                <?php endif; ?>
            </p>
            <p class="text-muted small mb-3"><?= esc(date('F j, Y g:i A', strtotime($item['created_at']))) ?></p>

            <h6 class="text-muted small mb-1">Feedback</h6>
            <div class="p-3 bg-light border rounded mb-3" style="white-space: pre-wrap;"><?= esc($item['body']) ?></div>

            <?php if ($item['admin_response']): ?>
            <h6 class="text-muted small mb-1">Previous Response</h6>
            <div class="p-3 border-start border-4 border-success bg-light rounded mb-2">
                <?= esc($item['admin_response']) ?>
            </div>
            <p class="text-muted small">Responded at <?= esc(date('M j, Y g:i A', strtotime($item['responded_at']))) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Response form -->
    <div class="col-lg-5">
        <div class="admin-card p-4">
            <h6 class="fw-600 mb-3"><i class="bi bi-reply me-2"></i>Write a Response</h6>
            <form method="post" action="<?= site_url('admin/feedback/' . $item['id'] . '/respond') ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="admin_response">Response <span class="text-danger">*</span></label>
                    <textarea id="admin_response" name="admin_response" class="form-control" rows="5"
                              required><?= esc(old('admin_response', $item['admin_response'] ?? '')) ?></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="status">Mark as</label>
                    <select id="status" name="status" class="form-select">
                        <option value="reviewed" <?= $item['status'] === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                        <option value="addressed" <?= $item['status'] === 'addressed' ? 'selected' : '' ?>>Addressed</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-kb-primary w-100">
                    <i class="bi bi-send me-1"></i>Save Response
                </button>
            </form>

            <hr>

            <form method="post" action="<?= site_url('admin/feedback/' . $item['id'] . '/dismiss') ?>"
                  onsubmit="return confirm('Dismiss this feedback? It will be marked as dismissed.')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-outline-danger w-100">
                    <i class="bi bi-x-circle me-1"></i>Dismiss
                </button>
            </form>
        </div>
    </div>
</div>
