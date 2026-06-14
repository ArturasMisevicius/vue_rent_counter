<?php

declare(strict_types=1);

namespace App\Filament\Support\Billing;

use Filament\Forms\Components\Textarea;

final class InvoiceLineItemDescription
{
    public const int MAX_LENGTH = 4000;

    private const int ROWS = 5;

    public static function textarea(Textarea $field): Textarea
    {
        return $field
            ->required()
            ->maxLength(self::MAX_LENGTH)
            ->rows(self::ROWS)
            ->autosize()
            ->helperText(__('admin.invoices.helpers.line_item_description'))
            ->columnSpanFull();
    }
}
