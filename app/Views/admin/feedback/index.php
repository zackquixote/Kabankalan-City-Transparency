<?php
/**
 * @var array   $feedback
 * @var object  $pager
 * @var array   $statuses
 * @var array   $currentUser
 */
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2 class="h5 mb-0"><i class="bi bi-chat-dots me-2"></i>Feedback Moderation Queue</h2>
</div>

<div class="admin-card">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>Project</th>
                    <th>Author</th>
                    <th>Excerpt</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($feedback)): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No feedback items.</td></tr>
            <?php else: ?>
                <?php foreach ($feedback as $f): ?>
                <tr>
                    <td class="small"><?= esc($f['project_title'] ?? "#{$f['project_id']}") ?></td>
                    <td><?= esc($f['author_name']) ?></td>
                    <td class="text-muted small"><?= esc(mb_substr($f['body'], 0, 80)) ?>…</td>
                    <td>
                        <?php
                        $badgeMap = [
                            'pending'   => 'badge-submitted',
                            'reviewed'  => 'badge-approved',
                            'addressed' => 'badge-published',
                            'dismissed' => 'badge-cancelled',
                        ];
                        ?>
                        <span class="badge <?= $badgeMap[$f['status']] ?? 'bg-secondary' ?>">
                            <?= esc(ucfirst($f['status'])) ?>
                        </span>
                    </td>
                    <td class="small"><?= esc(date('M j, Y', strtotime($f['created_at']))) ?></td>
                    <td class="text-end">
                        <a href="<?= site_url('admin/feedback/' . $f['id']) ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye me-1"></i>Review
                        </a>
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
