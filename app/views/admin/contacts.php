<?php
/**
 * Контакты: телефоны, почта, адреса, часы, карта, мессенджеры.
 *
 * @var array $contacts
 * @var array $messengers
 * @var array $errors
 * @var array $warnings
 */

$phones = array_values((array) ($contacts['phones'] ?? []));
while (count($phones) < 4) {
    $phones[] = ['number' => '', 'label' => '', 'primary' => false];
}
$primaryIndex = 0;
foreach ($phones as $i => $p) {
    if (!empty($p['primary'])) {
        $primaryIndex = $i;
        break;
    }
}
$messengerHints = [
    'max' => 'Ссылка вида https://max.ru/u/… — в приложении MAX: «Контакты» → «+» → «Пригласить по ссылке» → скопировать. MAX не умеет вставлять текст заявки сам: сайт скопирует текст, посетителю останется вставить его в чат.',
    'telegram' => 'Имя пользователя (@имя) или ссылка https://t.me/имя — личный аккаунт или бот, не канал. Текст заявки подставится в чат сам.',
    'whatsapp' => 'Номер с WhatsApp полностью: +7 900 000-00-00. Текст заявки подставится в чат сам.',
];
$messengerPlaceholders = ['max' => 'https://max.ru/u/…', 'telegram' => '@имя', 'whatsapp' => '+7 900 000-00-00'];
?>
<?= admin_head('Контакты и мессенджеры', 'Телефоны и адреса показываются в шапке, в разделе «Контакты», внизу сайта и на кнопках «Позвонить» и «Написать».') ?>

<?php if ($errors): ?>
<?= admin_alert('error', 'Не сохранено: проверьте поля, отмеченные красным.') ?>
<?php endif; ?>

<form method="post" action="/admin/contacts" novalidate data-dirty-form>
  <?= csrf_field() ?>

  <section class="card card--pad form-section" aria-labelledby="c-phones">
    <h2 class="form-section__title" id="c-phones">Телефоны</h2>
    <p class="form-section__lead">Основной телефон стоит в шапке сайта и на кнопке «Позвонить». Пустые строки не сохраняются.</p>
    <div class="phone-rows">
<?php foreach ($phones as $i => $phone): ?>
<?php $err = $errors['phones.' . $i] ?? ''; ?>
      <div class="phone-row<?= $err !== '' ? ' is-invalid' : '' ?>">
        <?= af_input('phones[' . $i . '][number]', 'Телефон ' . ($i + 1), $phone['number'], [
            'type' => 'tel', 'inputmode' => 'tel', 'max' => 40, 'autocomplete' => 'off',
            'placeholder' => '+7 900 000-00-00', 'error' => $err,
        ]) ?>
        <?= af_input('phones[' . $i . '][label]', 'Подпись', $phone['label'], [
            'max' => 40, 'placeholder' => 'Например: диспетчер', 'optional' => true,
        ]) ?>
        <label class="check phone-row__primary">
          <input type="radio" name="phone_primary" value="<?= $i ?>"<?= $i === $primaryIndex ? ' checked' : '' ?>>
          <span>Основной</span>
        </label>
      </div>
