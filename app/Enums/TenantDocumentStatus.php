<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum TenantDocumentStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case DRAFT = 'draft';
    case ACTIVE = 'active';
    case PENDING_REVIEW = 'pending_review';
    case VERIFIED = 'verified';
    case REJECTED = 'rejected';
    case EXPIRED = 'expired';
    case ARCHIVED = 'archived';
    case DELETED = 'deleted';

    /**
     * @return list<string>
     */
    public static function tenantPortalValues(): array
    {
        return self::onlyValues(
            self::ACTIVE,
            self::PENDING_REVIEW,
            self::VERIFIED,
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
            self::DRAFT,
            self::ACTIVE,
            self::PENDING_REVIEW,
            self::VERIFIED,
            self::REJECTED,
        );
    }
}
