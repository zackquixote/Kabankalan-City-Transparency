<?php

/**
 * @var PagerRenderer $pager
 */
$pager->setSurroundCount(2);
?>
<?php if ($pager->getPageCount() > 1): ?>
<nav aria-label="Pagination" class="mt-4">
    <ul class="pagination justify-content-center flex-wrap">
        <?php if ($pager->hasPrevious()): ?>
            <li class="page-item">
                <a class="page-link" href="<?= esc($pager->getFirst(), 'attr') ?>">First</a>
            </li>
            <li class="page-item">
                <a class="page-link" href="<?= esc($pager->getPrevious(), 'attr') ?>">Prev</a>
            </li>
        <?php endif; ?>

        <?php foreach ($pager->links() as $link): ?>
            <li class="page-item<?= $link['active'] ? ' active' : '' ?>">
                <a class="page-link" href="<?= esc($link['uri'], 'attr') ?>"><?= esc($link['title']) ?></a>
            </li>
        <?php endforeach; ?>

        <?php if ($pager->hasNext()): ?>
            <li class="page-item">
                <a class="page-link" href="<?= esc($pager->getNext(), 'attr') ?>">Next</a>
            </li>
            <li class="page-item">
                <a class="page-link" href="<?= esc($pager->getLast(), 'attr') ?>">Last</a>
            </li>
        <?php endif; ?>
    </ul>
</nav>
<?php endif; ?>