<?php endforeach; ?>
    </div>
  </section>

  <section class="card card--pad form-section" aria-labelledby="c-addr">
    <h2 class="form-section__title" id="c-addr">Почта и адреса</h2>
    <div class="form-grid">
      <?= af_input('email', 'Электронная почта', $contacts['email'] ?? '', ['type' => 'email', 'inputmode' => 'email', 'max' => 120, 'error' => $errors['email'] ?? '', 'wide' => true]) ?>
      <?= af_input('office_address', 'Адрес офиса', $contacts['office_address'] ?? '', ['max' => 200, 'required' => true, 'wide' => true, 'error' => $errors['office_address'] ?? '', 'hint' => 'С индексом: 664009, г. Иркутск, ул. Ширямова, д. 40, офис 306']) ?>
      <?= af_input('inspection_address', 'Адрес станции техосмотра', $contacts['inspection_address'] ?? '', ['max' => 200, 'wide' => true, 'hint' => 'Показывается на странице техосмотра. Если пусто — берётся адрес офиса.']) ?>
      <?= af_input('city', 'Город', $contacts['city'] ?? '', ['max' => 60]) ?>
      <?= af_input('region', 'Регион', $contacts['region'] ?? '', ['max' => 80]) ?>
    </div>
  </section>

  <section class="card card--pad form-section" aria-labelledby="c-hours">
    <h2 class="form-section__title" id="c-hours">Часы работы</h2>
    <div class="form-grid">
      <?= af_check('dispatcher_247', 'Диспетчер на связи круглосуточно', !empty($contacts['dispatcher_247']), ['wide' => true, 'hint' => 'Тогда рядом с телефоном будет написано «Круглосуточно», а поле ниже не нужно.']) ?>
      <?= af_input('dispatcher_hours', 'Часы работы диспетчера', $contacts['dispatcher_hours'] ?? '', ['max' => 120, 'placeholder' => 'Например: ежедневно с 7:00 до 22:00', 'wide' => true]) ?>
      <?= af_input('office_hours', 'Часы работы офиса', $contacts['office_hours'] ?? '', ['max' => 120, 'placeholder' => 'Например: пн–пт с 9:00 до 18:00', 'wide' => true, 'optional' => true]) ?>
    </div>
  </section>

  <section class="card card--pad form-section" id="map" aria-labelledby="c-map">
    <h2 class="form-section__title" id="c-map">Метка на карте</h2>
    <p class="form-section__lead">Без координат карта ищет офис по адресу — обычно этого хватает. Для точной метки: откройте <a href="https://yandex.ru/maps/" target="_blank" rel="noopener">Яндекс Карты</a>, нажмите на здание правой кнопкой мыши (на телефоне — долгое нажатие) и выберите «Что здесь?». Внизу появятся два числа, например <b>52.281, 104.297</b>. Можно вставить их оба в первое поле.</p>
    <div class="form-grid form-grid--3">
      <?= af_input('map_lat', 'Широта', $contacts['map_lat'] ?? '', ['inputmode' => 'decimal', 'max' => 40, 'placeholder' => '52.2812', 'error' => $errors['map_lat'] ?? '']) ?>
      <?= af_input('map_lng', 'Долгота', $contacts['map_lng'] ?? '', ['inputmode' => 'decimal', 'max' => 40, 'placeholder' => '104.2973', 'error' => $errors['map_lng'] ?? '']) ?>
      <?= af_select('map_zoom', 'Масштаб карты', (string) ($contacts['map_zoom'] ?? 16), [
          '14' => 'Район города', '15' => 'Несколько кварталов', '16' => 'Квартал (обычно)', '17' => 'Здание', '18' => 'Здание крупно',
      ]) ?>
    </div>
  </section>

  <section class="card card--pad form-section" id="messengers" aria-labelledby="c-msg">
    <h2 class="form-section__title" id="c-msg">Мессенджеры</h2>
    <p class="form-section__lead">Включённые мессенджеры появятся в окне «Написать»: посетитель заполняет заявку, и сайт открывает чат с готовым текстом.</p>
    <div class="messenger-rows">
<?php foreach (messenger_list() as $key => $meta): ?>
<?php $row = (array) ($messengers[$key] ?? []); ?>
      <div class="messenger-row">
        <div class="messenger-row__head">
          <span class="messenger-row__icon"><?= icon($meta['icon']) ?></span>
          <?= af_check('messengers[' . $key . '][enabled]', 'Показывать ' . $meta['label'], !empty($row['enabled'])) ?>
        </div>
        <?= af_input('messengers[' . $key . '][value]', 'Ссылка или контакт ' . $meta['label'], (string) ($row['value'] ?? ''), [
            'max' => 300,
            'placeholder' => $messengerPlaceholders[$key] ?? '',
            'hint' => $messengerHints[$key] ?? '',
            'error' => $errors['messengers.' . $key] ?? '',
            'warning' => $warnings['messengers.' . $key] ?? '',
            'spellcheck' => 'false',
            'attrs' => ['autocapitalize' => 'none'],
        ]) ?>
      </div>
<?php endforeach; ?>
    </div>
  </section>

  <?= admin_savebar('Сохранить контакты') ?>
</form>
