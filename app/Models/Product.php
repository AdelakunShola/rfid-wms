<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'barcode',
        'description',
        'quantity',
        'price',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:2',
    ];

    /**
     * Get the RFID tags for the product
     */
    public function rfidTags()
    {
        return $this->hasMany(RfidTag::class);
    }

    /**
     * Get the scan logs for the product
     */
    public function scanLogs()
    {
        return $this->hasMany(ScanLog::class);
    }

    /**
     * Update product quantity based on operation
     */
    public function updateQuantity(string $operationType, int $quantity = 1)
    {
        switch ($operationType) {
            case 'receiving':
                $this->increment('quantity', $quantity);
                break;
            case 'picking':
            case 'shipping':
                $this->decrement('quantity', $quantity);
                break;
            case 'count':
                // Count operations don't change quantity, just log
                break;
        }

        return $this->fresh();
    }
}