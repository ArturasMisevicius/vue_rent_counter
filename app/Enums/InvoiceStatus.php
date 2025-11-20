<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case DRAFT = 'draft';
    case FINALIZED = 'finalized';
    case PAID = 'paid';
}
