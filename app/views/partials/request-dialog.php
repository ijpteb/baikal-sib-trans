<?php
/**
 * Окно «Заявка на транспорт». Ничего не отправляет на сервер:
 * скрипт собирает из полей текст сообщения и предлагает отправить его в мессенджер или позвонить.
 *
 * @var string $page
 */

$types = trip_types();
$vehicles = Content::vehicles();
$today = date('Y-m-d');
?>
<dialog class="sheet" id="request-dialog" aria-labelledby="request-title" aria-describedby="request-subtitle">
  <div class="sheet__panel">
    <div class="sheet__grabber" aria-hidden="true"></div>
    <div class="sheet__header">
      <div>
        <h2 class="sheet__title" id="request-title">Заявка на транспорт</h2>
        <p class="sheet__subtitle" id="request-subtitle">Заполните то, что уже известно, — остальное уточнит диспетчер.</p>
      </div>
      <button class="sheet__close" type="button" aria-label="Закрыть" data-dialog-close><?= icon('close') ?></button>
    </div>

    <form class="sheet__body request-form" id="request-form" novalidate autocomplete="on">
<?php if ($types): ?>
      <fieldset>
        <legend>Тип поездки</legend>
        <div class="trip-types">
<?php foreach ($types as $i => $type): ?>
          <label><input type="radio" name="trip" value="<?= e($type) ?>"><span><?= e($type) ?></span></label>
<?php endforeach; ?>
          <label><input type="radio" name="trip" value="Другое"><span>Другое</span></label>
        </div>
      </fieldset>
<?php endif; ?>

      <div class="field-row">
        <div class="field" data-field="date">
          <label class="field__label" for="rq-date">Дата</label>
          <?= dt_input('date', 'rq-date', ['min' => $today, 'describedby' => 'rq-date-error']) ?>
          <p class="field__error" id="rq-date-error"><?= icon('alert-circle') ?><span></span></p>
        </div>
        <div class="field" data-field="time">
          <label class="field__label" for="rq-time">Время подачи</label>
          <?= dt_input('time', 'rq-time', ['step' => '300', 'describedby' => 'rq-time-error']) ?>
          <p class="field__error" id="rq-time-error"><?= icon('alert-circle') ?><span></span></p>
        </div>
      </div>

      <div class="field" data-field="from">
        <label class="field__label" for="rq-from">Откуда</label>
        <input class="input" type="text" id="rq-from" name="from" maxlength="200" placeholder="Адрес, аэропорт, вокзал" autocomplete="off">
      </div>
      <div class="field" data-field="to">
        <label class="field__label" for="rq-to">Куда</label>
        <input class="input" type="text" id="rq-to" name="to" maxlength="200" placeholder="Адрес или место назначения" autocomplete="off">
      </div>

      <div class="field-row">
        <div class="field" data-field="passengers">
          <label class="field__label" for="rq-passengers">Пассажиров</label>
          <div class="stepper" data-stepper>
            <button class="stepper__btn" type="button" data-step="-1" aria-label="Меньше пассажиров"><?= icon('minus') ?></button>
            <input class="stepper__input" type="number" id="rq-passengers" name="passengers" inputmode="numeric" min="1" max="999" step="1" placeholder="—" aria-describedby="rq-passengers-error">
            <button class="stepper__btn" type="button" data-step="1" aria-label="Больше пассажиров"><?= icon('plus') ?></button>
          </div>
          <p class="field__error" id="rq-passengers-error"><?= icon('alert-circle') ?><span></span></p>
        </div>
        <div class="field" data-field="vehicle">
          <label class="field__label" for="rq-vehicle">Транспорт</label>
          <select class="select" id="rq-vehicle" name="vehicle">
            <option value="">Подберёт диспетчер</option>
<?php foreach ($vehicles as $v): ?>
            <option value="<?= (int) $v['id'] ?>" data-seats="<?= (int) $v['seats'] ?>"><?= e($v['name']) ?></option>
<?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field" data-field="comment">
        <label class="field__label" for="rq-comment">Комментарий</label>
        <textarea class="textarea" id="rq-comment" name="comment" rows="3" maxlength="500" placeholder="Например: нужен обратный рейс в 18:00, будет багаж"></textarea>
      </div>

      <div class="field-row field-row--contacts">
        <div class="field" data-field="name">
          <label class="field__label" for="rq-name">Ваше имя <span class="req" aria-hidden="true">*</span></label>
          <input class="input" type="text" id="rq-name" name="name" maxlength="80" autocomplete="name" required aria-describedby="rq-name-error">
          <p class="field__error" id="rq-name-error"><?= icon('alert-circle') ?><span></span></p>
        </div>
        <div class="field" data-field="phone">
          <label class="field__label" for="rq-phone">Телефон <span class="req" aria-hidden="true">*</span></label>
          <input class="input" type="tel" id="rq-phone" name="phone" inputmode="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" maxlength="18" required data-phone-mask aria-describedby="rq-phone-error">
          <p class="field__error" id="rq-phone-error"><?= icon('alert-circle') ?><span></span></p>
        </div>
      </div>

      <input type="hidden" name="estimate" value="">
      <p class="request-form__privacy"><?= icon('lock') ?><span>Заявка не сохраняется на сайте. На следующем шаге вы сами отправите её в мессенджер или позвоните.</span></p>
    </form>

    <div class="sheet__footer">
      <button class="btn btn--primary btn--lg btn--block" type="submit" form="request-form">Продолжить<?= icon('arrow-right') ?></button>
    </div>
  </div>
</dialog>
