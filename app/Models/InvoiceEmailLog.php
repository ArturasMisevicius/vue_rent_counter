<?php

namespace App\Models;

use Database\Factories\InvoiceEmailLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceEmailLog extends Model
{
    /** @use HasFactory<InvoiceEmailLogFactory> */
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'organization_id',
        'sent_by_user_id',
        'recipient_email',
        'subject',
        'status',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by_user_id');
    }
}
