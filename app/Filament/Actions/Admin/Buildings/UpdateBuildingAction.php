<?php

namespace App\Filament\Actions\Admin\Buildings;

use App\Http\Requests\Admin\Buildings\BuildingRequest;
use App\Models\Building;

class UpdateBuildingAction
{
    public function handle(Building $building, array $data): Building
    {
        /** @var BuildingRequest $request */
        $request = new BuildingRequest;
        $validated = $request->validatePayload($data);

        $building->update($validated);

        return $building->fresh();
    }
}
