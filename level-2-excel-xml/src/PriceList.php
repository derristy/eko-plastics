<?php

namespace App;

use PhpOffice\PhpSpreadsheet\IOFactory;

class PriceList
{
    private array $index = [];
    private int $positions = 0;

    public function __construct(string $file, array $skipSheets = ['головна'])
    {
        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(true);
        $book = $reader->load($file);

        foreach ($book->getWorksheetIterator() as $sheet) {
            if (in_array($sheet->getTitle(), $skipSheets, true)) {
                continue;
            }
            $this->readSheet($sheet->getTitle(), $sheet->toArray(null, true, false, false));
        }

        $book->disconnectWorksheets();
        unset($book);
    }

    private function readSheet(string $title, array $rows): void
    {
        foreach ($rows as $row) {
            $text = [];
            $numbers = [];
            foreach ($row as $value) {
                if ($value === null || $value === '') {
                    continue;
                }
                if (is_numeric($value)) {
                    $numbers[] = (float) $value;
                } else {
                    $text[] = (string) $value;
                }
            }
            if (!$text || !$numbers) {
                continue;
            }

            $prices = array_values(array_filter($numbers, fn($v) => $v >= 100 && $v <= 400000));
            if (!$prices) {
                continue;
            }

            $name = implode(' ', $text);
            $models = Text::models($name);
            if (!$models) {
                continue;
            }

            $params = Text::params($name);
            foreach ($numbers as $n) {
                if ($n > 0 && $n < 100000 && !in_array($n, $prices, true)) {
                    $params = array_merge($params, Text::paramsFromNumber($n));
                }
            }
            if (!$params) {
                continue;
            }

            $this->positions++;
            $item = [
                'sheet' => $title,
                'name' => $name,
                'price' => min($prices),
            ];

            foreach ($models as $model) {
                foreach (array_unique($params) as $param) {
                    $this->index[$model . '|' . $param][] = $item;
                }
            }
        }
    }

    public function find(array $models, array $params): ?array
    {
        foreach ($models as $model) {
            foreach ($params as $param) {
                $key = $model . '|' . $param;
                if (!isset($this->index[$key])) {
                    continue;
                }
                $found = $this->index[$key];
                $prices = array_unique(array_column($found, 'price'));
                if (count($prices) > 1) {
                    continue;
                }
                return $found[0];
            }
        }

        return null;
    }

    public function positions(): int
    {
        return $this->positions;
    }

    public function keys(): int
    {
        return count($this->index);
    }
}
