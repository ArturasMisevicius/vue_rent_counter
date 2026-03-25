<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\KycVerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class UserKycProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'organization_id',
        'full_legal_name',
        'birth_date',
        'nationality',
        'gender',
        'marital_status',
        'tax_id_number',
        'social_security_number',
        'facial_recognition_consent',
        'secondary_contact_name',
        'secondary_contact_relationship',
        'secondary_contact_phone',
        'secondary_contact_email',
        'tertiary_contact_name',
        'tertiary_contact_relationship',
        'tertiary_contact_phone',
        'tertiary_contact_email',
        'employer_name',
        'employment_position',
        'employment_contract_type',
        'monthly_income_range',
        'iban',
        'swift_bic',
        'bank_name',
        'bank_account_holder_name',
        'payment_history_score',
        'external_credit_bureau_reference',
        'internal_credit_score',
        'blacklist_status',
        'verification_status',
        'rejection_reason',
        'submitted_at',
        'reviewed_at',
        'reviewed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'tax_id_number' => 'encrypted',
            'social_security_number' => 'encrypted',
            'iban' => 'encrypted',
            'swift_bic' => 'encrypted',
            'facial_recognition_consent' => 'boolean',
            'payment_history_score' => 'integer',
            'internal_credit_score' => 'integer',
            'blacklist_status' => 'boolean',
            'verification_status' => KycVerificationStatus::class,
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by_user_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }
}
