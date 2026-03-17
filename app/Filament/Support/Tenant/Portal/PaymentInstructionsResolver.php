<?php

namespace App\Filament\Support\Tenant\Portal;

use App\Models\OrganizationSetting;

class PaymentInstructionsResolver
{
    /**
     * @return array{
     *     content: string|null,
     *     contact_name: string|null,
     *     contact_email: string|null,
     *     contact_phone: string|null,
     *     has_contact_details: bool
     * }
     */
    public function resolve(?OrganizationSetting $settings): array
    {
        $content = filled($settings?->payment_instructions)
            ? trim($settings->payment_instructions)
            : (filled($settings?->invoice_footer) ? trim($settings->invoice_footer) : null);

        $contactName = filled($settings?->billing_contact_name) ? trim($settings->billing_contact_name) : null;
        $contactEmail = filled($settings?->billing_contact_email) ? trim($settings->billing_contact_email) : null;
        $contactPhone = filled($settings?->billing_contact_phone) ? trim($settings->billing_contact_phone) : null;

        return [
            'content' => $content,
            'contact_name' => $contactName,
            'contact_email' => $contactEmail,
            'contact_phone' => $contactPhone,
            'has_contact_details' => $contactName !== null || $contactEmail !== null || $contactPhone !== null,
        ];
    }
}
