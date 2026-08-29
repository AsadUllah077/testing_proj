<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductScanLog extends Model
{
    protected $guarded = [];

    protected $casts = [
        'scanned_at' => 'datetime',
    ];

    // Location → Color mapping
    const LOCATION_COLOR_MAP = [
        'A' => 'Yellow',
        'B' => 'Green',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function scannedBy()
    {
        return $this->belongsTo(User::class, 'scanned_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the badge color class for the location.
     */
    public function getLocationBadgeClassAttribute(): string
    {
        return match ($this->location) {
            'A'     => 'bg-yellow-100 text-yellow-800 border border-yellow-300',
            'B'     => 'bg-green-100 text-green-800 border border-green-300',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get the badge color class for the color field.
     */
    public function getColorBadgeClassAttribute(): string
    {
        return match ($this->color) {
            'Yellow' => 'bg-yellow-400 text-yellow-900',
            'Green'  => 'bg-green-500 text-white',
            default  => 'bg-gray-300 text-gray-800',
        };
    }
}
