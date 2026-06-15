<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum TenantKycDocumentStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case DRAFT = 'draft';
    case UPLOADED = 'uploaded';
    case PENDING_REVIEW = 'pending_review';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case ARCHIVED = 'archived';
    case REPLACED = 'replaced';

    /**
     * @return list<string>
     */
    public static function activeChecklistValues(): array
    {
        return self::onlyValues(
            self::UPLOADED,
            self::PENDING_REVIEW,
            self::APPROVED,
            self::REJECTED,
            self::EXPIRED,
        );
    }

    /**
     * @return list<string>
     */
    public static function expirableValues(): array
    {
        return self::onlyValues(
            self::UPLOADED,
            self::PENDING_REVIEW,
            self::APPROVED,
            self::REJECTED,
        );
    }
}
