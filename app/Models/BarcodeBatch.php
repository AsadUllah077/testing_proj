<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BarcodeBatch extends Model
{
    use SoftDeletes, BelongsToCompany, LogsActivity;

    protected $guarded = [];

    protected $casts = [
        'generated_ids' => 'array',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function barcodes()
    {
        return $this->hasMany(Barcode::class, 'container_id', 'container_id')
                    ->where('category_id', $this->category_id)
                    ->whereBetween('sequence_number', [$this->start_sequence, $this->end_sequence]);
    }

    // Methods
    public function generateBatchNumber()
    {
        return 'BATCH-' . strtoupper(uniqid());
    }

    public function getCategoryNameAttribute()
    {
        return $this->category->name;
    }
}
