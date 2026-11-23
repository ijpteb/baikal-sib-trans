<?php
/**
 * «Почему выбирают нас»: текст о компании, преимущества и цифры.
 *
 * @var array $advantages
 * @var array $stats
 */

$title = trim((string) setting('about.title', '')) ?: 'Почему выбирают нас';
$text = trim((string) setting('about.text', ''));
?>
<section class="section section--soft" id="about" aria-labelledby="about-title">
  <div class="container">
    <div class="section-head" data-reveal>
      <p class="eyebrow">О компании</p>
      <h2 class="section-title" id="about-title"><?= e($title) ?></h2>
<?php if ($text !== ''): ?>
      <div class="section-lead"><?= text_to_html($text) ?></div>
<?php endif; ?>
    </div>

<?php if ($advantages): ?>
    <ul class="advantages" data-stagger>
<?php foreach ($advantages as $adv): ?>
<?php $isInspection = str_contains(mb_strtolower((string) $adv['title']), 'техосмотр'); ?>
      <li data-reveal>
        <article class="<?= e(classes('card', 'advantage', $isInspection ? 'advantage--accent' : null)) ?>">
          <span class="icon-tile"><?= icon((string) $adv['icon']) ?: icon('check-circle') ?></span>
          <h3 class="advantage__title"><?= e($adv['title']) ?></h3>
<?php if (trim((string) $adv['text']) !== ''): ?>
          <p class="advantage__text"><?= e($adv['text']) ?></p>
<?php endif; ?>
<?php if ($isInspection): ?>
          <a class="link-arrow" href="/tehosmotr">Подробнее о техосмотре<?= icon('arrow-right') ?></a>
<?php endif; ?>
        </article>
      </li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>

<?php if ($stats): ?>
    <ul class="stats" data-stagger>
<?php foreach ($stats as $stat): ?>
<?php $num = trim((string) $stat['number']); ?>
      <li class="stat" data-reveal>
        <span class="stat__num"><span<?= preg_match('/^\d{1,7}$/', $num) ? ' data-count-to="' . e($num) . '"' : '' ?>><?= e($num) ?></span><?= e((string) $stat['suffix']) ?></span>
        <span class="stat__label"><?= e($stat['label']) ?></span>
      </li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
  </div>
</section>
