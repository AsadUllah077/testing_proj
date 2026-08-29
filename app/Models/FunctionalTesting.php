<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FunctionalTesting extends Model
{
      use  LogsActivity, BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'custom_tests' => 'array',
        'tested_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function tester()
    {
        return $this->belongsTo(Employee::class, 'tested_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Methods for test management
    public function addTest($testName, $testNotes, $result = null)
    {
        $tests = $this->custom_tests ?? [];

        $sanitizedName = strtolower(preg_replace('/[^a-z0-9]/', '_', $testName));

        $tests[$sanitizedName] = [
            'name' => $testName,
            'notes' => $testNotes,
            'result' => $result,
        ];

        $this->custom_tests = $tests;
        return $this;
    }

    public function updateTestResult($testKey, $result)
    {
        $tests = $this->custom_tests;

        if (isset($tests[$testKey])) {
            $tests[$testKey]['result'] = $result;
            $this->custom_tests = $tests;
        }

        return $this;
    }
}
