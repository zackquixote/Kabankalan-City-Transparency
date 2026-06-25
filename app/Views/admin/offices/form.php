<?php
/**
 * @var array|null  $office    null on create
 * @var array|null  $validation
 */
$isEdit = ($office !== null);
$action = $isEdit ? site_url('admin/offices/' . $office['id']) : site_url('admin/offices');
$old    = fn(string $k, $default = '') => old($k, $isEdit ? ($office[$k] ?? $default) : $default);
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('admin/offices') ?>" class="btn btn-sm btn-outline-secondary">
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
                <label class="form-label" for="code">Office Code <span class="text-danger">*</span></label>
                <input type="text" id="code" name="code" class="form-control text-uppercase"
                       value="<?= esc($old('code')) ?>" maxlength="20" required>
            </div>
            <div class="col-md-8">
                <label class="form-label" for="name">Office Name <span class="text-danger">*</span></label>
                <input type="text" id="name" name="name" class="form-control"
                       value="<?= esc($old('name')) ?>" maxlength="150" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="contact_email">Contact Email</label>
                <input type="email" id="contact_email" name="contact_email" class="form-control"
                       value="<?= esc($old('contact_email')) ?>" maxlength="255">
            </div>
            <div class="col-md-6 d-flex align-items-end">
                <div class="form-check form-switch mb-1">
                    <input class="form-check-input" type="checkbox" id="is_active" name="is_active"
                           value="1" <?= $old('is_active', 1) ? 'checked' : '' ?>>
                    <label class="form-check-label" for="is_active">Active</label>
                </div>
            </div>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description" class="form-control" rows="3"><?= esc($old('description')) ?></textarea>
            </div>
        </div>
        <hr class="my-4">
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-kb-primary">
                <i class="bi bi-floppy me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Office' ?>
            </button>
            <a href="<?= site_url('admin/offices') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>
