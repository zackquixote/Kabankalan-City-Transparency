<?php
/**
 * @var array $visions
 * @var array $currentUser
 */
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0"><i class="bi bi-eye me-2"></i>Visions / Plan Periods</h2>
    <a href="<?= site_url('admin/visions/new') ?>" class="btn btn-kb-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Vision
    </a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Period</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($visions)): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No visions found.</td></tr>
            <?php else: ?>
                <?php foreach ($visions as $v): ?>
                <tr>
                    <td><?= esc($v['title']) ?></td>
                    <td><?= esc($v['start_year']) ?> – <?= esc($v['end_year']) ?></td>
                    <td>
                        <span class="badge <?= $v['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $v['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="<?= site_url('admin/visions/' . $v['id']) ?>"
                           class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Edit
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
