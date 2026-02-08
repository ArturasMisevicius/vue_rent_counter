<?php

namespace App\Enums;

use App\Enums\Concerns\HasTranslatableLabel;
use Filament\Support\Contracts\HasLabel;

enum UserAssignmentAction: string implements HasLabel
{
    use HasTranslatableLabel;

    case CREATED = 'created';
    case ASSIGNED = 'assigned';
    case REASSIGNED = 'reassigned';
    case DEACTIVATED = 'deactivated';
    case REACTIVATED = 'reactivated';

    /**
     * Get the raw values for validation rules.
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
