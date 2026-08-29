<?php

namespace App\View\Components;

use App\Models\ChartOfAccount;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class ChartAccountBankSelect extends Component
{
    public $accounts;
    public $name;
    public $selected;
    public $id;
    /**
     * Create a new component instance.
     */
    public function __construct($name = 'account_id', $selected = null, $id = null)
    {
        $this->name = $name;
        $this->selected = $selected;
        $this->id = $id;

        $this->accounts = ChartOfAccount::forCompany(loggedCompanyId()['company_id'] ?? null)
            ->where('type', 'Account')
            ->where('sub_type', 'BANK')
            ->orderBy('account_number')
            ->get();
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View|Closure|string
    {
        return view('components.chart-account-bank-select');
    }
}
