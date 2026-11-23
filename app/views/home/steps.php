<?php
/**
 * «Как мы работаем»: шаги от заявки до поездки.
 *
 * @var array $steps
 */
?>
<section class="section" id="steps" aria-labelledby="steps-title">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <p class="eyebrow">Как заказать</p>
      <h2 class="section-title" id="steps-title">Как мы работаем</h2>
      <p class="section-lead">От заявки до поездки — <?= count($steps) ?> <?= plural_ru(count($steps), 'простой шаг', 'простых шага', 'простых шагов') ?>.</p>
    </div>
    <ol class="steps" style="--steps: <?= max(1, min(6, count($steps))) ?>" data-stagger>
<?php foreach ($steps as $i => $step): ?>
      <li class="step" data-reveal>
        <span class="step__num" aria-hidden="true"><?= $i + 1 ?></span>
        <h3 class="step__title"><?= e($step['title']) ?></h3>
<?php if (trim((string) $step['text']) !== ''): ?>
        <p class="step__text"><?= e($step['text']) ?></p>
<?php endif; ?>
      </li>
<?php endforeach; ?>
    </ol>
    <div class="steps__cta" data-reveal>
      <a class="btn btn--primary btn--lg" href="#contacts" data-open-request>Оставить заявку<?= icon('arrow-right') ?></a>
    </div>
  </div>
</section>
