<?php
/**
 * @var array   $projects
 * @var object  $pager
 * @var array   $currentUser
 */
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0"><i class="bi bi-folder2-open me-2"></i>Projects</h2>
    <a href="<?= site_url('admin/projects/new') ?>" class="btn btn-kb-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Project
    </a>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Title</th>
                    <th>Office</th>
                    <th>Vision</th>
                    <th>Year</th>
                    <th>Status</th>
                    <th>Allocated</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($projects)): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No projects found.</td></tr>
            <?php else: ?>
                <?php foreach ($projects as $p): ?>
                <tr>
                    <td><code><?= esc($p['project_code']) ?></code></td>
                    <td><?= esc($p['title']) ?></td>
                    <td><?= esc($p['office_name'] ?? $p['office_id']) ?></td>
                    <td class="text-muted small"><?= esc($p['vision_title'] ?? '—') ?></td>
                    <td><?= esc($p['fiscal_year']) ?></td>
                    <td>
                        <span class="badge badge-<?= esc($p['status']) ?>">
                            <?= esc(ucfirst(str_replace('_', ' ', $p['status']))) ?>
                        </span>
                    </td>
                    <td>₱<?= number_format((float)$p['allocated_amount'], 2) ?></td>
                    <td class="text-end">
                        <div class="btn-group btn-group-sm">
                            <a href="<?= site_url('admin/projects/' . $p['id']) ?>"
                               class="btn btn-outline-primary" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if ($p['status'] === 'draft'): ?>
                            <form method="post"
                                  action="<?= site_url('admin/projects/' . $p['id'] . '/submit') ?>"
                                  class="d-inline"
                                  onsubmit="return confirm('Submit this project for review?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-info" title="Submit"><i class="bi bi-send"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php if (in_array($p['status'], ['submitted','under_review','approved'])
                                  && in_array($currentUser['role'], ['super_admin','admin'])): ?>
                            <form method="post"
                                  action="<?= site_url('admin/projects/' . $p['id'] . '/publish') ?>"
                                  class="d-inline"
                                  onsubmit="return confirm('Publish this project?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-success" title="Publish"><i class="bi bi-globe"></i></button>
                            </form>
                            <?php endif; ?>
                            <?php if ($p['status'] === 'draft'): ?>
                            <form method="post"
                                  action="<?= site_url('admin/projects/' . $p['id'] . '/delete') ?>"
                                  class="d-inline"
                                  onsubmit="return confirm('Permanently delete this draft?')">
                                <?= csrf_field() ?>
                                <button class="btn btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if (! empty($pager)): ?>
    <div class="card-footer bg-transparent d-flex justify-content-end">
        <?= $pager->links() ?>
    </div>
    <?php endif; ?>
</div>
