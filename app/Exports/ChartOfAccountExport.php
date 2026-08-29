<?php

namespace App\Exports;

use App\Models\ChartOfAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ChartOfAccountExport implements FromCollection, WithHeadings, WithMapping
{
    protected $filters;
    protected $ytdBalances = [];
    protected $companyId;

    public function __construct($filters = [])
    {
        $this->filters = $filters;
        $this->companyId = loggedCompanyId()['company_id'];

        // Eager-calculate YTD balances for all accounts to prevent N+1 queries during map()
        $this->ytdBalances = DB::table('transactions')
            ->where('company_id', $this->companyId)
            ->groupBy('account_id')
            ->select('account_id', DB::raw('SUM(debit) as total_debit'), DB::raw('SUM(credit) as total_credit'))
            ->get()
            ->keyBy('account_id')
            ->toArray();
    }

    public function collection()
    {
        $query = ChartOfAccount::with('parent');

        if (Schema::hasColumn('chart_of_accounts', 'company_id')) {
            $query->where('company_id', $this->companyId);
        }

        if (!empty($this->filters['account_no'])) {
            $query->where('account_number', 'LIKE', "%{$this->filters['account_no']}%");
        }

        if (!empty($this->filters['name'])) {
            $searchLower = strtolower($this->filters['name']);
            $query->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"]);
        }

        if (!empty($this->filters['parent_id'])) {
            $query->where('parent_id', $this->filters['parent_id']);
        }

        return $query->orderBy('account_number')->get();
    }

    public function headings(): array
    {
        return [
            'Code',
            'Name',
            'Type',
            'Description',
            'Tax Rate',
            'YTD Balance',
        ];
    }

    public function map($account): array
    {
        $balObj = $this->ytdBalances[$account->id] ?? null;
        $debit = $balObj ? (float) $balObj->total_debit : 0.0;
        $credit = $balObj ? (float) $balObj->total_credit : 0.0;
        $isDebitNormal = in_array($account->category, ['Assets', 'Expenses', 'COGS']);
        $ytdValue = $isDebitNormal ? ($debit - $credit) : ($credit - $debit);

        return [
            $account->account_number,
            $account->name,
            $account->category ?? $account->sub_type ?? $account->type,
            $account->description ?? '',
            $account->tax_code ?? 'Tax Exempt (0%)',
            $ytdValue,
        ];
    }
}
