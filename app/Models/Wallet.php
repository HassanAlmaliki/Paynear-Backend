<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'owner_type',
        'balance',
        'currency',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
        ];
    }

    public function owner()
    {
        return $this->morphTo();
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }

    public function outgoingTransactions()
    {
        return $this->hasMany(Transaction::class, 'from_wallet_id');
    }

    public function incomingTransactions()
    {
        return $this->hasMany(Transaction::class, 'to_wallet_id');
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    /**
     * Get all transactions (both incoming and outgoing).
     */
    public function allTransactions()
    {
        return Transaction::where('from_wallet_id', $this->id)
            ->orWhere('to_wallet_id', $this->id);
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function credit(float $amount): void
    {
        $this->increment('balance', $amount);
    }

    public function debit(float $amount): void
    {
        $this->decrement('balance', $amount);
    }
}
