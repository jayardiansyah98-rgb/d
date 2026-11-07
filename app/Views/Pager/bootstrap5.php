<?php

use CodeIgniter\Pager\PagerRenderer;

/** @var PagerRenderer $pager */
$pager->setSurroundCount(2);
?>
<?php if ($pager->getPageCount() > 1) : ?>
    <nav aria-label="Navigasi halaman">
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $pager->hasPrevious() ? '' : 'disabled' ?>">
                <a class="page-link" href="<?= esc($pager->getPrevious() ?? '#', 'attr') ?>" aria-label="Sebelumnya">
                    <i class="bi bi-chevron-left"></i>
                </a>
            </li>

            <?php foreach ($pager->links() as $link) : ?>
                <li class="page-item <?= $link['active'] ? 'active' : '' ?>">
                    <a class="page-link" href="<?= esc($link['uri'], 'attr') ?>"><?= esc($link['title']) ?></a>
                </li>
            <?php endforeach ?>

            <li class="page-item <?= $pager->hasNext() ? '' : 'disabled' ?>">
                <a class="page-link" href="<?= esc($pager->getNext() ?? '#', 'attr') ?>" aria-label="Berikutnya">
                    <i class="bi bi-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
<?php endif ?>
