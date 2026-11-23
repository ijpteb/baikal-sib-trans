<?php
/**
 * Карта Яндекса. Загружается только по нажатию «Показать карту»:
 * страница открывается быстрее, а сервис карт не получает данных о посетителе, пока карта не нужна.
 *
 * @var string $address
 * @var string $title
 */
?>
<div class="map" data-map data-src="<?= e(map_embed_url($address)) ?>" data-title="<?= e($title) ?>" data-reveal>
  <div class="map__placeholder">
    <span class="map__pin"><?= icon('map-pin') ?></span>
    <p class="map__address"><?= e($address) ?></p>
    <div class="map__actions">
      <button class="btn btn--secondary btn--sm" type="button" data-map-load><?= icon('map-pin') ?>Показать карту</button>
      <a class="btn btn--outline btn--sm" href="<?= e(map_link_url($address)) ?>" target="_blank" rel="noopener">Открыть в Яндекс Картах<?= icon('external-link') ?></a>
    </div>
    <p class="map__note">Карта загружается с сервиса «Яндекс Карты»</p>
  </div>
</div>
