<?php
/**
 * Страница политики конфиденциальности.
 *
 * @var array  $crumbs
 * @var string $body
 * @var int    $updatedAt
 */
?>
<section class="page-hero page-hero--compact" aria-labelledby="page-title">
  <div class="hero__bg"><?= inline_svg_file(APP_DIR . '/views/partials/hero-art.svg') ?></div>
  <div class="container page-hero__inner">
    <?= view('partials/breadcrumbs', ['crumbs' => $crumbs]) ?>
    <h1 class="page-hero__title" id="page-title">Политика конфиденциальности</h1>
    <p class="page-hero__lead">Коротко: сайт не собирает и не хранит ваши персональные данные. Заявку вы отправляете сами — по телефону или в мессенджер.</p>
  </div>
</section>

<section class="section">
  <div class="container">
    <article class="prose">
      <?= $body ?>
<?php if ($updatedAt > 0): ?>
      <p class="prose__updated">Редакция от <?= e(date_ru(date('Y-m-d', $updatedAt))) ?></p>
<?php endif; ?>
    </article>
  </div>
</section>
