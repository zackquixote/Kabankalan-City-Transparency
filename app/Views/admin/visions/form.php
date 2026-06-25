<?php
/**
 * @var array|null  $vision    null on create
 * @var array|null  $validation
 */
$isEdit = ($vision !== null);
$action = $isEdit ? site_url('admin/visions/' . $vision['id']) : site_url('admin/visions');
$old    = fn(string $k, $default = '') => old($k, $isEdit ? ($vision[$k] ?? $default) : $default);
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('admin/visions') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h5 mb-0"><?= esc($title) ?></h2>
</div>

<?php if (! empty($validation)): ?>
<div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($validation as $e): ?><li><?= esc($e) ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<div class="admin-card p-4">
    <form method="post" action="<?= $action ?>">
        <?= csrf_field() ?>
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label" for="title">Title <span class="text-danger">*</span></label>
                <input type="text" id="title" name="title" class="form-control"
                       value="<?= esc($old('title')) ?>" maxlength="255" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="start_year">Start Year <span class="text-danger">*</span></label>
                <input type="number" id="start_year" name="start_year" class="form-control"
                       value="<?= esc($old('start_year', date('Y'))) ?>" min="2000" max="2100" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="end_year">End Year <span class="text-danger">*</span></label>
                <input type="number" id="end_year" name="end_year" class="form-control"
                       value="<?= esc($old('end_year', date('Y') + 5)) ?>" min="2000" max="2100" required>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="4"><?= esc($old('description')) ?></textarea>
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <div class="form-check form-switch mt-1">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                           value="1" <?= $old('is_active', 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>
        <hr class="my-4">
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-kb-primary">
                <i class="bi bi-floppy me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Vision' ?>
            </button>
            <a href="<?= site_url('admin/visions') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
