<?php

declare(strict_types=1);

/*
 * sitemap.xml — список страниц для поисковиков.
 */

$contentUpdated = 0;
foreach (['vehicles', 'services', 'advantages', 'steps', 'reviews', 'partners', 'faq'] as $table) {
    $contentUpdated = max($contentUpdated, (int) Db::value('SELECT COALESCE(MAX(updated_at), 0) FROM ' . $table));
}
$inspectionUpdated = (int) Db::value('SELECT COALESCE(MAX(updated_at), 0) FROM inspection_categories');
$settingsUpdated = Settings::lastUpdate();
$fallback = (int) @filemtime(APP_DIR . '/lib/settings.php');

$pages = [
    ['/', max($contentUpdated, $settingsUpdated, $fallback), '1.0'],
    ['/tehosmotr', max($inspectionUpdated, Settings::updatedAt('inspection'), Settings::updatedAt('contacts'), $fallback), '0.8'],
    ['/privacy', max(Settings::updatedAt('privacy'), $fallback), '0.2'],
];

header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($pages as [$path, $updated, $priority]) {
    echo "  <url>\n";
    echo '    <loc>' . e(abs_url($path)) . "</loc>\n";
    if ($updated > 0) {
        echo '    <lastmod>' . date('Y-m-d', $updated) . "</lastmod>\n";
    }
    echo '    <priority>' . $priority . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
