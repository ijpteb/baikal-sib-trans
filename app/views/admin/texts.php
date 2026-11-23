<?php
/**
 * Тексты, логотип, значок, SEO, политика конфиденциальности — по вкладкам.
 *
 * @var array  $tabs
 * @var string $tab
 * @var array  $values
 * @var array  $errors
 */

$err = static fn (string $key): string => $errors[$key] ?? '';
$site = $values['site'];
$hero = $values['hero'];
$about = $values['about'];
$seo = $values['seo'];
$privacy = $values['privacy'];
?>
<?= admin_head('Тексты, логотип, SEO', 'Основные тексты сайта. Изменения видны на сайте сразу после сохранения.') ?>

<nav class="tabs" aria-label="Разделы настроек">
<?php foreach ($tabs as $key => $label): ?>
  <a class="tabs__link" href="/admin/texts?tab=<?= e($key) ?>"<?= $key === $tab ? ' aria-current="page"' : '' ?>><?= e($label) ?></a>
<?php endforeach; ?>
</nav>

<?php if ($errors): ?>
<?= admin_alert('error', 'Не сохранено: проверьте поля, отмеченные красным.') ?>
<?php endif; ?>

<form method="post" action="/admin/texts?tab=<?= e($tab) ?>" novalidate data-dirty-form>
  <?= csrf_field() ?>

<?php if ($tab === 'site'): ?>
  <section class="card card--pad form-section" aria-labelledby="t-name">
    <h2 class="form-section__title" id="t-name">Название</h2>
    <div class="form-grid">
      <?= af_input('name', 'Название сайта', $site['name'], ['required' => true, 'max' => 60, 'error' => $err('name'), 'hint' => 'В шапке, во вкладке браузера и в поиске.']) ?>
      <?= af_input('tagline', 'Подпись под названием', $site['tagline'], ['max' => 60, 'optional' => true, 'hint' => 'Мелким шрифтом рядом со знаком в шапке.']) ?>
      <?= af_input('company', 'Название организации', $site['company'], ['required' => true, 'max' => 120, 'wide' => true, 'error' => $err('company'), 'hint' => 'Внизу сайта и в политике конфиденциальности.']) ?>
      <?= af_textarea('footer_text', 'Текст внизу сайта', $site['footer_text'], ['max' => 300, 'rows' => 3, 'wide' => true, 'counter' => true]) ?>
    </div>
  </section>
  <section class="card card--pad form-section" aria-labelledby="t-images">
    <h2 class="form-section__title" id="t-images">Логотип и картинки</h2>
    <div class="form-grid">
      <?= af_image('logo', 'Логотип', $site['logo'], 'logo', ['optional' => true]) ?>
      <?= af_image('favicon', 'Значок сайта (на вкладке браузера)', $site['favicon'], 'favicon', ['optional' => true]) ?>
      <?= af_image('og_image', 'Картинка для ссылок в мессенджерах', $site['og_image'], 'og', ['optional' => true]) ?>
    </div>
  </section>

<?php elseif ($tab === 'hero'): ?>
  <section class="card card--pad form-section" aria-labelledby="t-hero">
    <h2 class="form-section__title" id="t-hero">Первый экран главной страницы</h2>
    <div class="form-grid">
      <?= af_textarea('title', 'Заголовок', $hero['title'], ['required' => true, 'max' => 120, 'rows' => 2, 'wide' => true, 'counter' => true, 'error' => $err('title'), 'hint' => 'Главная фраза сайта. В ней стоит оставить «аренда автобусов с водителем» и «Иркутск» — так сайт лучше находят.']) ?>
      <?= af_textarea('subtitle', 'Текст под заголовком', $hero['subtitle'], ['max' => 300, 'rows' => 3, 'wide' => true, 'counter' => true]) ?>
      <?= af_textarea('points', 'Короткие преимущества', $hero['points'], ['max' => 400, 'rows' => 4, 'wide' => true, 'hint' => 'До 4 строк, каждое — с новой строки. Показываются с галочками.']) ?>
      <?= af_image('photo', 'Фото на фоне', $hero['photo'], 'hero', ['optional' => true]) ?>
      <?= af_input('photo_alt', 'Что на фото', $hero['photo_alt'], ['max' => 150, 'wide' => true, 'optional' => true, 'hint' => 'Для незрячих посетителей и поисковиков. Например: «Белый автобус на берегу Байкала».']) ?>
    </div>
  </section>

<?php elseif ($tab === 'about'): ?>
  <section class="card card--pad form-section" aria-labelledby="t-about">
    <h2 class="form-section__title" id="t-about">Раздел «О компании»</h2>
    <p class="form-section__lead">Карточки преимуществ и цифры этого раздела редактируются в пунктах меню «Преимущества» и «Цифры о компании».</p>
    <div class="form-grid">
      <?= af_input('title', 'Заголовок раздела', $about['title'], ['max' => 80, 'wide' => true]) ?>
      <?= af_textarea('text', 'Текст о компании', $about['text'], ['max' => 1200, 'rows' => 6, 'wide' => true, 'counter' => true, 'hint' => 'Пустая строка — новый абзац.']) ?>
    </div>
  </section>

