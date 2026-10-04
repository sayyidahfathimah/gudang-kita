<?php

namespace App\Validation;

final class Validator
{
    public static function required(array $data, array $fields): array
    {
        $e = [];
        foreach ($fields as $f) {
            if (!isset($data[$f]) || trim((string)$data[$f]) === '') {
                $e[$f] = 'Field wajib diisi.';
            }
        }
        return $e;
    }
    public static function email(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    public static function enum(string $value, array $allowed): bool
    {
        return in_array($value, $allowed, true);
    }
    public static function dateOrder(string $start, string $target): bool
    {
        return $target >= $start;
    }
}
