<?php

namespace App\Actions\Admin\Buildings;

use App\Models\Building;
use Illuminate\Validation\ValidationException;

class DeleteBuildingAction
{
    /**
     * @throws ValidationException
     */
    public function handle(Building $building): void
    {
        if ($building->properties()->exists()) {
            throw ValidationException::withMessages([
                'building' => __('admin.buildings.messages.delete_blocked'),
            ]);
        }

        $building->delete();
    }
}
