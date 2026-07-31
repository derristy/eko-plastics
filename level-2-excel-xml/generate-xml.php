<?php

require __DIR__ . '/src/XlsxRows.php';

use App\XlsxRows;

const FLUSH_EVERY = 500;

const COL_ID = 0;
const COL_ARTICLE = 1;
const COL_NAME = 2;
const COL_AVAILABLE = 3;
const COL_PARENT = 4;
const COL_PRICE = 6;
const COL_OLD_PRICE = 7;
const COL_GALLERY = 8;
const COL_COUNTRY = 10;
const COL_PUBLISHED = 16;
const COL_DESCRIPTION = 19;
const COL_URI = 77;
const COL_FULL_URL = 91;

$source = $argv[1] ?? __DIR__ . '/output/import-updated.xlsx';
$target = $argv[2] ?? __DIR__ . '/output/feed.xml';

if (!is_file($source)) {
    fwrite(STDERR, "нет файла $source, сначала запустите update-prices.php\n");
    exit(1);
}

$started = microtime(true);

$categories = [];
$shopUrl = '';
$reader = new XlsxRows($source);
foreach ($reader->rows() as $number => $row) {
    if ($number === 1) {
        continue;
    }
    $category = (int) (float) ($row[COL_PARENT] ?? 0);
    if ($category > 0 && trim((string) ($row[COL_NAME] ?? '')) !== '') {
        $categories[$category] = true;
    }
    if ($shopUrl === '') {
        $host = parse_url(trim((string) ($row[COL_FULL_URL] ?? '')), PHP_URL_HOST);
        if ($host) {
            $shopUrl = 'https://' . $host;
        }
    }
}
ksort($categories);

if ($shopUrl === '') {
    fwrite(STDERR, "в выгрузке нет ссылок на товары, не из чего взять домен магазина\n");
    exit(1);
}

$shopName = parse_url($shopUrl, PHP_URL_HOST);
printf("магазин: %s, категорий: %d\n", $shopName, count($categories));

$xml = new XMLWriter();
$xml->openUri($target);
$xml->setIndent(true);
$xml->startDocument('1.0', 'UTF-8');
$xml->startElement('yml_catalog');
$xml->writeAttribute('date', date('Y-m-d H:i'));
$xml->startElement('shop');
$xml->writeElement('name', $shopName);
$xml->writeElement('company', $shopName);
$xml->writeElement('url', $shopUrl);

$xml->startElement('currencies');
$xml->startElement('currency');
$xml->writeAttribute('id', 'UAH');
$xml->writeAttribute('rate', '1');
$xml->endElement();
$xml->endElement();

$xml->startElement('categories');
foreach (array_keys($categories) as $id) {
    $xml->startElement('category');
    $xml->writeAttribute('id', (string) $id);
    $xml->text('Категорія ' . $id);
    $xml->endElement();
}
$xml->endElement();

$offers = 0;
$skipped = 0;

$xml->startElement('offers');
$reader = new XlsxRows($source);
foreach ($reader->rows() as $number => $row) {
    if ($number === 1) {
        continue;
    }

    $name = trim((string) ($row[COL_NAME] ?? ''));
    $price = (float) ($row[COL_PRICE] ?? 0);

    if ($name === '' || $price <= 0 || (float) ($row[COL_PUBLISHED] ?? 0) <= 0) {
        $skipped++;
        continue;
    }

    $offers++;
    $id = (int) (float) ($row[COL_ID] ?? 0);
    $oldPrice = (float) ($row[COL_OLD_PRICE] ?? 0);
    $category = (int) (float) ($row[COL_PARENT] ?? 0);
    $article = trim((string) ($row[COL_ARTICLE] ?? ''));
    $country = trim((string) ($row[COL_COUNTRY] ?? ''));
    $uri = trim((string) ($row[COL_URI] ?? ''));
    $description = (string) ($row[COL_DESCRIPTION] ?? '');

    $xml->startElement('offer');
    $xml->writeAttribute('id', (string) ($id ?: $offers));
    $xml->writeAttribute('available', (float) ($row[COL_AVAILABLE] ?? 0) > 0 ? 'true' : 'false');

    $xml->writeElement('name', $name);
    $xml->writeElement('price', number_format($price, 2, '.', ''));
    if ($oldPrice > $price) {
        $xml->writeElement('oldprice', number_format($oldPrice, 2, '.', ''));
    }
    $xml->writeElement('currencyId', 'UAH');
    if ($category) {
        $xml->writeElement('categoryId', (string) $category);
    }
    if ($article !== '') {
        $xml->writeElement('vendorCode', $article);
    }
    if ($country !== '') {
        $xml->writeElement('vendor', $country);
    }
    $url = trim((string) ($row[COL_FULL_URL] ?? ''));
    if ($url === '' && $uri !== '') {
        $url = $shopUrl . '/' . ltrim($uri, '/');
    }
    if ($url !== '') {
        $xml->writeElement('url', $url);
    }

    $gallery = preg_split('/[|,;\s]+/', (string) ($row[COL_GALLERY] ?? ''), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    foreach (array_slice(array_unique($gallery), 0, 10) as $picture) {
        $xml->writeElement('picture', $picture);
    }

    if ($description !== '') {
        $xml->startElement('description');
        $xml->writeCdata(mb_substr(strip_tags($description), 0, 4000));
        $xml->endElement();
    }

    $xml->endElement();

    if ($offers % FLUSH_EVERY === 0) {
        $xml->flush();
        printf("  товаров: %d, память %.0f МБ\r", $offers, memory_get_usage(true) / 1048576);
    }
}
$xml->endElement();

$xml->endElement();
$xml->endElement();
$xml->endDocument();
$xml->flush();

printf("\nтоваров в фиде: %d, пропущено: %d\n", $offers, $skipped);
printf("файл: %s (%.1f МБ)\n", $target, filesize($target) / 1048576);
printf("время: %.1f c, пиковая память: %.0f МБ\n", microtime(true) - $started, memory_get_peak_usage(true) / 1048576);
