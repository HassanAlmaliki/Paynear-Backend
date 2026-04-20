<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class Agent extends Authenticatable implements FilamentUser
{
    use HasFactory;

    protected $fillable = [
        'name',
        'username',
        'password',
        'phone',
        'commission_type',
        'commission_value',
        'status',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Interact with the agent's phone to always ensure it starts with +967.
     */
    protected function phone(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            set: function (?string $value) {
                if (blank($value)) {
                    return $value;
                }
                if (str_starts_with($value, '+967')) {
                    return $value;
                }
                if (str_starts_with($value, '0')) {
                    return '+967' . substr($value, 1);
                }
                if (str_starts_with($value, '7')) {
                    return '+967' . $value;
                }
                return $value;
            },
        );
    }

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'commission_value' => 'decimal:2',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'agent') {
            return $this->status === 'active';
        }
        return false;
    }

    public function withdrawals()
    {
        return $this->hasMany(Withdrawal::class);
    }

    public function commissionTransactions()
    {
        return $this->hasMany(AgentCommissionTransaction::class);
    }

    public function agentWallet()
    {
        return $this->hasOne(AgentWallet::class);
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    public function getOrCreateAgentWallet(): AgentWallet
    {
        return $this->agentWallet ?? $this->agentWallet()->create([
            'balance' => 0,
            'total_earned' => 0,
            'currency' => 'YER',
        ]);
    }

    /**
     * Calculate commission based on tiered fixed rules.
     */
    public function calculateCommission(float $amount): float
    {
        if ($amount <= 10000) {
            return 50;
        } elseif ($amount <= 100000) {
            return 100;
        } else {
            return 300;
        }
    }
}
