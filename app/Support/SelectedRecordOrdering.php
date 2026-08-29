<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class SelectedRecordOrdering
{
    public static function ids(Request $request, string $key): array
    {
        $raw = $request->input($key, []);
        $values = is_array($raw) ? $raw : explode(',', (string) $raw);

        return collect($values)
            ->map(fn($id) => (int) $id)
            ->filter(fn($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    public static function apply(Builder $query, array $ids, string $column = 'id'): Builder
    {
        if (empty($ids)) {
            return $query;
        }

        $case = collect($ids)
            ->map(fn($id, $index) => "WHEN {$column} = ? THEN {$index}")
            ->implode(' ');

        return $query->orderByRaw("CASE {$case} ELSE ? END", [...$ids, count($ids)]);
    }
}
