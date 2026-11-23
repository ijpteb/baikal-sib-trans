<?php
/**
 * Уведомление о cookie. Показывается, только если в админке указан номер счётчика Яндекс Метрики.
 * Сам счётчик подключает скрипт сайта — после согласия посетителя (или сразу, если уведомление выключено).
 *
 * @var string $id
 */

if (!setting('seo.cookie_notice', true)) {
    return;
}
?>
<div class="cookie-note" id="cookie-note" role="region" aria-label="Уведомление о cookie" hidden>
  <p>Мы используем cookie и сервис «Яндекс Метрика», чтобы понимать, как сделать сайт удобнее. <a href="/privacy">Подробнее</a></p>
  <div class="cookie-note__actions">
    <button class="btn btn--secondary btn--sm" type="button" data-cookie-choice="yes">Хорошо</button>
    <button class="btn btn--ghost btn--sm" type="button" data-cookie-choice="no">Отказаться</button>
  </div>
</div>
