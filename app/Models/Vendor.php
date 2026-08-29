<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\Searchable;

class Vendor extends Model
{
    use SoftDeletes;
    use HasFactory;
    use Sortable, BelongsToCompany, Searchable;

    protected $fillable = [
        'company_id',
        'name',
        'email',
        'phone',
        'address',
        'payment_terms_days',
    ];

    protected $casts = ['payment_terms_days' => 'integer'];

    protected $searchable = ['name', 'email', 'phone'];

    public $sortable = ['name', 'email', 'phone', 'address'];

    public function toGlobalSearchResult(): array
    {
        return [
            'title' => $this->name,
            'category' => 'Vendor',
            'url' => route('account.bill.vendors.edit', $this->id),
            'description' => $this->email ?? $this->phone,
        ];
    }

    // Company Relationship
    public function company()
    {
        return $this->belongsTo(Company::class);
    }
    public function logChange($event, $old = null, $new = null)
    {
        VendorLog::create([
            'vendor_id' => $this->id,
            'company_id'  => $this->company_id,
            'user_id'     => auth('web')->id(),
            'event'       => $event,
            'old_values'  => $old,
            'new_values'  => $new,
        ]);
    }
    public function logs()
    {
        return $this->hasMany(VendorLog::class);
    }
    public function bills()
    {
        return $this->hasMany(Bill::class);
    }
    public function getTotalRemainingBalanceAttribute()
    {
        return $this->bills()->where('approval_status', 'approved')->sum('remaining_amount');
    }

    public function getDisplayNameAttribute()
    {
        return $this->name;
    }
}
