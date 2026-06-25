<?php
/**
 * @var array $offices
 * @var array $currentUser
 */
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0"><i class="bi bi-building me-2"></i>Government Offices</h2>
    <a href="<?= site_url('admin/offices/new') ?>" class="btn btn-kb-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Office
    </a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr><th>Code</th><th>Name</th><th>Contact</th><th>Status</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            <?php if (empty($offices)): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No offices found.</td></tr>
            <?php else: ?>
                <?php foreach ($offices as $o): ?>
                <tr>
                    <td><code><?= esc($o['code']) ?></code></td>
                    <td><?= esc($o['name']) ?></td>
                    <td class="text-muted small"><?= esc($o['contact_email'] ?: '—') ?></td>
                    <td>
                        <span class="badge <?= $o['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $o['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a href="<?= site_url('admin/offices/' . $o['id']) ?>" class="btn btn-sm btn-outline-primary">
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
