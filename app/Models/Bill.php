<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use App\Traits\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\Searchable;

class Bill extends Model
{
    use LogsActivity, SoftDeletes, Sortable, BelongsToCompany, Searchable;

    protected $guarded = [];

    protected $searchable = ['unique_no', 'invoice_no', 'vendor.name', 'date', 'total'];

    public function toGlobalSearchResult(): array
    {
        return [
            'title' => $this->unique_no,
            'category' => 'Bill',
            'url' => route('account.bill-invoice.edit', $this->id),
            'description' => $this->invoice_no . ' ' . $this->vendor->name,
        ];
    }

    protected $table = 'bills';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $casts = [
        'date' => 'date',
        'sub_total' => 'decimal:2',
        'discount_total' => 'decimal:2',
        'net_before_tax' => 'decimal:2',
        'tax' => 'decimal:2',
        'total' => 'decimal:2',
        'grand_total' => 'decimal:2',
        'withholding_tax' => 'decimal:2',
        'base_currency_total' => 'decimal:2',
        'exchange_rate' => 'decimal:8',
        'total_after_discount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'remaining_amount' => 'decimal:2',
        'vendor_invoice_date' => 'date',
        'posting_date' => 'date',
        'due_date' => 'date',
        'bill_approved_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public $defaultSort = 'date';

    public $sortable = ['date', 'ref_no', 'invoice_no', 'vendor_id', 'account_id', 'sub_total', 'total', 'total_after_discount', 'paid_amount', 'remaining_amount', 'created_by', 'updated_by'];

    // Relationships
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function accountingPeriod()
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    public function account()
    {
        return $this->belongsTo(ChartOfAccount::class, 'account_id');
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function billItems()
    {
        return $this->hasMany(BillItem::class);
    }

    public function attachments()
    {
        return $this->hasMany(BillAttachment::class);
    }

    public function auditLogs()
    {
        return $this->hasMany(BillAuditLog::class);
    }
    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'reference_id', 'id')->where('type', 'Bill');
    }

    public function paymentAllocations()
    {
        return $this->hasMany(VendorPaymentRunItem::class, 'bill_id');
    }

    public function debitNotes()
    {
        return $this->belongsToMany(DebitNote::class, 'debit_note_allocations')
            ->withPivot(['amount', 'allocation_date'])
            ->withTimestamps()
            ->select('debit_notes.*', 'debit_note_allocations.amount as allocated_amount');
    }

    public function debitNoteAllocations()
    {
        return $this->hasMany(DebitNoteAllocation::class);
    }

    public function deliveries()
    {
        return $this->hasMany(BillInvoiceDelivery::class);
    }

    public function versions()
    {
        return $this->hasMany(BillInvoiceVersion::class);
    }

    public function voidedBy()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function withholdingAccount()
    {
        return $this->belongsTo(ChartOfAccount::class, 'withholding_account_id');
    }

    public function isEditable(): bool
    {
        $basicEligibility = in_array($this->approval_status, ['draft', 'rejected'], true)
            && in_array($this->payment_status ?? 'unpaid', ['unpaid'], true)
            && (float) $this->paid_amount <= 0;

        return $basicEligibility && (
            ! $this->exists
            || (! $this->paymentAllocations()->exists() && ! $this->debitNoteAllocations()->exists())
        );
    }
}
