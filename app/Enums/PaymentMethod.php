<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case BANK_TRANSFER = 'bank_transfer';
    case CARD = 'card';
    case CASH = 'cash';
    case OTHER = 'other';
}
