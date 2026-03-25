<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Kyc;

use App\Enums\KycVerificationStatus;
use App\Models\User;
use App\Models\UserKycProfile;
use Illuminate\Support\Facades\Auth;

class VerifyKycProfileAction
{
    public function handle(UserKycProfile $profile): UserKycProfile
    {
        $reviewer = Auth::user();

        $profile->update([
            'verification_status' => KycVerificationStatus::VERIFIED,
            'rejection_reason' => null,
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $reviewer instanceof User ? $reviewer->getKey() : null,
        ]);

        return $profile->fresh();
    }
}
