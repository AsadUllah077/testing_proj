<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Throwable;

final class AccountingDate
{
    public static function fromIso(mixed $value, ?string $timezone = null): string
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', (string) $value, $timezone ?: config('app.timezone'));
        } catch (Throwable) {
            $date = false;
        }

        if (! $date || $date->format('Y-m-d') !== $value) {
            throw ValidationException::withMessages(['date' => 'The date must use the YYYY-MM-DD format.']);
        }

        return $date->toDateString();
    }
}
