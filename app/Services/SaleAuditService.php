<?php

namespace App\Services;

use App\Models\SaleAuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class SaleAuditService
{
    public static function log($sale, $action, $oldData = null, $newData = null, Request $request = null)
    {
        $changes = null;

        if ($action === 'updated' && $oldData && $newData) {
            $changes = self::getChanges($oldData, $newData);
        }

        return SaleAuditLog::create([
            'sale_id' => $sale->id,
            'user_id' => Auth::id(),
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
            'changes' => $changes,
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ]);
    }

    public static function logCreation($sale, Request $request = null)
    {
        return self::log($sale, 'created', null, $sale->toArray(), $request);
    }

    public static function logUpdate($sale, $oldData, $newData, Request $request = null)
    {
        return self::log($sale, 'updated', $oldData, $newData, $request);
    }

    public static function logDeletion($sale, Request $request = null)
    {
        return self::log($sale, 'deleted', $sale->toArray(), null, $request);
    }

    private static function getChanges($oldData, $newData)
    {
        $changes = [];

        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;

            if ($oldValue != $newValue) {   
                $changes[$key] = [
                    'from' => $oldValue,
                    'to' => $newValue
                ];
            }
        }

        return $changes;
    }
}
