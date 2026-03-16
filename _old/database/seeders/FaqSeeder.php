<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            [
                'question' => 'How are meter readings validated?',
                'answer' => 'Readings pass monotonic checks, zone validation, and anomaly detection before invoicing.',
                'category' => 'Meters',
                'display_order' => 1,
            ],
            [
                'question' => 'Can tenants see only their properties?',
                'answer' => 'Yes, tenant dashboards enforce TenantScope and policies to keep data isolated.',
                'category' => 'Access',
                'display_order' => 2,
            ],
            [
                'question' => 'Do invoices support corrections?',
                'answer' => 'Invoices track drafts, finalization timestamps, and correction audits to preserve history.',
                'category' => 'Billing',
                'display_order' => 3,
            ],
            [
                'question' => 'Is there an admin back office?',
                'answer' => 'Admins manage users, providers, tariffs, and audit logs via the Filament panel with role-based navigation.',
                'category' => 'Admin',
                'display_order' => 4,
            ],
        ];

        foreach ($faqs as $faq) {
            Faq::updateOrCreate(
                ['question' => $faq['question']],
                $faq + ['is_published' => true]
            );
        }
    }
}
