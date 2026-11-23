<?php
/**
 * Страница техосмотра: тексты, часы, документы, категории и цены.
 *
 * @var array $page
 * @var array $categories
 * @var array $errors
 */
?>
<?= admin_head('Техосмотр', 'Тексты и цены страницы <a href="/tehosmotr" target="_blank" rel="noopener">«Техосмотр»</a>.') ?>

<?php if ($errors): ?>
<?= admin_alert('error', 'Не сохранено: проверьте поля, отмеченные красным.') ?>
<?php endif; ?>

<form method="post" action="/admin/inspection" novalidate data-dirty-form>
  <?= csrf_field() ?>

  <section class="card card--pad form-section" aria-labelledby="i-texts">
    <h2 class="form-section__title" id="i-texts">Тексты страницы</h2>
    <div class="form-grid">
      <?= af_input('title', 'Заголовок', $page['title'] ?? '', ['required' => true, 'max' => 120, 'wide' => true, 'error' => $errors['title'] ?? '']) ?>
      <?= af_textarea('intro', 'Вступление', $page['intro'] ?? '', ['max' => 600, 'rows' => 3, 'wide' => true, 'counter' => true]) ?>
      <?= af_textarea('hours', 'Часы работы станции', $page['hours'] ?? '', ['max' => 300, 'rows' => 3, 'wide' => true, 'placeholder' => "Пн–пт: 9:00–18:00\nСб: 10:00–15:00\nВс: выходной", 'hint' => 'Каждый день или период — с новой строки. Если пусто, на сайте будет «Уточняйте по телефону».']) ?>
      <?= af_textarea('booking_text', 'Как записаться', $page['booking_text'] ?? '', ['max' => 300, 'rows' => 2, 'wide' => true]) ?>
    </div>
  </section>

  <section class="card card--pad form-section" aria-labelledby="i-docs">
    <h2 class="form-section__title" id="i-docs">Документы для техосмотра</h2>
    <div class="form-grid">
      <?= af_textarea('documents', 'Список документов', $page['documents'] ?? '', ['max' => 1500, 'rows' => 5, 'wide' => true, 'hint' => 'Каждый документ — с новой строки.']) ?>
      <?= af_check('documents_reviewed', 'Список документов проверен', !empty($page['documents_reviewed']), ['wide' => true, 'hint' => 'Список составлен заранее. Уточните его и поставьте галочку — пункт исчезнет из чек-листа.']) ?>
    </div>
  </section>

  <section class="card card--pad form-section" id="categories" aria-labelledby="i-cats">
    <h2 class="form-section__title" id="i-cats">Категории и цены</h2>
    <p class="form-section__lead">Цена в рублях, только цифры. Если цену не указать, на сайте будет «Цену уточняйте».</p>
    <div class="category-rows">
<?php foreach ($categories as $cat): ?>
<?php $id = (int) $cat['id']; $base = 'cats[' . $id . ']'; ?>
      <div class="category-row<?= !empty($cat['is_visible']) ? '' : ' category-row--hidden' ?>">
        <span class="category-row__code" aria-hidden="true"><?= e($cat['code']) ?></span>
        <div class="category-row__fields">
          <?= af_input($base . '[title]', 'Категория ' . $cat['code'], $cat['title'], ['max' => 80, 'error' => $errors['cats.' . $id] ?? '']) ?>
          <?= af_input($base . '[description]', 'Пояснение', $cat['description'], ['max' => 200]) ?>
          <?= af_input($base . '[price]', 'Цена, ₽', admin_price_value($cat['price']), ['inputmode' => 'numeric', 'max' => 12, 'placeholder' => '—', 'input_class' => 'input--money', 'error' => $errors['cats.' . $id . '.price'] ?? '']) ?>
          <div class="category-row__checks">
            <?= af_check($base . '[price_from]', 'Цена «от»', !empty($cat['price_from'])) ?>
            <?= af_check($base . '[is_visible]', 'На сайте', !empty($cat['is_visible'])) ?>
          </div>
        </div>
      </div>
<?php endforeach; ?>
    </div>
  </section>

  <?= admin_savebar('Сохранить') ?>
</form>
