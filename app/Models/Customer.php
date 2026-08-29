<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\Searchable;

class Customer extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes, Sortable, Searchable;

    protected $guarded = [];

    protected $casts = [
        'credit_limit' => 'decimal:2',
        'payment_terms_days' => 'integer',
    ];

    protected $searchable = ['name', 'email', 'phone'];

    public $sortable = ['name', 'email', 'phone', 'address'];

    public function toGlobalSearchResult(): array
    {
        return [
            'title' => $this->name,
            'category' => 'Customer',
            'url' => route('account.sale.customers.edit', $this->id),
            'description' => $this->email ?? $this->phone,
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function logChange($event, $old = null, $new = null)
    {
        CustomerLog::create([
            'customer_id' => $this->id,
            'company_id' => $this->company_id,
            'user_id' => auth('web')->id(),
            'event' => $event,
            'old_values' => $old,
            'new_values' => $new,
        ]);
    }

    public function logs()
    {
        return $this->hasMany(CustomerLog::class);
    }

    public function getTotalRemainingBalanceAttribute()
    {
        return $this->sales()->where('approval_status', 'approved')->sum('remaining_amount');
    }

    /**
     * Get all sales for this customer
     */
    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}
