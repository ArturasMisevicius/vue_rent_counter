<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case FINALIZED = 'finalized';
    case PARTIALLY_PAID = 'partially_paid';
    case PAID = 'paid';
    case OVERDUE = 'overdue';
    case VOID = 'void';
}
