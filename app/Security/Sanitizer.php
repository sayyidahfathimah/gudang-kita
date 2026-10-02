<?php

namespace App\Security;

class Sanitizer
{
    public static function string(mixed $input): string
    {
        return htmlspecialchars(trim((string)$input), ENT_QUOTES, 'UTF-8');
    }

    public static function int(mixed $input): int
    {
        $v = filter_var($input, FILTER_VALIDATE_INT);
        return $v === false ? 0 : (int)$v;
    }

    public static function float(mixed $input): float
    {
        $v = filter_var($input, FILTER_VALIDATE_FLOAT);
        return $v === false ? 0.0 : (float)$v;
    }

    public static function email(mixed $input): string
    {
        $v = filter_var(trim((string)$input), FILTER_VALIDATE_EMAIL);
        return $v === false ? '' : $v;
    }

    public static function date(mixed $input): string
    {
        $s = trim((string)$input);
        $d = \DateTime::createFromFormat('Y-m-d', $s);
        return ($d && $d->format('Y-m-d') === $s) ? $s : date('Y-m-d');
    }
}
