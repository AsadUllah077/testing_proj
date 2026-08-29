<?php

use App\Models\ChartOfAccount;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

if (!function_exists('handleTryCatch')) {
    function handleTryCatch(callable $function, ...$params)
    {
        try {
            return $function(...$params);
        } catch (\Throwable $e) {
            // Optionally log the error
            Log::error($e->getMessage(), ['trace' => $e->getTraceAsString()]);

            // You can also return a standard response if you want
            return response()->json(['message' => [$e->getMessage()], 'success' => false], 500);
        }
    }
}


if (!function_exists('companies')) {
    function companies()
    {
        return Company::latest()->get();
    }
}

if (!function_exists('loggedCompanyId')) {
    function loggedCompanyId()
    {
        $user = Auth::guard('web')->user() ?: Auth::user();
        $company = $user?->company;

        return [
            'company_name' => $company->name ?? '',
            'company_id' => $company?->id ?? $user?->company_id,
        ];
    }
}

if (!function_exists('dateFormat')) {
    function dateFormat($date = null)
    {
        if (!$date) {
            return date('d-m-Y');
        }

        return date('d-m-Y', strtotime($date));
    }
}

if (!function_exists('numberFormat')) {
    function numberFormat($value)
    {
        return number_format((float)$value, 2, '.', ',');
    }
}

if (!function_exists('byOrderCoa')) {

    function byOrderCoa()
    {
        $order = [
            'Assets' => 1,
            'Liabilities' => 2,
            'Equity' => 3,
            'Revenue' => 4,
            'Expenses' => 5,
        ];

        $companyId = loggedCompanyId()['company_id'] ?? null;

        $accounts = ChartOfAccount::select(
            'id',
            'account_number',
            'name',
            'category',
            'parent_id',
            'type',
            'tax_code'
        )
            ->forCompany($companyId)
            ->orderBy('account_number')
            ->get();

        return $accounts->sortBy(function ($item) use ($order) {
            return $order[$item->category] ?? 999;
        })->values();
    }
}

if (!function_exists('separateByGroup')) {
    function separateByGroup($current, $previous, $colspan, $bgColor = '#F2F3F4')
    {
        if (!$previous) return;

        $curr = explode(' - ', $current->unique_no)[0];
        $prev = explode(' - ', $previous->unique_no)[0];

        if ($curr !== $prev) {
            echo '<tr><td colspan="' . $colspan . '" style="height: 10px; background:' . $bgColor . ';"></td></tr>';
        }
    }
}

if (!function_exists('debug')) {
    function debug($data)
    {
        echo "<pre>";
        print_r($data->toArray());
        echo "</pre>";
    }
}

if (!function_exists('dateParse')) {
    function dateParse($date)
    {
        return Carbon::parse($date)->format('d M, Y');
    }
}

if (!function_exists('awarePaginate')) {
    function awarePaginate($items, $perPage = 20)
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = collect($items);

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage),
            $items->count(),
            $perPage,
            $page,
            [
                'path' => request()->url(),
                'query' => request()->query(),
            ]
        );
    }
}

if (!function_exists('resolveDateRange')) {
    function resolveDateRange(Request $request, string $default = 'month'): array
    {
        // Fiscal year filter takes priority
        if ($request->fiscal_year) {
            $period = \App\Models\AccountingPeriod::find($request->fiscal_year);
            if ($period) {
                return [
                    $period->start_date->format('Y-m-d'),
                    $period->end_date->format('Y-m-d'),
                ];
            }
        }

        // by default data shows by fiscal year when no filter is applied
        if (!$request->fiscal_year && !$request->from_date && !$request->to_date && !$request->date_range) {
            $period = \App\Models\AccountingPeriod::findPeriodForDate(now()->format('Y-m-d'));

            if ($period) {
                return [
                    $period->start_date->format('Y-m-d'),
                    $period->end_date->format('Y-m-d'),
                ];
            }
        }
        // by default data shows by fiscal year when no filter is applied

        $from = $request->from_date ?: $request->start_date;
        $to   = $request->to_date ?: $request->end_date;
        $dateRange = $request->date_range ?: $request->per_date;

        if ($from || $to) {
            return [
                $from ?: Carbon::createFromDate(1900, 1, 1)->toDateString(),
                $to   ?: Carbon::now()->toDateString()
            ];
        }

        $default = $dateRange ?: $default;

        if ($default === 'last_7_days') {
            return [
                Carbon::now()->subDays(7)->toDateString(),
                Carbon::now()->toDateString()
            ];
        }

        if ($default === 'last_30_days') {
            return [
                Carbon::now()->subDays(30)->toDateString(),
                Carbon::now()->toDateString()
            ];
        }

        if ($default === 'last_90_days') {
            return [
                Carbon::now()->subDays(90)->toDateString(),
                Carbon::now()->toDateString()
            ];
        }


        if ($default === 'unlimited_before') {
            return [
                Carbon::createFromDate(1900, 1, 1)->toDateString(),
                Carbon::now()->toDateString()
            ];
        }

        if ($default === 'unlimited_after') {
            return [
                Carbon::now()->toDateString(),
                Carbon::createFromDate(2100, 1, 1)->toDateString()
            ];
        }

        if (str_contains($default, 'years_range_')) {
            $years = (int) str_replace('years_range_', '', $default);

            return [
                Carbon::now()->subYears($years)->toDateString(),
                Carbon::now()->addYears($years)->toDateString()
            ];
        }

        return match ($default) {
            'today' => [
                Carbon::today()->toDateString(),
                Carbon::today()->toDateString()
            ],

            'week' => [
                Carbon::now()->startOfWeek()->toDateString(),
                Carbon::now()->endOfWeek()->toDateString()
            ],

            'month' => [
                Carbon::now()->startOfMonth()->toDateString(),
                Carbon::now()->endOfMonth()->toDateString()
            ],

            'year' => [
                Carbon::now()->startOfYear()->toDateString(),
                Carbon::now()->endOfYear()->toDateString()
            ],

            default => [
                Carbon::now()->startOfMonth()->toDateString(),
                Carbon::now()->endOfMonth()->toDateString()
            ]
        };
    }
}

