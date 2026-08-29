<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use App\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class Barcode extends Model
{
    use  SoftDeletes, LogsActivity, BelongsToCompany;

    protected $guarded = [];

    protected $casts = [
        'generated_at' => 'datetime',
        'used_at' => 'datetime',
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

    public function usable()
    {
        return $this->morphTo('used_for');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeUsed($query)
    {
        return $query->where('status', 'used');
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Accessors
    public function getBarcodeUrlAttribute()
    {
        if ($this->barcode_image_path && Storage::disk('public')->exists($this->barcode_image_path)) {
            return asset('storage/' . $this->barcode_image_path);
        }
        return null;
    }

    public function getBarcodeHtmlAttribute()
    {
        $url = $this->barcode_url;
        if ($url) {
            return '<img src="' . $url . '" alt="' . $this->unique_id . '" class="h-10 w-auto">';
        }
        return '<span class="text-xs text-gray-500">No barcode</span>';
    }
}
