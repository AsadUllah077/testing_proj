<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\Searchable;

class Sale extends Model
{
    use BelongsToCompany, LogsActivity, SoftDeletes, Sortable, Searchable;

    protected $guarded = [];

    protected $searchable = ['unique_no', 'invoice_no', 'customer.name', 'date', 'total'];

    public function toGlobalSearchResult(): array
    {
        return [
            'title' => $this->unique_no,
            'category' => 'Sale',
            'url' => route('account.sale-invoice.edit', $this->id),
            'description' => $this->invoice_no . ' ' . $this->customer->name,
        ];
    }

    protected $table = 'sales';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'sub_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'net_before_tax' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'base_currency_total' => 'decimal:2',
        'exchange_rate' => 'decimal:8',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'total_after_discount' => 'decimal:2',
        'status' => 'integer',
        'credit_note_status' => 'integer',
        'due_date' => 'date',
        'approved_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public $defaultSort = 'date';

    public $sortable = ['date', 'ref_no', 'invoice_no', 'customer_id', 'account_id', 'sub_total', 'total', 'total_after_discount', 'paid_amount', 'remaining_amount', 'created_by', 'updated_by'];

    /**
     * Get the company that owns the sale.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the accounting period that owns the sale.
     */
    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    /**
     * Get the customer that owns the sale.
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the account that owns the sale.
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    /**
     * Get the currency that owns the sale.
     */
    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get the user who created the sale.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the user who last updated the sale.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function saleItems()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function attachments()
    {
        return $this->hasMany(SaleAttachments::class);
    }

    // In Sale model
    public function auditLogs()
    {
        return $this->hasMany(SaleAuditLog::class)->with('user')->orderBy('created_at', 'desc');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'reference_id', 'id')->where('type', 'Sale');
    }

    public function creditNotes()
    {
        return $this->belongsToMany(CreditNote::class, 'credit_note_allocations')
            ->withPivot(['amount', 'allocation_date'])
            ->withTimestamps()
            ->select('credit_notes.*', 'credit_note_allocations.amount as allocated_amount');
    }

    public function creditNoteAllocations()
    {
        return $this->hasMany(CreditNoteAllocation::class);
    }

    public function paymentAllocations()
    {
        return $this->hasMany(CustomerPaymentItem::class, 'sale_id');
    }

    public function deliveries()
    {
        return $this->hasMany(SaleInvoiceDelivery::class);
    }

    public function versions()
    {
        return $this->hasMany(SaleInvoiceVersion::class);
    }

    public function salesperson(): BelongsTo
    {
        return $this->belongsTo(User::class, 'salesperson_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function isEditable(): bool
    {
        return in_array($this->approval_status, ['draft', 'rejected'], true)
            && (float) $this->paid_amount <= 0
            && ! $this->voided_at;
    }
}
