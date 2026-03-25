<?php

use App\Enums\KycVerificationStatus;

it('provides translated labels and options for kyc verification statuses', function () {
    expect(KycVerificationStatus::options())->toBe([
        'unverified' => 'Unverified',
        'pending' => 'Pending',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ])
        ->and(KycVerificationStatus::UNVERIFIED->label())->toBe('Unverified')
        ->and(KycVerificationStatus::PENDING->label())->toBe('Pending')
        ->and(KycVerificationStatus::VERIFIED->label())->toBe('Verified')
        ->and(KycVerificationStatus::REJECTED->label())->toBe('Rejected');
});
