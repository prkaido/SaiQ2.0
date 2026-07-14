<?php

namespace App\Support;

class AcademicText
{
    public static function name(?string $value): string
    {
        return self::clean($value) ?? '';
    }

    public static function nullableName(?string $value): ?string
    {
        return self::clean($value);
    }

    public static function upper(?string $value): string
    {
        $value = self::name($value);

        return function_exists('mb_strtoupper')
            ? mb_strtoupper($value, 'UTF-8')
            : strtoupper($value);
    }

    public static function cleanObject(?object $item, array $fields): ?object
    {
        if (!$item) {
            return $item;
        }

        foreach ($fields as $field) {
            if (property_exists($item, $field)) {
                $item->{$field} = self::nullableName($item->{$field});
            }
        }

        return $item;
    }

    public static function cleanCollection($items, array $fields)
    {
        return $items->map(fn ($item) => self::cleanObject($item, $fields));
    }

    private static function clean(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = self::repairMojibake(trim($value));
        $value = str_replace(["\xC2\xA0", "\r", "\n", "\t"], ' ', $value);
        $value = preg_replace('/\s+/u', ' ', $value) ?? $value;
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private static function repairMojibake(string $value): string
    {
        $value = self::replaceKnownMojibake($value);

        for ($i = 0; $i < 2; $i++) {
            if (!preg_match('/[ÃÂ]/u', $value)) {
                break;
            }

            $candidate = self::decodeMojibake($value);

            if ($candidate === null || self::mojibakeScore($candidate) >= self::mojibakeScore($value)) {
                break;
            }

            $value = $candidate;
        }

        return self::replaceKnownMojibake($value);
    }

    private static function decodeMojibake(string $value): ?string
    {
        if (!function_exists('mb_convert_encoding') || !function_exists('mb_check_encoding')) {
            return null;
        }

        foreach (['Windows-1252', 'ISO-8859-1'] as $encoding) {
            $candidate = @mb_convert_encoding($value, $encoding, 'UTF-8');

            if (is_string($candidate) && mb_check_encoding($candidate, 'UTF-8')) {
                return $candidate;
            }
        }

        return null;
    }

    private static function mojibakeScore(string $value): int
    {
        return preg_match_all('/[ÃÂ]|�|[\x{0080}-\x{009F}]/u', $value) ?: 0;
    }

    private static function replaceKnownMojibake(string $value): string
    {
        return strtr($value, [
            "\u{00C3}\u{00A1}" => "\u{00E1}",
            "\u{00C3}\u{00A9}" => "\u{00E9}",
            "\u{00C3}\u{00AD}" => "\u{00ED}",
            "\u{00C3}\u{00B3}" => "\u{00F3}",
            "\u{00C3}\u{00BA}" => "\u{00FA}",
            "\u{00C3}\u{00B1}" => "\u{00F1}",
            "\u{00C3}\u{00BC}" => "\u{00FC}",
            "\u{00C3}\u{0081}" => "\u{00C1}",
            "\u{00C3}\u{0089}" => "\u{00C9}",
            "\u{00C3}\u{008D}" => "\u{00CD}",
            "\u{00C3}\u{0093}" => "\u{00D3}",
            "\u{00C3}\u{009A}" => "\u{00DA}",
            "\u{00C3}\u{0091}" => "\u{00D1}",
            "\u{00C3}\u{009C}" => "\u{00DC}",
            "\u{00C3}\u{0160}" => "\u{00DA}",
            "\u{00C3}\u{2030}" => "\u{00C9}",
            "\u{00C3}\u{0080}" => "\u{00C0}",
            "\u{00C3}\u{0092}" => "\u{00D2}",
            "\u{00C2}\u{00A0}" => ' ',
            "\u{00C2}" => '',
        ]);
    }
}
