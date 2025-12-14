<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScanLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'epc_code',
        'product_id',
        'operation_type',
        'quantity',
        'device_id',
        'scanned_by',
        'metadata',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'metadata' => 'array',
    ];

    /**
     * Get the product that was scanned
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the RFID tag that was scanned
     */
    public function rfidTag()
    {
        return $this->hasOne(RfidTag::class, 'epc_code', 'epc_code');
    }

    /**
     * Scope a query to only include scans of a given operation type
     */
    public function scopeOfOperationType($query, $operationType)
    {
        return $query->where('operation_type', $operationType);
    }

    /**
     * Scope a query to only include scans within a date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to only include today's scans
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }
}