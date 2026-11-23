<?php
/**
 * Карточка машины: фото (карусель, если фотографий несколько), места, удобства, цена, кнопка «Выбрать».
 *
 * @var array $v
 * @var int   $index
 */

$features = vehicle_features();
$photos = $v['photos'];
$count = count($photos);
$sizes = '(min-width: 1100px) 384px, (min-width: 720px) 50vw, 100vw';
$titleId = 'vehicle-' . (int) $v['id'] . '-title';
$placeholder = in_array($v['type'], ['car', 'minibus', 'bus'], true) ? $v['type'] : 'bus';
?>
<article class="card vehicle" aria-labelledby="<?= e($titleId) ?>">
  <div class="vehicle__media">
<?php if ($count > 1): ?>
    <div class="carousel" data-carousel role="group" aria-roledescription="карусель" aria-label="Фотографии: <?= e($v['name']) ?>">
      <div class="carousel__track" tabindex="0" aria-label="Листайте фотографии">
<?php foreach ($photos as $n => $photo): ?>
        <div class="carousel__slide" role="group" aria-roledescription="слайд" aria-label="<?= $n + 1 ?> из <?= $count ?>">
          <?= image_tag($photo, $sizes, ['alt' => $photo['alt'] !== '' ? $photo['alt'] : $v['name'] . ', фото ' . ($n + 1)]) ?>
        </div>
<?php endforeach; ?>
      </div>
      <button class="carousel__btn carousel__btn--prev" type="button" aria-label="Предыдущее фото" data-carousel-prev disabled><?= icon('chevron-left') ?></button>
      <button class="carousel__btn carousel__btn--next" type="button" aria-label="Следующее фото" data-carousel-next><?= icon('chevron-right') ?></button>
<?php if ($count <= 8): ?>
      <div class="carousel__dots">
<?php for ($n = 0; $n < $count; $n++): ?>
        <button class="carousel__dot" type="button" aria-label="Фото <?= $n + 1 ?>"<?= $n === 0 ? ' aria-current="true"' : '' ?> data-carousel-dot="<?= $n ?>"></button>
<?php endfor; ?>
      </div>
<?php else: ?>
      <span class="carousel__count" aria-hidden="true" data-carousel-count>1 / <?= $count ?></span>
<?php endif; ?>
    </div>
<?php elseif ($count === 1): ?>
    <?= image_tag($photos[0], $sizes, ['alt' => $photos[0]['alt'] !== '' ? $photos[0]['alt'] : $v['name']]) ?>
<?php else: ?>
    <img src="<?= e(asset('img/vehicles/' . $placeholder . '.svg')) ?>" width="800" height="600" alt="" loading="lazy" decoding="async">
<?php endif; ?>
  </div>

  <div class="vehicle__body">
<?php if ($v['type_label'] !== ''): ?>
    <p class="vehicle__type"><?= e($v['type_label']) ?></p>
<?php endif; ?>
    <h3 class="vehicle__name" id="<?= e($titleId) ?>"><?= e($v['name']) ?></h3>
<?php if ($v['seats'] > 0): ?>
    <p class="vehicle__seats"><?= icon('users') ?>до <?= (int) $v['seats'] ?> <?= plural_ru($v['seats'], 'пассажирского места', 'пассажирских мест', 'пассажирских мест') ?></p>
<?php endif; ?>
<?php if ($v['features'] || $v['custom_features']): ?>
    <ul class="vehicle__features" aria-label="Удобства">
<?php foreach ($v['features'] as $key): ?>
      <li class="vehicle__feature"><?= icon($features[$key]['icon']) ?><?= e($features[$key]['label']) ?></li>
<?php endforeach; ?>
<?php foreach ($v['custom_features'] as $custom): ?>
      <li class="vehicle__feature"><?= icon('check') ?><?= e($custom) ?></li>
<?php endforeach; ?>
    </ul>
<?php endif; ?>
<?php if (trim($v['description']) !== ''): ?>
    <p class="vehicle__desc"><?= e($v['description']) ?></p>
<?php endif; ?>
    <div class="vehicle__footer">
<?php if ($v['price_label'] !== null): ?>
      <p class="<?= e(classes('vehicle__price', $v['price_on_request'] ? 'vehicle__price--muted' : null)) ?>"><?= e($v['price_label']) ?></p>
<?php endif; ?>
      <a class="btn btn--secondary" href="#contacts" data-open-request data-vehicle="<?= (int) $v['id'] ?>">Выбрать<span class="visually-hidden">: <?= e($v['name']) ?></span></a>
    </div>
  </div>
</article>
