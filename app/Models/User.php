<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'full_name',
        'phone',
        'password',
        'is_verified',
        'status',
        'role',
        'gender',
    ];

    protected $hidden = [
        'password',
    ];

    /**
     * Interact with the user's phone to always ensure it starts with +967.
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
            'is_verified' => 'boolean',
        ];
    }

    public function profile()
    {
        return $this->morphOne(UserProfile::class, 'owner');
    }

    public function wallet()
    {
        return $this->morphOne(Wallet::class, 'owner');
    }

    public function notifications()
    {
        return $this->morphMany(Notification::class, 'notifiable');
    }

    /**
     * Get the wallet, creating one if it doesn't exist.
     */
    public function getOrCreateWallet(): Wallet
    {
        return $this->wallet ?? $this->wallet()->create([
            'balance' => 0,
            'currency' => 'YER',
            'status' => 'active',
        ]);
    }
}
