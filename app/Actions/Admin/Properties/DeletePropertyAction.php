<?php

namespace App\Actions\Admin\Properties;

use App\Models\Property;
use Illuminate\Validation\ValidationException;

class DeletePropertyAction
{
    /**
     * @throws ValidationException
     */
    public function handle(Property $property): void
    {
        $property->loadCount([
            'assignments',
            'meters',
            'invoices',
        ]);

        if (
            $property->assignments_count > 0
            || $property->meters_count > 0
            || $property->invoices_count > 0
        ) {
            throw ValidationException::withMessages([
                'property' => __('admin.properties.messages.delete_blocked'),
            ]);
        }

        $property->delete();
    }
}
