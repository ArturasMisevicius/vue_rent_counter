<?php

namespace App\Filament\Actions\Admin\Buildings;

use App\Models\Building;
use App\Models\Organization;
use Illuminate\Support\Facades\Validator;

class CreateBuildingAction
{
    public function handle(Organization $organization, array $data): Building
    {
        $validated = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'address_line_1' => ['required', 'string', 'max:255'],
            'address_line_2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country_code' => ['required', 'string', 'size:2'],
        ])->validate();

        $validated['country_code'] = strtoupper($validated['country_code']);

        return Building::query()->create([
            ...$validated,
            'organization_id' => $organization->id,
        ]);
    }
}
