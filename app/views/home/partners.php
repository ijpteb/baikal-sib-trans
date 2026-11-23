<?php
/**
 * «Нам доверяют»: логотипы партнёров (серые, при наведении — цветные).
 *
 * @var array $partners
 */
?>
<section class="section section--tight section--soft" id="partners" aria-labelledby="partners-title">
  <div class="container">
    <div class="section-head section-head--center" data-reveal>
      <h2 class="section-title" id="partners-title">Нам доверяют</h2>
    </div>
    <ul class="partners" data-stagger>
<?php foreach ($partners as $partner): ?>
<?php
    $logo = is_array($partner['logo']) && !empty($partner['logo']['file']) ? $partner['logo'] : null;
    $url = trim((string) $partner['url']);
    $isLink = $url !== '' && preg_match('#^https?://#i', $url);
    $tag = $isLink ? 'a' : 'div';
?>
      <li data-reveal>
        <<?= $tag ?> class="partner"<?= $isLink ? ' href="' . e($url) . '" target="_blank" rel="noopener nofollow"' : '' ?>>
<?php if ($logo !== null): ?>
          <?= partner_logo_tag($logo, (string) $partner['name']) ?>
<?php else: ?>
          <span class="partner__name"><?= e($partner['name']) ?></span>
<?php endif; ?>
        </<?= $tag ?>>
      </li>
<?php endforeach; ?>
    </ul>
  </div>
</section>
