<?php

// app/Models/Part.php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Part extends Model
{
    use BelongsToCompany, HasFactory, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'specifications' => 'array',
        'quantity' => 'integer',
        'min_stock_level' => 'integer',
        'reorder_level' => 'integer',
        'extracted_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($part) {
            if (empty($part->part_number)) {
                $part->part_number = 'PART-'.strtoupper(uniqid());
            }
        });

        static::updating(function ($part) {
            // Update status based on quantity
            if ($part->quantity <= 0) {
                $part->status = 'OUT_OF_STOCK';
            } elseif ($part->quantity <= $part->min_stock_level) {
                $part->status = 'LOW_STOCK';
            } else {
                $part->status = 'IN_STOCK';
            }
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function extractedBy()
    {
        return $this->belongsTo(User::class, 'extracted_by');
    }

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function warehouseLocation(): BelongsTo
    {
        return $this->belongsTo(WarehouseLocation::class);
    }

    public function movements()
    {
        return $this->hasMany(PartMovement::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_part')
            ->withPivot('quantity', 'notes', 'added_by')
            ->withTimestamps();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    // Scopes
    public function scopeInStock($query)
    {
        return $query->where('status', 'IN_STOCK');
    }

    public function scopeLowStock($query)
    {
        return $query->where('status', 'LOW_STOCK');
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('status', 'OUT_OF_STOCK');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeByLocation($query, $location)
    {
        return $query->where('location', $location);
    }

    public function scopeNeedsReorder($query)
    {
        return $query->whereColumn('quantity', '<=', 'reorder_level');
    }

    // Methods
    public function addStock($quantity, $reason = null, $referenceType = null, $referenceId = null)
    {
        $before = $this->quantity;
        $this->increment('quantity', $quantity);

        $this->movements()->create([
            'movement_type' => 'IN',
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $this->quantity,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => auth('web')->id(),
            'company_id' => $this->company_id,
        ]);

        $this->updateStatusBasedOnQuantity();
    }

    public function removeStock($quantity, $reason = null, $referenceType = null, $referenceId = null)
    {
        if ($this->quantity < $quantity) {
            throw new \Exception('Insufficient stock');
        }

        $before = $this->quantity;
        $this->decrement('quantity', $quantity);

        $this->movements()->create([
            'movement_type' => 'OUT',
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $this->quantity,
            'reason' => $reason,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'created_by' => auth('web')->id(),
            'company_id' => $this->company_id,
        ]);

        $this->updateStatusBasedOnQuantity();
    }

    public function transfer($toLocation, $quantity, $reason = null)
    {
        if ($this->quantity < $quantity) {
            throw new \Exception('Insufficient stock for transfer');
        }

        $before = $this->quantity;
        $fromLocation = $this->location;

        $this->update([
            'location' => $toLocation,
            'quantity' => $this->quantity - $quantity,
        ]);

        $this->movements()->create([
            'movement_type' => 'TRANSFER',
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $this->quantity,
            'from_location' => $fromLocation,
            'to_location' => $toLocation,
            'reason' => $reason,
            'created_by' => auth('web')->id(),
            'company_id' => $this->company_id,
        ]);

        $this->updateStatusBasedOnQuantity();
    }

    protected function updateStatusBasedOnQuantity()
    {
        if ($this->quantity <= 0) {
            $this->status = 'OUT_OF_STOCK';
        } elseif ($this->quantity <= $this->min_stock_level) {
            $this->status = 'LOW_STOCK';
        } else {
            $this->status = 'IN_STOCK';
        }
        $this->saveQuietly();
    }

    public function isLowStock()
    {
        return $this->quantity <= $this->min_stock_level;
    }

    public function needsReorder()
    {
        return $this->quantity <= $this->reorder_level;
    }
}
