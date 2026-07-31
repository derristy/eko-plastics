<?php

namespace App;

use RuntimeException;
use XMLReader;
use ZipArchive;

class XlsxRows
{
    private string $file;
    private string $sheetPath;
    private array $strings = [];

    public function __construct(string $file, int $sheet = 1)
    {
        if (!is_file($file)) {
            throw new RuntimeException("файл не найден: $file");
        }

        $this->file = $file;
        $this->sheetPath = 'xl/worksheets/sheet' . $sheet . '.xml';

        $zip = new ZipArchive();
        if ($zip->open($file) !== true) {
            throw new RuntimeException("не открывается: $file");
        }
        $hasStrings = $zip->locateName('xl/sharedStrings.xml') !== false;
        $exists = $zip->locateName($this->sheetPath) !== false;
        $zip->close();

        if (!$exists) {
            throw new RuntimeException("нет листа $sheet в $file");
        }
        if ($hasStrings) {
            $this->loadStrings();
        }
    }

    private function loadStrings(): void
    {
        $reader = new XMLReader();
        $reader->open('zip://' . $this->file . '#xl/sharedStrings.xml');

        $text = '';
        $inItem = false;
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                if ($reader->name === 'si') {
                    $inItem = true;
                    $text = '';
                } elseif ($reader->name === 't' && $inItem) {
                    $text .= $reader->readString();
                }
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'si') {
                $this->strings[] = $text;
                $inItem = false;
            }
        }
        $reader->close();
    }

    public function rows(): \Generator
    {
        $reader = new XMLReader();
        $reader->open('zip://' . $this->file . '#' . $this->sheetPath);

        $row = [];
        $index = 0;
        $rowNumber = 0;

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT) {
                if ($reader->name === 'row') {
                    $row = [];
                    $rowNumber = (int) $reader->getAttribute('r');
                    if ($reader->isEmptyElement) {
                        yield $rowNumber => [];
                    }
                } elseif ($reader->name === 'c') {
                    $ref = (string) $reader->getAttribute('r');
                    $type = (string) $reader->getAttribute('t');
                    $index = $ref !== '' ? self::columnIndex($ref) : $index;

                    if ($reader->isEmptyElement) {
                        $index++;
                        continue;
                    }

                    $value = null;
                    $depth = $reader->depth;
                    while ($reader->read()) {
                        if ($reader->nodeType === XMLReader::END_ELEMENT && $reader->depth === $depth) {
                            break;
                        }
                        if ($reader->nodeType === XMLReader::ELEMENT && ($reader->name === 'v' || $reader->name === 't')) {
                            $value = $reader->readString();
                        }
                    }

                    if ($value !== null) {
                        $row[$index] = $type === 's' ? ($this->strings[(int) $value] ?? '') : $value;
                    }
                    $index++;
                }
            } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'row') {
                yield $rowNumber => $row;
                $row = [];
            }
        }

        $reader->close();
    }

    private static function columnIndex(string $ref): int
    {
        $letters = rtrim($ref, '0123456789');
        $index = 0;
        foreach (str_split($letters) as $ch) {
            $index = $index * 26 + (ord($ch) - 64);
        }

        return $index - 1;
    }
}
