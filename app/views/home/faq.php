<?php
/**
 * Частые вопросы — раскрывающийся список.
 *
 * @var array $faq
 */

$phone = primary_phone();
?>
<section class="section section--soft" id="faq" aria-labelledby="faq-title">
  <div class="container faq">
    <div class="faq__aside">
      <div data-reveal>
        <p class="eyebrow">Вопросы и ответы</p>
        <h2 class="section-title" id="faq-title">Частые вопросы</h2>
        <p class="section-lead">Коротко о том, как заказать транспорт и что входит в стоимость.</p>
      </div>
      <div class="card faq__help" data-reveal>
        <h3 class="faq__help-title">Не нашли ответ?</h3>
        <p>Позвоните или напишите — диспетчер ответит на вопросы и подберёт транспорт.</p>
<?php if ($phone !== null): ?>
        <a class="btn btn--outline btn--block" href="<?= e($phone['href']) ?>"><?= icon('phone') ?><?= e($phone['number']) ?></a>
<?php endif; ?>
        <a class="btn btn--primary btn--block" href="#contacts" data-open-request>Задать вопрос в заявке</a>
      </div>
    </div>

    <div class="accordion" data-accordion data-stagger>
<?php foreach ($faq as $i => $item): ?>
<?php $qid = 'faq-q-' . (int) $item['id']; $aid = 'faq-a-' . (int) $item['id']; ?>
      <div class="accordion__item" data-reveal>
        <h3 class="accordion__heading">
          <button class="accordion__trigger" type="button" id="<?= e($qid) ?>" aria-expanded="false" aria-controls="<?= e($aid) ?>">
            <span><?= e($item['question']) ?></span>
            <span class="accordion__icon" aria-hidden="true"></span>
          </button>
        </h3>
        <div class="accordion__panel" id="<?= e($aid) ?>" aria-labelledby="<?= e($qid) ?>">
          <div class="accordion__content"><div><?= text_to_html((string) $item['answer']) ?></div></div>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </div>
</section>
