<?php
/**
 * Хлебные крошки: Главная / Раздел.
 *
 * @var list<array{label: string, href: string}> $crumbs
 */

$last = count($crumbs) - 1;
?>
<nav class="breadcrumbs" aria-label="Вы здесь">
  <ol>
<?php foreach ($crumbs as $i => $crumb): ?>
<?php if ($i === $last): ?>
    <li><span aria-current="page"><?= e($crumb['label']) ?></span></li>
<?php else: ?>
    <li><a href="<?= e($crumb['href']) ?>"><?= e($crumb['label']) ?></a></li>
<?php endif; ?>
<?php endforeach; ?>
  </ol>
</nav>
