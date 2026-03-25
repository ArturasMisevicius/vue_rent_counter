<?php

declare(strict_types=1);

namespace App\Filament\Actions\Admin\Kyc;

use App\Enums\KycVerificationStatus;
use App\Http\Requests\Admin\Kyc\RejectKycProfileRequest;
use App\Models\User;
use App\Models\UserKycProfile;
use Illuminate\Support\Facades\Auth;

class RejectKycProfileAction
{
    public function handle(UserKycProfile $profile, array $data): UserKycProfile
    {
        $request = new RejectKycProfileRequest;
        $validated = $request->validatePayload($data, Auth::user());
        $reviewer = Auth::user();

        $profile->update([
            'verification_status' => KycVerificationStatus::REJECTED,
            'rejection_reason' => (string) $validated['rejection_reason'],
            'reviewed_at' => now(),
            'reviewed_by_user_id' => $reviewer instanceof User ? $reviewer->getKey() : null,
        ]);

        return $profile->fresh();
    }
}
