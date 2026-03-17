<?php

namespace App\Livewire\Tenant;

use App\Models\User;
use App\Support\Tenant\Portal\TenantHomePresenter;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class HomeSummary extends Component
{
    public function render(): View
    {
        /** @var User $tenant */
        $tenant = auth()->user();

        return view('livewire.tenant.home-summary', [
            'summary' => array_replace_recursive(
                $this->defaultSummary(),
                app(TenantHomePresenter::class)->for($tenant),
            ),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function defaultSummary(): array
    {
        return [
            'tenant_name' => '',
            'property_name' => null,
            'property_address' => null,
            'property_url' => route('tenant.property.show'),
            'submit_reading_url' => route('tenant.readings.create'),
            'has_outstanding_balance' => false,
            'outstanding_label' => __('tenant.status.all_paid_up'),
            'outstanding_total' => 0,
            'outstanding_total_display' => '',
            'outstanding_invoice_count' => 0,
            'payment_guidance' => [
                'content' => null,
                'has_contact_details' => false,
                'contact_name' => null,
                'contact_email' => null,
                'contact_phone' => null,
            ],
            'month_heading' => __('tenant.pages.home.month_heading'),
            'meters_missing_current_month' => 0,
            'current_month_metric' => trans_choice('tenant.pages.home.current_month_metric', 0, [
                'count' => 0,
            ]),
            'current_month_message' => __('tenant.messages.all_current_month'),
            'recent_readings' => [],
        ];
    }
}
