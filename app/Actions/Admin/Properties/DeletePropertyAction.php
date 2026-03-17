<?php

namespace App\Actions\Admin\Properties;

use App\Models\Property;
use App\Support\Admin\SubscriptionLimitGuard;
use Illuminate\Validation\ValidationException;

class DeletePropertyAction
{
    public function __construct(
        private readonly SubscriptionLimitGuard $subscriptionLimitGuard,
    ) {}

    public function handle(Property $property): void
    {
        $this->subscriptionLimitGuard->ensureCanWrite($property->organization_id);

        if (
            $property->assignments()->exists()
            || $property->meters()->exists()
            || $property->invoices()->exists()
        ) {
            throw ValidationException::withMessages([
                'property' => __('admin.properties.messages.delete_blocked'),
            ]);
        }

        $property->delete();
    }
}
