<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class LogisticsFreightAgent extends Model
{
    use BelongsToCompany;

    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(function (self $agent): void {
            $agent->name = trim((string) $agent->name);
            $agent->name_search = self::normalizeName($agent->name);
            $agent->email = filled($agent->email) ? trim((string) $agent->email) : null;
            $agent->contact = filled($agent->contact) ? trim((string) $agent->contact) : null;
        });
    }

    public static function normalizeName(string $name): string
    {
        $asciiName = Str::lower(Str::ascii(trim($name)));

        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $asciiName));
    }
}