if (!function_exists('randomDateLastYear')) {
    function randomDateLastYear()
    {
        return Carbon::now()->subDays(rand(0, 365))->format('Y-m-d');
    }
}

if (! function_exists('currency')) {
    function currency(?string $key = null)
    {
        $currency = auth('web')->user()?->company?->currency;

        if (! $currency) {
            return null;
        }

        // Return specific field
        if ($key) {
            return $currency->{$key} ?? null;
        }

        // Return full currency object
        return $currency;
    }
}

if (!function_exists('formatAmount')) {
    function formatAmount($value)
    {
        return $value > 0 ? numberFormat($value) : '---';
    }
}

if (!function_exists('resolveActivityFieldValue')) {
    function resolveActivityFieldValue($key, $value)
    {
        static $cache = [];

        if (is_array($value)) {
            if ($value === []) {
                return '---';
            }

            $encodedValue = json_encode(
                $value,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE
            );

            return $encodedValue !== false ? $encodedValue : '---';
        }

        if (empty($value)) {
            return '---';
        }
        if (!is_numeric($value)) {
            return $value;
        }

        // Create a unique cache key
        $cacheKey = $key.'_'.$value;
        if (isset($cache[$cacheKey])) {
            return $cache[$cacheKey];
        }

        $resolved = $value;

        try {
            if (in_array($key, ['employee_id', 'assign_to', 'assigned_to', 'reporting_to', 'user_id', 'added_by'])) {
                if ($key === 'user_id' || $key === 'added_by') {
                    $user = \App\Models\User::find($value);
                    $resolved = $user ? ($user->fullName ?? $user->username) : $value;
                } else {
                    $employee = \App\Models\Employee::find($value);
                    $resolved = $employee ? ($employee->full_name ?? $employee->name) : $value;
                }
            } elseif ($key === 'department_id') {
                $dept = \App\Models\Department::find($value);
                $resolved = $dept ? $dept->name : $value;
            } elseif ($key === 'hrm_job_id') {
                $job = \App\Models\HrmJob::find($value);
                $resolved = $job ? $job->job_title : $value;
            } elseif ($key === 'location_id' || $key === 'warehouse_location_id') {
                $loc = \App\Models\WarehouseLocation::find($value);
                $resolved = $loc ? ($loc->location_name ?? $loc->name) : $value;
            } elseif ($key === 'product_id') {
                $prod = \App\Models\Product::find($value);
                $resolved = $prod ? ($prod->unique_id ?? $prod->name) : $value;
            } elseif ($key === 'category_id' || $key === 'scrap_category_id') {
                $cat = ($key === 'scrap_category_id')
                    ? \App\Models\ScrapCategory::find($value)
                    : \App\Models\ProductCategory::find($value);
                $resolved = $cat ? ($cat->name ?? $cat->category_name) : $value;
            }
        } catch (\Exception $e) {
            // Fallback to ID if anything goes wrong
        }

        $cache[$cacheKey] = $resolved;

        return $resolved;
    }
}

if (!function_exists('get_index_url')) {
    /**
     * Get the stored index URL from the session, or fallback to the route's default URL.
     *
     * @param string $routeName The name of the index route (e.g., 'account.sale-invoice.index')
     * @param mixed $parameters Optional parameters for the fallback route
     * @return string
     */
    function get_index_url($routeName, $parameters = [])
    {
        return session('index_url_' . $routeName, route($routeName, $parameters));
    }
}
