<?php
/**
 * @var string     $title
 * @var array      $user
 * @var array      $currentUser  (provided by adminView())
 */
$user = $user ?? $currentUser;
?>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="admin-card p-3 d-flex align-items-center gap-3">
            <div class="fs-2 text-primary"><i class="bi bi-person-badge-fill"></i></div>
            <div>
                <div class="text-muted small">Role</div>
                <div class="fw-600 text-capitalize"><?= esc(str_replace('_', ' ', $user['role'])) ?></div>
            </div>
        </div>
    </div>
    <?php if ($user['office_id']): ?>
    <div class="col-md-4">
        <div class="admin-card p-3 d-flex align-items-center gap-3">
            <div class="fs-2 text-info"><i class="bi bi-building"></i></div>
            <div>
                <div class="text-muted small">Office ID</div>
                <div class="fw-600"><?= esc($user['office_id']) ?></div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <div class="col-md-4">
        <div class="admin-card p-3 d-flex align-items-center gap-3">
            <div class="fs-2 text-success"><i class="bi bi-clock-history"></i></div>
            <div>
                <div class="text-muted small">Session started</div>
                <div class="fw-600"><?= date('M j, Y g:i A', (int) $user['login_time']) ?></div>
            </div>
        </div>
    </div>
</div>

<div class="admin-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-lightning-charge me-2 text-warning"></i>Quick Actions</span>
    </div>
    <div class="card-body">
        <div class="row g-3">
            <?php if (in_array($user['role'], ['super_admin', 'admin', 'office_staff'])): ?>
            <div class="col-sm-6 col-lg-4">
                <a href="<?= site_url('admin/projects') ?>" class="btn btn-outline-primary w-100 py-3">
                    <i class="bi bi-folder2-open d-block fs-3 mb-1"></i>Manage Projects
                </a>
            </div>
            <div class="col-sm-6 col-lg-4">
                <a href="<?= site_url('admin/feedback') ?>" class="btn btn-outline-primary w-100 py-3">
                    <i class="bi bi-chat-dots d-block fs-3 mb-1"></i>Moderate Feedback
                </a>
            </div>
            <?php endif; ?>
            <?php if (in_array($user['role'], ['super_admin', 'admin'])): ?>
            <div class="col-sm-6 col-lg-4">
                <a href="<?= site_url('admin/visions') ?>" class="btn btn-outline-secondary w-100 py-3">
                    <i class="bi bi-eye d-block fs-3 mb-1"></i>Manage Visions
                </a>
            </div>
            <div class="col-sm-6 col-lg-4">
                <a href="<?= site_url('admin/cycle-stages') ?>" class="btn btn-outline-secondary w-100 py-3">
                    <i class="bi bi-diagram-3 d-block fs-3 mb-1"></i>Cycle Stages
                </a>
            </div>
            <?php endif; ?>
            <?php if ($user['role'] === 'super_admin'): ?>
            <div class="col-sm-6 col-lg-4">
                <a href="<?= site_url('admin/offices') ?>" class="btn btn-outline-danger w-100 py-3">
                    <i class="bi bi-building d-block fs-3 mb-1"></i>Manage Offices
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>