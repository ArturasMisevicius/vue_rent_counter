<?php

namespace App\Filament\Support\Tenant\Portal;

use App\Models\OrganizationSetting;

class PaymentInstructionsResolver
{
    /**
     * @var array<string, string>
     */
    private const LOCALIZED_CONTENT_KEYS = [
        'Pay by bank transfer or at the office.' => 'tenant.payment_instructions.bank_transfer_or_office',
        'Pay by bank transfer and include your invoice reference.' => 'tenant.payment_instructions.bank_transfer_with_reference',
        'Thank you for paying on time.' => 'tenant.payment_instructions.thank_you_for_paying_on_time',
    ];

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
        $content = $this->localizedContent($settings?->payment_instructions)
            ?? $this->localizedContent($settings?->invoice_footer);

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

    private function localizedContent(?string $content): ?string
    {
        if (! filled($content)) {
            return null;
        }

        $trimmedContent = trim($content);
        $translationKey = self::LOCALIZED_CONTENT_KEYS[$trimmedContent] ?? null;

        return $translationKey === null ? $trimmedContent : __($translationKey);
    }
}
