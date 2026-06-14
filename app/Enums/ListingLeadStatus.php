<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum ListingLeadStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case NEW = 'new';
    case ASSIGNED = 'assigned';
    case CONTACTED = 'contacted';
    case FOLLOW_UP_NEEDED = 'follow_up_needed';
    case RESPONDED = 'responded';
    case INTERESTED = 'interested';
    case NOT_INTERESTED = 'not_interested';
    case CONVERTED = 'converted';
    case DUPLICATE = 'duplicate';
    case INVALID = 'invalid';
    case DO_NOT_CONTACT = 'do_not_contact';
    case ARCHIVED = 'archived';

    /**
     * @return list<string>
     */
    public static function activeValues(): array
    {
        return self::exceptValues(self::CONVERTED, self::DUPLICATE, self::INVALID, self::ARCHIVED);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [self::CONVERTED, self::ARCHIVED], true);
    }
}
