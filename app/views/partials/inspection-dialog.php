<?php
/**
 * Окно «Запись на техосмотр». Как и заявка на транспорт, ничего не сохраняет на сервере —
 * собирает текст и предлагает отправить его в мессенджер или позвонить.
 *
 * @var array $categories
 */

$today = date('Y-m-d');
?>
<dialog class="sheet" id="inspection-dialog" aria-labelledby="inspection-title" aria-describedby="inspection-subtitle">
  <div class="sheet__panel">
    <div class="sheet__grabber" aria-hidden="true"></div>
    <div class="sheet__header">
      <div>
        <h2 class="sheet__title" id="inspection-title">Запись на техосмотр</h2>
        <p class="sheet__subtitle" id="inspection-subtitle">Укажите категорию и удобную дату — мы подтвердим время.</p>
      </div>
      <button class="sheet__close" type="button" aria-label="Закрыть" data-dialog-close><?= icon('close') ?></button>
    </div>

    <form class="sheet__body request-form" id="inspection-form" novalidate>
      <div class="field" data-field="category">
        <label class="field__label" for="in-category">Категория транспорта</label>
        <select class="select" id="in-category" name="category">
          <option value="">Не знаю, подскажите</option>
<?php foreach ($categories as $cat): ?>
          <option value="<?= e($cat['code'] . ' — ' . $cat['title']) ?>"><?= e($cat['code'] . ' — ' . $cat['title']) ?></option>
<?php endforeach; ?>
        </select>
      </div>
      <div class="field-row">
        <div class="field" data-field="date">
          <label class="field__label" for="in-date">Желаемая дата</label>
          <input class="input" type="date" id="in-date" name="date" min="<?= e($today) ?>" aria-describedby="in-date-error">
          <p class="field__error" id="in-date-error"><?= icon('alert-circle') ?><span></span></p>
        </div>
        <div class="field" data-field="time">
          <label class="field__label" for="in-time">Время</label>
          <input class="input" type="time" id="in-time" name="time" step="900">
        </div>
      </div>
      <div class="field" data-field="car">
        <label class="field__label" for="in-car">Марка и модель</label>
        <input class="input" type="text" id="in-car" name="car" maxlength="120" placeholder="Например: Toyota Camry" autocomplete="off">
      </div>
      <div class="field" data-field="comment">
        <label class="field__label" for="in-comment">Комментарий</label>
        <textarea class="textarea" id="in-comment" name="comment" rows="2" maxlength="600"></textarea>
      </div>
      <div class="field-row field-row--contacts">
        <div class="field" data-field="name">
          <label class="field__label" for="in-name">Ваше имя <span class="req" aria-hidden="true">*</span></label>
          <input class="input" type="text" id="in-name" name="name" maxlength="80" autocomplete="name" required aria-describedby="in-name-error">
          <p class="field__error" id="in-name-error"><?= icon('alert-circle') ?><span></span></p>
        </div>
        <div class="field" data-field="phone">
          <label class="field__label" for="in-phone">Телефон <span class="req" aria-hidden="true">*</span></label>
          <input class="input" type="tel" id="in-phone" name="phone" inputmode="tel" autocomplete="tel" placeholder="+7 (___) ___-__-__" maxlength="18" required data-phone-mask aria-describedby="in-phone-error">
          <p class="field__error" id="in-phone-error"><?= icon('alert-circle') ?><span></span></p>
        </div>
      </div>
      <p class="request-form__privacy"><?= icon('lock') ?><span>Данные не сохраняются на сайте. На следующем шаге вы сами отправите сообщение в мессенджер или позвоните.</span></p>
    </form>

    <div class="sheet__footer">
      <button class="btn btn--primary btn--lg btn--block" type="submit" form="inspection-form">Продолжить<?= icon('arrow-right') ?></button>
    </div>
  </div>
</dialog>
