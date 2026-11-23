<?php

declare(strict_types=1);

/*
 * robots.txt — правила для поисковых роботов.
 */

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: public, max-age=3600');

echo "User-agent: *\n";
echo "Disallow: /admin\n";
echo "Disallow: /styleguide\n";
echo "\n";
echo 'Sitemap: ' . abs_url('/sitemap.xml') . "\n";
