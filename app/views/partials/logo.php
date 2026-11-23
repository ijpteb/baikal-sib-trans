<?php
/**
 * Логотип: загруженный в админке или стандартный знак + название.
 *
 * @var string $class  дополнительный CSS-класс
 */

$logo = site_logo();
$name = (string) setting('site.name', 'Байкал Сиб-Транс');
$tagline = trim((string) setting('site.tagline', ''));
?>
<a class="<?= e(classes('logo', $class ?? '')) ?>" href="/"<?= ($current ?? false) ? ' aria-current="page"' : '' ?>>
<?php if ($logo !== null): ?>
  <?= image_tag($logo, '220px', ['class' => 'logo__img', 'alt' => $name, 'loading' => null, 'decoding' => null, 'src' => image_url($logo, 480)]) ?>
<?php else: ?>
  <img class="logo__mark" src="<?= e(asset('img/logo-mark.svg')) ?>" width="40" height="40" alt="">
  <span class="logo__text">
    <span class="logo__name"><?= e($name) ?></span>
<?php if ($tagline !== ''): ?>
    <span class="logo__tagline"><?= e($tagline) ?></span>
<?php endif; ?>
  </span>
<?php endif; ?>
</a>
