<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Withdrawal extends Model
{
    use HasFactory;

    protected $fillable = [
        'agent_id',
        'wallet_id',
        'requested_amount',
        'commission_amount',
        'total_deducted_amount',
        'commission_type',
        'commission_value',
        'status',
        'verification_code',
        'firebase_session_info',
        'expires_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_amount' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'total_deducted_amount' => 'decimal:2',
            'commission_value' => 'decimal:2',
            'expires_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function agent()
    {
        return $this->belongsTo(Agent::class);
    }

    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    public function commissionTransaction()
    {
        return $this->hasOne(AgentCommissionTransaction::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
