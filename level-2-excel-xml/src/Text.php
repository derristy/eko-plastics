<?php

namespace App;

class Text
{
    public static function normalize(string $s): string
    {
        $s = mb_strtolower($s);
        $s = str_replace(["\u{00a0}", '–', '—'], [' ', '-', '-'], $s);
        $s = preg_replace('/(\d),(\d)/u', '$1.$2', $s);

        return trim(preg_replace('/\s+/u', ' ', $s));
    }

    public static function models(string $s): array
    {
        $s = self::normalize($s);
        $models = [];

        if (preg_match_all('/([a-zа-яіїєґ]{3,})[\s-]?(\d{1,4})(?![\d.])/u', $s, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $models[] = $m[1] . $m[2];
            }
        }

        return array_values(array_unique($models));
    }

    public static function params(string $s): array
    {
        $s = self::normalize($s);
        $params = [];

        if (preg_match_all('/(\d+(?:\.\d+)?)\s*(кв\.?\s?м|м(?![а-яa-z])|квт|вт)/u', $s, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $value = (float) $m[1];
                $unit = 'm';
                if (str_starts_with($m[2], 'кв')) {
                    $unit = 'm2';
                } elseif ($m[2] === 'вт') {
                    $unit = 'w';
                } elseif ($m[2] === 'квт') {
                    $unit = 'w';
                    $value *= 1000;
                }
                $params[] = $unit . ':' . self::number($value);
            }
        }

        return array_values(array_unique($params));
    }

    public static function tailNumber(string $s): array
    {
        $s = self::normalize($s);
        if (!preg_match('/(\d+(?:\.\d+)?)\s*$/u', $s, $m)) {
            return [];
        }

        return self::paramsFromNumber((float) $m[1]);
    }

    public static function paramsFromNumber(float $n): array
    {
        $v = self::number($n);

        return ['m:' . $v, 'w:' . $v, 'm2:' . $v];
    }

    public static function number(float $v): string
    {
        return rtrim(rtrim(number_format($v, 2, '.', ''), '0'), '.');
    }
}
