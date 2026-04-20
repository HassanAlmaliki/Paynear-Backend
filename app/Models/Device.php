<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Device extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_number',
        'api_key',
        'merchant_id',
        'status',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    /**
     * Generate a unique serial number.
     */
    public static function generateSerialNumber(): string
    {
        do {
            $serial = 'PN-' . strtoupper(Str::random(8));
        } while (static::where('serial_number', $serial)->exists());

        return $serial;
    }

    /**
     * Generate a unique API key.
     */
    public static function generateApiKey(): string
    {
        do {
            $key = 'pnkey_' . Str::random(48);
        } while (static::where('api_key', $key)->exists());

        return $key;
    }

    protected static function booted(): void
    {
        static::creating(function (Device $device) {
            if (empty($device->serial_number)) {
                $device->serial_number = static::generateSerialNumber();
            }
            if (empty($device->api_key)) {
                $device->api_key = static::generateApiKey();
            }
        });
    }
}
