<?php
/**
 * @var array|null  $stage     null on create
 * @var array|null  $validation
 */
$isEdit = ($stage !== null);
$action = $isEdit ? site_url('admin/cycle-stages/' . $stage['id']) : site_url('admin/cycle-stages');
$old    = fn(string $k, $default = '') => old($k, $isEdit ? ($stage[$k] ?? $default) : $default);
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('admin/cycle-stages') ?>" class="btn btn-sm btn-outline-secondary">
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
            <div class="col-md-4">
                <label class="form-label" for="slug">Slug <span class="text-danger">*</span></label>
                <input type="text" id="slug" name="slug" class="form-control"
                       value="<?= esc($old('slug')) ?>" maxlength="50"
                       pattern="[a-zA-Z0-9_\-]+" required>
                <div class="form-text">Lowercase letters, numbers, dashes (used in URLs).</div>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="name">Display Name <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" class="form-control"
                       value="<?= esc($old('name')) ?>" maxlength="100" required>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="sort_order">Order</label>
                <input type="number" id="sort_order" name="sort_order" class="form-control"
                       value="<?= esc($old('sort_order', 0)) ?>" min="0" max="255">
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3"><?= esc($old('description')) ?></textarea>
            </div>
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                           value="1" <?= $old('is_active', 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
        </div>
        <hr class="my-4">
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-kb-primary">
                <i class="bi bi-floppy me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Stage' ?>
            </button>
            <a href="<?= site_url('admin/cycle-stages') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
