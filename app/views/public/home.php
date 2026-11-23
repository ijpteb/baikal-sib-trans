<?php
/**
 * Главная страница. Каждый раздел — в отдельном файле app/views/home/*.php.
 *
 * @var array $services
 * @var array $vehicles
 * @var array $advantages
 * @var array $stats
 * @var array $steps
 * @var array $partners
 * @var array $reviews
 * @var array $faq
 * @var array $calcVehicles
 */
?>
<?= view('home/hero') ?>
<?= $services ? view('home/services', ['services' => $services]) : '' ?>
<?= view('home/fleet', ['vehicles' => $vehicles]) ?>
<?= $calcVehicles ? view('home/calculator', ['calcVehicles' => $calcVehicles]) : '' ?>
<?= view('home/about', ['advantages' => $advantages, 'stats' => $stats]) ?>
<?= $steps ? view('home/steps', ['steps' => $steps]) : '' ?>
<?= $partners ? view('home/partners', ['partners' => $partners]) : '' ?>
<?= $reviews ? view('home/reviews', ['reviews' => $reviews]) : '' ?>
<?= $faq ? view('home/faq', ['faq' => $faq]) : '' ?>
<?= view('home/contacts') ?>
