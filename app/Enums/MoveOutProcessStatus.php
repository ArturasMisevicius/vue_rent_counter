<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasTranslatedLabel;
use Filament\Support\Contracts\HasLabel;

enum MoveOutProcessStatus: string implements HasLabel
{
    use HasTranslatedLabel;

    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case WAITING_FINAL_READINGS = 'waiting_final_readings';
    case READY_FOR_FINAL_INVOICE = 'ready_for_final_invoice';
    case FINAL_INVOICE_SENT = 'final_invoice_sent';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    /**
     * @return array<int, string>
     */
    public static function openValues(): array
    {
        return self::exceptValues(self::COMPLETED, self::CANCELLED);
    }

    public function isOpen(): bool
    {
        return in_array($this, [
            self::DRAFT,
            self::SCHEDULED,
            self::WAITING_FINAL_READINGS,
            self::READY_FOR_FINAL_INVOICE,
            self::FINAL_INVOICE_SENT,
        ], true);
    }
}
