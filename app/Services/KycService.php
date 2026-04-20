<?php

namespace App\Services;

use App\Models\UserProfile;
use App\Models\User;
use App\Models\Merchant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class KycService
{
    /**
     * Submit KYC documents for a user or merchant.
     */
    public function submit(User|Merchant $owner, array $data, ?UploadedFile $frontImage = null, ?UploadedFile $backImage = null): UserProfile
    {
        $profileData = [
            'owner_id' => $owner->id,
            'owner_type' => $owner instanceof User ? 'user' : 'merchant',
            'id_type' => $data['id_type'] ?? null,
            'id_number' => $data['id_number'] ?? null,
            'id_expiry_date' => $data['id_expiry_date'] ?? null,
            'nationality' => $data['nationality'] ?? null,
            'address' => $data['address'] ?? null,
            'dob' => $data['dob'] ?? null,
            'verification_status' => 'pending_verification',
        ];

        // Handle image uploads
        if ($frontImage) {
            $frontPath = $frontImage->store('kyc/documents', 'public');
            $profileData['id_front_image'] = $frontPath;
        }

        if ($backImage) {
            $backPath = $backImage->store('kyc/documents', 'public');
            $profileData['id_back_image'] = $backPath;
        }

        // Update or create profile
        $profile = UserProfile::updateOrCreate(
            [
                'owner_id' => $owner->id,
                'owner_type' => $profileData['owner_type'],
            ],
            $profileData
        );

        return $profile;
    }

    /**
     * Approve a KYC request.
     */
    public function approve(UserProfile $profile): UserProfile
    {
        $profile->update(['verification_status' => 'approved']);

        // Update the owner's verified status
        $owner = $profile->owner;
        if ($owner) {
            $owner->update(['is_verified' => true]);
        }

        return $profile;
    }

    /**
     * Reject a KYC request.
     */
    public function reject(UserProfile $profile, string $reason): UserProfile
    {
        $profile->update([
            'verification_status' => 'rejected',
            'rejection_reason' => $reason,
        ]);

        return $profile;
    }
}
