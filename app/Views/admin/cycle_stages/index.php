<?php
/**
 * @var array $stages
 * @var array $currentUser
 */
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0"><i class="bi bi-diagram-3 me-2"></i>Budget Cycle Stages</h2>
    <a href="<?= site_url('admin/cycle-stages/new') ?>" class="btn btn-kb-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Stage
    </a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>#</th><th>Slug</th><th>Name</th><th>Sort</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($stages)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No stages found.</td></tr>
            <?php else: ?>
                <?php foreach ($stages as $s): ?>
                <tr>
                    <td><?= esc($s['id']) ?></td>
                    <td><code><?= esc($s['slug']) ?></code></td>
                    <td><?= esc($s['name']) ?></td>
                    <td><?= esc($s['sort_order']) ?></td>
                    <td>
                        <span class="badge <?= $s['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $s['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="<?= site_url('admin/cycle-stages/' . $s['id']) ?>" class="btn btn-sm btn-outline-primary">
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
