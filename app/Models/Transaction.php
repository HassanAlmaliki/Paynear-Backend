<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_wallet_id',
        'to_wallet_id',
        'original_amount',
        'commission_amount',
        'total_amount',
        'type',
        'status',
        'reference',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
        ];
    }

    public function fromWallet()
    {
        return $this->belongsTo(Wallet::class, 'from_wallet_id');
    }

    public function toWallet()
    {
        return $this->belongsTo(Wallet::class, 'to_wallet_id');
    }

    /**
     * Generate a unique reference number.
     */
    public static function generateReference(): string
    {
        do {
            $ref = 'TXN-' . strtoupper(Str::random(10));
        } while (static::where('reference', $ref)->exists());

        return $ref;
    }

    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if (empty($transaction->reference)) {
                $transaction->reference = static::generateReference();
            }
        });
    }
}
