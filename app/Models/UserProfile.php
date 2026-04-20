<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'owner_id',
        'owner_type',
        'id_type',
        'id_number',
        'id_front_image',
        'id_back_image',
        'id_expiry_date',
        'nationality',
        'address',
        'dob',
        'verification_status',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'id_expiry_date' => 'date',
            'dob' => 'date',
        ];
    }

    public function owner()
    {
        return $this->morphTo();
    }

    public function isPending(): bool
    {
        return in_array($this->verification_status, ['pending', 'pending_verification']);
    }

    public function isApproved(): bool
    {
        return $this->verification_status === 'approved';
    }
}
