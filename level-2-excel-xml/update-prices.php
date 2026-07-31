<?php

require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/src/Text.php';
require __DIR__ . '/src/PriceList.php';

use App\PriceList;
use App\Text;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;

const CHUNK = 500;
const COL_ARTICLE = 1;
const COL_NAME = 2;
const COL_PRICE = 6;
const COL_OLD_PRICE = 7;
const PARAM_COLUMNS = [37, 40, 38, 25, 23];

$importFile = __DIR__ . '/input/Import.xlsx';
$priceFile = __DIR__ . '/input/price-2024.xlsx';
$resultFile = __DIR__ . '/output/import-updated.xlsx';

$started = microtime(true);

echo "читаю прайс\n";
$price = new PriceList($priceFile);
printf("  позиций: %d, ключей: %d\n", $price->positions(), $price->keys());

echo "читаю импорт\n";
$reader = IOFactory::createReader('Xlsx');
$reader->setReadDataOnly(true);
$book = $reader->load($importFile);
$sheet = $book->getActiveSheet();

$lastRow = $sheet->getHighestDataRow();
$lastColumn = $sheet->getHighestDataColumn();
printf("  строк: %d, колонок: %s\n", $lastRow, $lastColumn);

$changed = $same = $missing = 0;

for ($start = 2; $start <= $lastRow; $start += CHUNK) {
    $end = min($start + CHUNK - 1, $lastRow);
    $rows = $sheet->rangeToArray('A' . $start . ':' . $lastColumn . $end, null, true, false, false);

    foreach ($rows as $offset => $row) {
        $rowNumber = $start + $offset;
        $name = (string) ($row[COL_NAME] ?? '');
        if ($name === '') {
            continue;
        }

        $models = Text::models($name);

        // параметры из названия надёжнее: в выгрузке встречаются строки,
        // где в колонке площади стоит значение от другого товара
        $params = array_merge(Text::params($name), Text::tailNumber($name));
        foreach (PARAM_COLUMNS as $column) {
            if (!empty($row[$column])) {
                $params = array_merge($params, Text::params((string) $row[$column]));
            }
        }

        $match = $models && $params ? $price->find($models, array_unique($params)) : null;
        if ($match === null) {
            $missing++;
            continue;
        }

        $current = (float) ($row[COL_PRICE] ?? 0);
        $new = (float) $match['price'];

        $sheet->setCellValue([COL_PRICE + 1, $rowNumber], $new);
        $sheet->setCellValue([COL_OLD_PRICE + 1, $rowNumber], round($new * 1.1, 2));

        $equal = abs($current - $new) < 0.01;
        $equal ? $same++ : $changed++;

        $sheet->getStyle('A' . $rowNumber . ':' . $lastColumn . $rowNumber)
            ->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()
            ->setRGB($equal ? 'C6EFCE' : 'FFEB9C');
    }

    unset($rows);
}

if (!is_dir(__DIR__ . '/output')) {
    mkdir(__DIR__ . '/output', 0755, true);
}

IOFactory::createWriter($book, 'Xlsx')->save($resultFile);
$book->disconnectWorksheets();
unset($book);

printf(
    "\nобновлено: %d, без изменений: %d, нет в прайсе: %d\n",
    $changed,
    $same,
    $missing
);
printf("файл: %s\n", $resultFile);
printf("время: %.1f c, память: %.0f МБ\n", microtime(true) - $started, memory_get_peak_usage(true) / 1048576);
