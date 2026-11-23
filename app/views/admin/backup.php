<?php
/**
 * Резервная копия: скачать архив и как восстановить.
 *
 * @var int   $dbSize
 * @var array $uploads
 * @var int   $lastBackup
 */
?>
<?= admin_head('Резервная копия', 'Архив со всем содержимым сайта: тексты, автопарк, цены, контакты, учётная запись и загруженные картинки. Храните его у себя на компьютере или в облаке.') ?>

<section class="card card--pad form-section backup-card" aria-labelledby="b-download">
  <h2 class="form-section__title" id="b-download">Скачать копию</h2>
  <dl class="facts">
    <div><dt>База данных</dt><dd><?= e(format_bytes($dbSize)) ?></dd></div>
    <div><dt>Картинки</dt><dd><?= (int) $uploads['count'] ?> <?= plural_ru((int) $uploads['count'], 'файл', 'файла', 'файлов') ?><?= $uploads['count'] > 0 ? ', ' . e(format_bytes((int) $uploads['bytes'])) : '' ?></dd></div>
    <div><dt>Последняя копия</dt><dd><?= $lastBackup > 0 ? e(date_ru(date('Y-m-d', $lastBackup))) . ' в ' . date('H:i', $lastBackup) : 'ещё не скачивалась' ?></dd></div>
  </dl>
  <form method="post" action="/admin/backup/download" data-download-form>
    <?= csrf_field() ?>
    <button class="btn btn--secondary btn--lg" type="submit"><?= icon('download') ?>Скачать резервную копию</button>
  </form>
  <p class="field__hint">Скачивайте копию раз в месяц и после больших изменений: новых машин, цен, фото.</p>
</section>

<section class="card card--pad form-section prose-admin" aria-labelledby="b-restore">
  <h2 class="form-section__title" id="b-restore">Как восстановить сайт из копии</h2>
  <ol>
    <li>Распакуйте архив на компьютере (двойной щелчок по файлу).</li>
    <li>В файловом менеджере хостинга откройте папку <b>data</b> и замените файл <b>site.sqlite</b> файлом из архива.</li>
    <li>Загрузите содержимое папки <b>uploads</b> из архива в папку <b>public_html/uploads</b> на хостинге.</li>
    <li>Откройте сайт и проверьте, что всё на месте. Войдите в админку с логином и паролем, которые были на момент копии.</li>
  </ol>
  <p>Если что-то пошло не так, напишите в поддержку хостинга — у них тоже обычно есть резервные копии за последние дни.</p>
</section>
