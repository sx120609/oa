<?php

namespace App\Util;

use App\Domain\ValidationException;

class Helpers
{
    public static function requireFields(array $data, array $fields): void
    {
        $missing = [];
        foreach ($fields as $field) {
            if (!array_key_exists($field, $data) || $data[$field] === '' || $data[$field] === null) {
                $missing[] = $field;
            }
        }
        if ($missing) {
            throw new ValidationException('Missing required fields', ['fields' => $missing]);
        }
    }

    public static function intVal($value, string $field): int
    {
        if (!is_numeric($value)) {
            throw new ValidationException("Field {$field} must be numeric");
        }
        return (int)$value;
    }

    public static function stringVal($value, string $field): string
    {
        if (!is_string($value) || trim($value) === '') {
            throw new ValidationException("Field {$field} must be a non-empty string");
        }
        return trim($value);
    }

    public static function boolVal($value, string $field): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if ($value === 'true' || $value === '1' || $value === 1) {
            return true;
        }
        if ($value === 'false' || $value === '0' || $value === 0) {
            return false;
        }
        throw new ValidationException("Field {$field} must be boolean");
    }
}
