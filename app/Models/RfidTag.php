<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class RfidTag extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'encoded_at' => 'datetime',
        'metadata' => 'array',
    ];

  

    /**
     * Relationship: tag → product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relationship: tag → scan logs
     */
    public function scanLogs()
    {
        return $this->hasMany(ScanLog::class, 'epc_code', 'epc_code');
    }

    /**
     * Generate a random EPC code (24 chars)
     */
    public static function generateEpcCode($productId = null)
    {
        // Generate SGTIN-96 format EPC (24 hex characters)
        $header = 'E2';
        $filter = '80';
        
        // Random company prefix and item reference
        $randomHex = function($length) {
            $hex = '';
            for ($i = 0; $i < $length; $i++) {
                $hex .= dechex(rand(0, 15));
            }
            return strtoupper($hex);
        };
        
        $companyPrefix = $randomHex(6);
        $itemRef = $randomHex(6);
        $serial = $randomHex(10);
        
        return $header . $filter . $companyPrefix . $itemRef . $serial;
    }
}
