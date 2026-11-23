<?php
/**
 * Образец оформления: всё, из чего собран сайт, на одной странице.
 */

$colors = [
    ['Байкальский синий', '#0B4F8A', '--c-primary', 'Кнопки, ссылки, иконки'],
    ['Синий при наведении', '#0A4478', '--c-primary-hover', 'Кнопки при наведении'],
    ['Глубокий синий', '#073763', '--c-primary-strong', 'Тени, акценты'],
    ['Бирюза', '#1A8BB8', '--c-teal', 'Градиент первого экрана'],
    ['Лёд 50', '#F4F9FD', '--c-ice-50', 'Фон разделов'],
    ['Лёд 100', '#E8F3FB', '--c-ice-100', 'Плашки, фон фото'],
    ['Лёд 200', '#D3E8F7', '--c-ice-200', 'Выделение'],
    ['Лёд 300', '#AED3EE', '--c-ice-300', 'Рамки карточек при наведении'],
    ['Тёмно-синий текст', '#0A1F33', '--c-navy', 'Заголовки и текст'],
    ['Серо-синий текст', '#4A5D70', '--c-text-muted', 'Пояснения'],
    ['Рамки', '#D6E3EF', '--c-border', 'Линии и рамки'],
    ['Янтарный', '#FFAE1A', '--c-accent', 'Только главные кнопки'],
    ['Зелёный', '#1B7F4B', '--c-success', 'Успех'],
    ['Красный', '#C62828', '--c-danger', 'Ошибки в формах'],
];
$icons = Icons::names();
sort($icons);
?>
<style>
.sg h2 { margin: 56px 0 20px; font-size: 1.5rem; }
.sg h2:first-child { margin-top: 0; }
.sg-colors { list-style: none; display: grid; gap: 16px; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr)); }
.sg-color { display: grid; gap: 2px; align-content: start; font-size: var(--fs-sm); }
.sg-color__swatch { height: 72px; margin-bottom: 8px; border-radius: var(--r-md); border: 1px solid rgba(10, 31, 51, 0.1); }
.sg-color__name { font-weight: 700; color: var(--c-navy); }
.sg-color code { font-size: var(--fs-xs); color: var(--c-text-muted); }
.sg-color__usage { font-size: var(--fs-xs); color: var(--c-text-muted); }
.sg-type > p { margin: 0; }
.sg-label { margin-top: 18px !important; font-size: var(--fs-xs); font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: var(--c-text-muted); }
.sg-label:first-child { margin-top: 0 !important; }
.sg-h1, .sg-h2, .sg-h3 { font-weight: 800; color: var(--c-navy); line-height: var(--lh-tight); letter-spacing: -0.02em; }
.sg-h1 { font-size: var(--fs-h1); }
.sg-h2 { font-size: var(--fs-h2); }
.sg-h3 { font-size: var(--fs-h3); font-weight: 700; letter-spacing: -0.01em; }
.sg-row { display: flex; flex-wrap: wrap; align-items: center; gap: 12px; }
.sg-row--dark { margin-top: 16px; padding: 20px; border-radius: var(--r-lg); background: var(--hero-gradient); --c-focus: #FFD27A; }
.sg-form { display: grid; gap: 18px; max-width: 640px; }
.sg-icons { list-style: none; display: grid; gap: 10px; grid-template-columns: repeat(auto-fill, minmax(128px, 1fr)); }
.sg-icons li { display: grid; justify-items: center; gap: 8px; padding: 16px 8px; border: 1px solid var(--c-border); border-radius: var(--r-md); font-size: var(--fs-xs); color: var(--c-text-muted); text-align: center; overflow-wrap: anywhere; }
.sg-icons .icon { width: 28px; height: 28px; color: var(--c-primary); }
</style>
<section class="page-hero page-hero--compact" aria-labelledby="page-title">
  <div class="hero__bg"><?= inline_svg_file(APP_DIR . '/views/partials/hero-art.svg') ?></div>
  <div class="container page-hero__inner">
    <h1 class="page-hero__title" id="page-title">Образец оформления</h1>
    <p class="page-hero__lead">Цвета, шрифт, кнопки, поля и иконки сайта. Эта страница видна только при запуске на вашем компьютере.</p>
  </div>
</section>

<section class="section sg">
  <div class="container">
    <h2>Цвета</h2>
    <ul class="sg-colors">
<?php foreach ($colors as [$name, $hex, $var, $usage]): ?>
      <li class="sg-color">
        <span class="sg-color__swatch" style="background: var(<?= e($var) ?>)"></span>
        <span class="sg-color__name"><?= e($name) ?></span>
        <code><?= e($hex) ?></code>
        <span class="sg-color__usage"><?= e($usage) ?></span>
      </li>
<?php endforeach; ?>
    </ul>

    <h2>Шрифт Manrope</h2>
    <div class="sg-type card card--pad">
      <p class="sg-label">Заголовок 1</p>
      <p class="sg-h1">Аренда автобусов с водителем</p>
      <p class="sg-label">Заголовок 2</p>
      <p class="sg-h2">Для каких поездок</p>
      <p class="sg-label">Заголовок 3</p>
      <p class="sg-h3">Трансфер из аэропорта</p>
      <p class="sg-label">Основной текст</p>
      <p>Встретим гостей в аэропорту или на вокзале, поможем с багажом и довезём до гостиницы. Цифры: 0123456789 — 2&nbsp;500&nbsp;₽, № 40.</p>
      <p class="sg-label">Пояснение</p>
      <p class="field__hint">Точную стоимость подтвердит диспетчер.</p>
    </div>

    <h2>Кнопки</h2>
    <div class="sg-row">
      <a class="btn btn--primary btn--lg" href="#">Заказать транспорт<?= icon('arrow-right') ?></a>
      <a class="btn btn--primary" href="#">Главная кнопка</a>
      <a class="btn btn--secondary" href="#">Выбрать</a>
      <a class="btn btn--outline" href="#"><?= icon('phone') ?>Позвонить</a>
      <a class="btn btn--ghost" href="#">Подробнее</a>
      <button class="btn btn--secondary" type="button" disabled>Недоступно</button>
      <a class="btn btn--primary btn--sm" href="#">Маленькая</a>
    </div>
    <div class="sg-row sg-row--dark">
      <a class="btn btn--primary" href="#">На синем фоне</a>
      <a class="btn btn--glass" href="#"><?= icon('phone') ?>+7 952 622-11-44</a>
      <a class="btn btn--light" href="#">Светлая</a>
    </div>

    <h2>Поля формы</h2>
    <div class="sg-form card card--pad">
      <div class="field">
        <label class="field__label" for="sg-name">Ваше имя <span class="req" aria-hidden="true">*</span></label>
        <input class="input" id="sg-name" type="text" placeholder="Иван">
        <p class="field__hint">Подсказка под полем.</p>
      </div>
      <div class="field is-invalid">
        <label class="field__label" for="sg-phone">Телефон <span class="req" aria-hidden="true">*</span></label>
        <input class="input" id="sg-phone" type="tel" placeholder="+7 (___) ___-__-__" data-phone-mask aria-invalid="true" aria-describedby="sg-phone-error">
        <p class="field__error" id="sg-phone-error"><?= icon('alert-circle') ?><span>Введите номер полностью: +7 и ещё 10 цифр</span></p>
      </div>
      <div class="field-row">
        <div class="field">
          <label class="field__label" for="sg-date">Дата</label>
          <input class="input" id="sg-date" type="date">
        </div>
        <div class="field">
          <label class="field__label" for="sg-time">Время</label>
          <input class="input" id="sg-time" type="time">
        </div>
      </div>
      <div class="field-row">
        <div class="field">
          <label class="field__label" for="sg-pass">Пассажиров</label>
          <div class="stepper" data-stepper>
            <button class="stepper__btn" type="button" data-step="-1" aria-label="Меньше"><?= icon('minus') ?></button>
            <input class="stepper__input" id="sg-pass" type="number" min="1" max="999" value="25" inputmode="numeric">
            <button class="stepper__btn" type="button" data-step="1" aria-label="Больше"><?= icon('plus') ?></button>
          </div>
        </div>
        <div class="field">
          <label class="field__label" for="sg-select">Транспорт</label>
          <select class="select" id="sg-select"><option>Подберёт диспетчер</option><option>Автобус, до 45 мест</option></select>
        </div>
      </div>
      <div class="field">
        <label class="field__label" for="sg-comment">Комментарий</label>
        <textarea class="textarea" id="sg-comment" rows="3" placeholder="Например: будет багаж"></textarea>
      </div>
      <div class="segmented" role="radiogroup" aria-label="Как считать">
        <label class="segmented__option"><input type="radio" name="sg-mode" checked><span>Почасово</span></label>
        <label class="segmented__option"><input type="radio" name="sg-mode"><span>По километрам</span></label>
        <label class="segmented__option"><input type="radio" name="sg-mode"><span>Трансфер</span></label>
      </div>
      <div class="chips" role="group" aria-label="Фильтр">
        <button class="chip" type="button" aria-pressed="true">Все<span class="chip__count">3</span></button>
        <button class="chip" type="button" aria-pressed="false">до 8 мест<span class="chip__count">1</span></button>
        <button class="chip" type="button" aria-pressed="false">9–20 мест<span class="chip__count">1</span></button>
      </div>
      <label class="check"><input type="checkbox" checked> Флажок с подписью</label>
      <div class="sg-row">
        <button class="btn btn--secondary" type="button" data-demo-toast>Показать уведомление</button>
        <a class="btn btn--primary" href="#contacts" data-open-request>Открыть заявку</a>
      </div>
    </div>

    <h2>Карточки</h2>
    <ul class="services-grid">
      <li>
        <article class="card card--hover service-card">
          <span class="icon-tile"><?= icon('plane') ?></span>
          <h3 class="service-card__title">Трансфер</h3>
          <p class="service-card__text">Встретим в аэропорту или на вокзале и довезём до места.</p>
          <a class="service-card__action stretched" href="#">Заказать<?= icon('arrow-right') ?></a>
        </article>
      </li>
      <li>
        <article class="card advantage advantage--accent">
          <span class="icon-tile"><?= icon('clipboard-check') ?></span>
          <h3 class="advantage__title">Своя станция техосмотра</h3>
          <p class="advantage__text">Машины проходят проверку у нас же.</p>
        </article>
      </li>
      <li>
        <div class="card card--pad">
          <p><span class="badge"><?= icon('check') ?>Метка</span></p>
          <p class="field__hint">Карточка с отступами.</p>
        </div>
      </li>
    </ul>

    <h2>Иконки (<?= count($icons) ?>)</h2>
    <ul class="sg-icons">
<?php foreach ($icons as $name): ?>
      <li><?= icon($name) ?><span><?= e($name) ?></span></li>
<?php endforeach; ?>
    </ul>
  </div>
</section>
