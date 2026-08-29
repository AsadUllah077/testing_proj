<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    public static function bootLogsActivity()
    {
        static::created(function ($model) {
            $model->storeLog("created");
        });

        static::updated(function ($model) {
            $model->storeLog("updated", $model->getDirty());
        });

        static::deleting(function ($model) {
            // before delete — capture the record’s existing data
            $model->storeLog("deleted", $model->getOriginal());
        });
    }

    public function storeLog($action, $changes = null)
    {
        $logChanges = null;

        if ($changes) {
            $original = [];
            $new = [];

            foreach ($changes as $field => $newValue) {
                $original[$field] = $this->getOriginal($field);
                $new[$field] = $newValue;
            }

            $logChanges = [
                'old' => $original,
                'new' => $new,
            ];
        }

        $user = Auth::user();
        $userId = null;
        $employeeId = null;

        if ($user instanceof \App\Models\Employee) {
            $employeeId = $user->id;
        } else {
            $userId = Auth::id();
        }

        ActivityLog::create([
            'company_id' => $this->company_id ?? optional($user)->company_id,
            'loggable_type' => get_class($this),
            'loggable_id' => $this->id,
            'user_id' => $userId,
            'employee_id' => $employeeId,
            'action' => $action,
            'changes' => $logChanges,
            'created_at' => now(),
        ]);
    }

}
