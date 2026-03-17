<?php

namespace App\Filament\Actions\Admin\Buildings;

use App\Http\Requests\Admin\Buildings\BuildingRequest;
use App\Models\Building;
use App\Models\Organization;

class CreateBuildingAction
{
    public function handle(Organization $organization, array $data): Building
    {
        /** @var BuildingRequest $request */
        $request = new BuildingRequest;
        $validated = $request->validatePayload($data);

        return Building::query()->create([
            ...$validated,
            'organization_id' => $organization->id,
        ]);
    }
}
