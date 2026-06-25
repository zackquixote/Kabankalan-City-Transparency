<?php
/**
 * Shared create / edit form for projects.
 *
 * @var array|null  $project      null on create
 * @var array       $offices
 * @var array       $visions
 * @var array       $stages
 * @var int|null    $lockedOffice  non-null for office_staff (office pre-set)
 * @var array|null  $validation
 * @var array       $currentUser
 * @var array       $attachments   existing attachments (edit only)
 */
$isEdit  = ($project !== null);
$action  = $isEdit
    ? site_url('admin/projects/' . $project['id'])
    : site_url('admin/projects');
$old = fn(string $k, $default = '') => old($k, $isEdit ? ($project[$k] ?? $default) : $default);
?>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="<?= site_url('admin/projects') ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <h2 class="h5 mb-0"><?= esc($title) ?></h2>
</div>

<?php if (! empty($validation)): ?>
<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($validation as $err): ?>
        <li><?= esc($err) ?></li>
        <?php endforeach; ?>
    </ul>
</div>
<?php endif; ?>

<div class="admin-card p-4">
    <form method="post" action="<?= $action ?>" novalidate>
        <?= csrf_field() ?>

        <div class="row g-3">

            <div class="col-md-4">
                <label class="form-label" for="project_code">Project Code <span class="text-danger">*</span></label>
                <input type="text" id="project_code" name="project_code"
                       class="form-control text-uppercase"
                       value="<?= esc($old('project_code')) ?>"
                       maxlength="30" required>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="fiscal_year">Fiscal Year <span class="text-danger">*</span></label>
                <input type="number" id="fiscal_year" name="fiscal_year"
                       class="form-control"
                       value="<?= esc($old('fiscal_year', date('Y'))) ?>"
                       min="2000" max="2100" required>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="barangay">Barangay</label>
                <input type="text" id="barangay" name="barangay"
                       class="form-control"
                       value="<?= esc($old('barangay')) ?>"
                       maxlength="100">
            </div>

            <div class="col-12">
                <label class="form-label" for="title">Project Title <span class="text-danger">*</span></label>
                <input type="text" id="title" name="title"
                       class="form-control"
                       value="<?= esc($old('title')) ?>"
                       maxlength="255" required>
            </div>

            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea id="description" name="description"
                          class="form-control" rows="4"><?= esc($old('description')) ?></textarea>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="office_id">Office <span class="text-danger">*</span></label>
                <?php if ($lockedOffice): ?>
                    <input type="hidden" name="office_id" value="<?= (int) $lockedOffice ?>">
                    <?php
                    $lockedOfficeName = '';
                    foreach ($offices as $o) {
                        if ((int)$o['id'] === (int)$lockedOffice) { $lockedOfficeName = $o['name']; break; }
                    }
                    ?>
                    <input type="text" class="form-control" value="<?= esc($lockedOfficeName) ?>" readonly>
                <?php else: ?>
                    <select id="office_id" name="office_id" class="form-select" required>
                        <option value="">-- Select office --</option>
                        <?php foreach ($offices as $o): ?>
                        <option value="<?= $o['id'] ?>"
                            <?= (string)$old('office_id', $project['office_id'] ?? '') === (string)$o['id'] ? 'selected' : '' ?>>
                            <?= esc($o['name']) ?> (<?= esc($o['code']) ?>)
                        </option>
                        <?php endforeach; ?>
                    </select>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="vision_id">Vision / Plan Period <span class="text-danger">*</span></label>
                <select id="vision_id" name="vision_id" class="form-select" required>
                    <option value="">-- Select vision --</option>
                    <?php foreach ($visions as $v): ?>
                    <option value="<?= $v['id'] ?>"
                        <?= (string)$old('vision_id', $project['vision_id'] ?? '') === (string)$v['id'] ? 'selected' : '' ?>>
                        <?= esc($v['title']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="budget_cycle_stage_id">Budget Cycle Stage <span class="text-danger">*</span></label>
                <select id="budget_cycle_stage_id" name="budget_cycle_stage_id" class="form-select" required>
                    <option value="">-- Select stage --</option>
                    <?php foreach ($stages as $s): ?>
                    <option value="<?= $s['id'] ?>"
                        <?= (string)$old('budget_cycle_stage_id', $project['budget_cycle_stage_id'] ?? '') === (string)$s['id'] ? 'selected' : '' ?>>
                        <?= esc($s['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-4">
                <label class="form-label" for="allocated_amount">Allocated Amount (P) <span class="text-danger">*</span></label>
                <input type="number" id="allocated_amount" name="allocated_amount"
                       class="form-control"
                       value="<?= esc($old('allocated_amount', 0)) ?>"
                       min="0" step="0.01" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="obligated_amount">Obligated Amount (P)</label>
                <input type="number" id="obligated_amount" name="obligated_amount"
                       class="form-control"
                       value="<?= esc($old('obligated_amount')) ?>"
                       min="0" step="0.01">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="disbursed_amount">Disbursed Amount (P)</label>
                <input type="number" id="disbursed_amount" name="disbursed_amount"
                       class="form-control"
                       value="<?= esc($old('disbursed_amount')) ?>"
                       min="0" step="0.01">
            </div>

            <div class="col-md-4">
                <label class="form-label" for="target_completion_date">Target Completion Date</label>
                <input type="date" id="target_completion_date" name="target_completion_date"
                       class="form-control"
                       value="<?= esc($old('target_completion_date')) ?>">
            </div>

            <div class="col-md-4">
                <label class="form-label" for="latitude">Latitude <span class="text-muted fw-normal">(optional)</span></label>
                <input type="number" id="latitude" name="latitude"
                       class="form-control"
                       value="<?= esc($old('latitude')) ?>"
                       step="any" placeholder="e.g. 10.1167" min="-90" max="90">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="longitude">Longitude <span class="text-muted fw-normal">(optional)</span></label>
                <input type="number" id="longitude" name="longitude"
                       class="form-control"
                       value="<?= esc($old('longitude')) ?>"
                       step="any" placeholder="e.g. 122.8167" min="-180" max="180">
            </div>
        </div>

        <hr class="my-4">
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-kb-primary">
                <i class="bi bi-floppy me-1"></i><?= $isEdit ? 'Save Changes' : 'Create Project' ?>
            </button>
            <a href="<?= site_url('admin/projects') ?>" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </form>
</div>

<?php if ($isEdit): ?>
<!-- Attachments Panel -->
<div class="admin-card p-4 mt-4">
    <h3 class="h6 mb-3"><i class="bi bi-paperclip me-2"></i>Attachments</h3>

    <?php
    $images = array_filter($attachments ?? [], fn($a) => str_starts_with($a['mime_type'], 'image/'));
    $docs   = array_filter($attachments ?? [], fn($a) => ! str_starts_with($a['mime_type'], 'image/'));
    ?>

    <?php if (! empty($images)): ?>
    <div class="mb-3">
        <p class="small text-muted fw-semibold mb-2 text-uppercase" style="font-size:.7rem">Photos</p>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($images as $img): ?>
            <div class="position-relative" style="width:100px">
                <a href="<?= site_url('attachments/' . $img['id']) ?>" target="_blank" rel="noopener">
                    <img src="<?= site_url('attachments/' . $img['id']) ?>"
                         alt="<?= esc($img['label'] ?? $img['original_filename']) ?>"
                         style="width:100px;height:80px;object-fit:cover;border-radius:8px;border:1px solid #e2e8f0">
                </a>
                <form method="post"
                      action="<?= site_url('admin/projects/' . $project['id'] . '/attachments/' . $img['id'] . '/delete') ?>"
                      onsubmit="return confirm('Delete this photo?')">
                    <?= csrf_field() ?>
                    <button type="submit" title="Delete"
                            style="position:absolute;top:-6px;right:-6px;background:#dc3545;color:#fff;border:none;border-radius:50%;width:20px;height:20px;font-size:.65rem;line-height:1;cursor:pointer">
                        <i class="bi bi-x"></i>
                    </button>
                </form>
                <p class="text-muted" style="font-size:.65rem;margin:.2rem 0 0;overflow:hidden;white-space:nowrap;text-overflow:ellipsis;max-width:100px">
                    <?= esc($img['label'] ?? $img['original_filename']) ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if (! empty($docs)): ?>
    <div class="mb-3">
        <p class="small text-muted fw-semibold mb-2 text-uppercase" style="font-size:.7rem">Documents</p>
        <?php foreach ($docs as $doc): ?>
        <div class="d-flex align-items-center justify-content-between border rounded p-2 mb-1" style="background:#f8fafc">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-file-earmark-pdf text-danger fs-5"></i>
                <div>
                    <a href="<?= site_url('attachments/' . $doc['id']) ?>" target="_blank" rel="noopener"
                       class="small fw-semibold text-decoration-none text-dark">
                        <?= esc($doc['label'] ?? $doc['original_filename']) ?>
                    </a>
                    <p class="mb-0 text-muted" style="font-size:.7rem">
                        <?= esc($doc['original_filename']) ?> &bull; <?= round($doc['file_size'] / 1024, 1) ?> KB
                    </p>
                </div>
            </div>
            <form method="post"
                  action="<?= site_url('admin/projects/' . $project['id'] . '/attachments/' . $doc['id'] . '/delete') ?>"
                  onsubmit="return confirm('Delete this document?')">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                    <i class="bi bi-trash"></i>
                </button>
            </form>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($attachments)): ?>
        <p class="text-muted small mb-3">No attachments yet.</p>
    <?php endif; ?>

    <hr class="my-3">
    <p class="small text-muted fw-semibold mb-2 text-uppercase" style="font-size:.7rem">Upload New Attachment</p>
    <form method="post"
          action="<?= site_url('admin/projects/' . $project['id'] . '/attachments') ?>"
          enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small" for="attachment">File
                    <span class="text-muted fw-normal">(JPG PNG WEBP GIF PDF DOCX, max 10 MB)</span>
                </label>
                <input type="file" id="attachment" name="attachment" class="form-control form-control-sm"
                       accept=".jpg,.jpeg,.png,.webp,.gif,.pdf,.docx" required>
            </div>
            <div class="col-md-4">
                <label class="form-label small" for="label">Label <span class="text-muted fw-normal">(optional)</span></label>
                <input type="text" id="label" name="label" class="form-control form-control-sm"
                       placeholder="e.g. Site photo before construction" maxlength="255">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-kb-primary w-100">
                    <i class="bi bi-upload me-1"></i> Upload
                </button>
            </div>
        </div>
    </form>
</div>
<?php endif; ?>
