<?php

namespace App\Enums\Concerns;

trait ResolvesFromValue
{
    public static function resolve(mixed $value): ?self
    {
        if ($value instanceof self) {
            return $value;
        }

        if (is_string($value) || is_int($value)) {
            return self::tryFrom($value);
        }

        return null;
    }
}