<?php elseif ($tab === 'seo'): ?>
  <section class="card card--pad form-section" aria-labelledby="t-url">
    <h2 class="form-section__title" id="t-url">Адрес сайта</h2>
    <div class="form-grid">
      <?= af_input('url', 'Адрес сайта', $site['url'], ['type' => 'url', 'inputmode' => 'url', 'max' => 120, 'wide' => true, 'placeholder' => 'https://baikalst.ru', 'error' => $err('url'), 'hint' => 'Укажите после подключения домена и HTTPS. Нужен поисковикам и для ссылок в мессенджерах.']) ?>
    </div>
  </section>
  <section class="card card--pad form-section" aria-labelledby="t-seo">
    <h2 class="form-section__title" id="t-seo">Как сайт выглядит в поиске</h2>
    <p class="form-section__lead">Заголовок — до 60–70 символов, описание — 120–160 символов. Их показывают Яндекс и Google в результатах поиска.</p>
    <div class="form-grid">
      <?= af_input('home_title', 'Главная — заголовок', $seo['home_title'], ['required' => true, 'max' => 120, 'wide' => true, 'counter' => true, 'error' => $err('home_title')]) ?>
      <?= af_textarea('home_description', 'Главная — описание', $seo['home_description'], ['max' => 300, 'rows' => 2, 'wide' => true, 'counter' => true]) ?>
      <?= af_input('tehosmotr_title', 'Техосмотр — заголовок', $seo['tehosmotr_title'], ['required' => true, 'max' => 120, 'wide' => true, 'counter' => true, 'error' => $err('tehosmotr_title')]) ?>
      <?= af_textarea('tehosmotr_description', 'Техосмотр — описание', $seo['tehosmotr_description'], ['max' => 300, 'rows' => 2, 'wide' => true, 'counter' => true]) ?>
      <?= af_input('privacy_title', 'Политика — заголовок', $seo['privacy_title'], ['required' => true, 'max' => 120, 'wide' => true, 'counter' => true, 'error' => $err('privacy_title')]) ?>
      <?= af_textarea('privacy_description', 'Политика — описание', $seo['privacy_description'], ['max' => 300, 'rows' => 2, 'wide' => true, 'counter' => true]) ?>
    </div>
  </section>
  <section class="card card--pad form-section" aria-labelledby="t-metrika">
    <h2 class="form-section__title" id="t-metrika">Яндекс Метрика и Вебмастер</h2>
    <div class="form-grid">
      <?= af_input('metrika_id', 'Номер счётчика Метрики', $seo['metrika_id'], ['inputmode' => 'numeric', 'max' => 12, 'optional' => true, 'error' => $err('metrika_id'), 'hint' => 'Только цифры, например 98765432. Пусто — Метрика выключена.']) ?>
      <?= af_input('yandex_verification', 'Код подтверждения Яндекс Вебмастера', $seo['yandex_verification'], ['max' => 200, 'optional' => true, 'error' => $err('yandex_verification'), 'hint' => 'Можно вставить весь тег из Вебмастера — сайт возьмёт из него код.']) ?>
      <?= af_check('cookie_notice', 'Показывать уведомление о cookie', !empty($seo['cookie_notice']), ['wide' => true, 'hint' => 'Нужно, когда включена Метрика: она запустится только после согласия посетителя.']) ?>
    </div>
  </section>

<?php elseif ($tab === 'privacy'): ?>
  <section class="card card--pad form-section" aria-labelledby="t-privacy">
    <h2 class="form-section__title" id="t-privacy">Политика конфиденциальности</h2>
    <p class="form-section__lead">Текст страницы <a href="/privacy" target="_blank" rel="noopener">/privacy</a>. Подстановки <code>{компания}</code>, <code>{сайт}</code>, <code>{адрес}</code>, <code>{email}</code>, <code>{телефон}</code> заменяются на ваши данные. Оформление: <code>## Заголовок</code>, <code>- пункт списка</code>, <code>**жирный**</code>.</p>
    <div class="form-grid">
      <?= af_textarea('text', 'Текст политики', $privacy['text'], ['rows' => 18, 'wide' => true, 'max' => 30000, 'error' => $err('text'), 'input_class' => 'textarea--code']) ?>
      <?= af_check('reviewed', 'Текст проверен', !empty($privacy['reviewed']), ['wide' => true, 'hint' => 'Отметьте, когда прочитаете текст (лучше вместе с юристом). Тогда пункт исчезнет из чек-листа.']) ?>
    </div>
  </section>
<?php endif; ?>

  <?= admin_savebar('Сохранить', $tab === 'privacy'
      ? '<button class="btn btn--ghost savebar__delete" type="submit" name="action" value="privacy_default" formnovalidate data-confirm="Вернуть исходный текст политики? Ваши правки в этом тексте пропадут.">' . icon('refresh') . 'Вернуть исходный текст</button>'
      : '') ?>
</form>
