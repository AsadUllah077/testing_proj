<?php

namespace App\Services;

use App\Models\BillAuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillAuditService
{
    public static function logCreation($bill, Request $request)
    {
        BillAuditLog::create([
            'bill_id' => $bill->id,
            'user_id' => Auth::id(),
            'action' => 'created',
            'new_data' => $bill->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public static function logUpdate($bill, $oldData, $newData, Request $request)
    {
        $changes = self::getChanges($oldData, $newData);

        BillAuditLog::create([
            'bill_id' => $bill->id,
            'user_id' => Auth::id(),
            'action' => 'updated',
            'old_data' => $oldData,
            'new_data' => $newData,
            'changes' => $changes, // Add this line to store changes
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    public static function logDeletion($bill, Request $request)
    {
        BillAuditLog::create([
            'bill_id' => $bill->id,
            'user_id' => Auth::id(),
            'action' => 'deleted',
            'old_data' => $bill->toArray(),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);
    }

    private static function getChanges($oldData, $newData)
    {
        $changes = [];

        foreach ($newData as $key => $newValue) {
            $oldValue = $oldData[$key] ?? null;

            // Handle date comparison and other special cases
            if ($oldValue instanceof \Carbon\Carbon) {
                $oldValue = $oldValue->toDateTimeString();
            }
            if ($newValue instanceof \Carbon\Carbon) {
                $newValue = $newValue->toDateTimeString();
            }

            // Compare values (strict comparison might be too strict for some cases)
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
