<?php

declare(strict_types=1);

/*
 * Страница-образец оформления: цвета, шрифт, кнопки, поля, иконки.
 * Открывается только на компьютере разработчика (php -S), на хостинге её нет.
 */

if (!is_debug()) {
    not_found();
}

$html = render_public_page('public/styleguide', [], [
    'title' => 'Образец оформления',
    'robots' => 'noindex, nofollow',
    'canonical' => null,
    'page' => 'styleguide',
]);

send_html($html);
