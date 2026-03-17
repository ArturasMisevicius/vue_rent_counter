<?php

namespace App\Models;

use Database\Factories\OrganizationSettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrganizationSetting extends Model
{
    /** @use HasFactory<OrganizationSettingFactory> */
    use HasFactory;

    protected $fillable = [
        'organization_id',
        'billing_contact_name',
        'billing_contact_email',
        'billing_contact_phone',
        'payment_instructions',
        'invoice_footer',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
