<?php

namespace App\Actions\Admin\Properties;

use App\Models\Property;
use Illuminate\Validation\ValidationException;

class DeletePropertyAction
{
    public function handle(Property $property): void
    {
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
