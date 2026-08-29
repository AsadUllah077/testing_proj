<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LogisticsTransitStop extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (self $transitStop): void {
            $transitStop->name = trim((string) $transitStop->name);
            $transitStop->name_search = self::normalizeName($transitStop->name);
        });
    }

    public static function normalizeName(string $name): string
    {
        $asciiName = Str::lower(Str::ascii(trim($name)));

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $asciiName));
    }
}
