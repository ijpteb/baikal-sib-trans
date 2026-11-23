<?php
/**
 * Отзывы. Раздел показывается, только если в админке опубликован хотя бы один отзыв.
 *
 * @var array $reviews
 */
?>
<section class="section" id="reviews" aria-labelledby="reviews-title">
  <div class="container">
    <div class="section-head" data-reveal>
      <p class="eyebrow">Отзывы</p>
      <h2 class="section-title" id="reviews-title">Что говорят клиенты</h2>
    </div>
    <ul class="reviews" data-stagger<?= count($reviews) > 1 ? ' tabindex="0" aria-label="Отзывы, листайте в сторону"' : '' ?>>
<?php foreach ($reviews as $review): ?>
<?php
    $meta = array_filter([
        trim((string) $review['company']),
        !empty($review['review_date']) ? date_ru((string) $review['review_date']) : '',
    ]);
?>
      <li data-reveal>
        <figure class="card review">
          <?= icon('quote', 'review__quote') ?>
          <blockquote class="review__text"><?= text_to_html((string) $review['text']) ?></blockquote>
          <figcaption class="review__author">
            <span class="review__name"><?= e($review['author']) ?></span>
<?php if ($meta): ?>
            <span class="review__meta"><?= e(implode(' · ', $meta)) ?></span>
<?php endif; ?>
          </figcaption>
        </figure>
      </li>
<?php endforeach; ?>
    </ul>
  </div>
</section>
