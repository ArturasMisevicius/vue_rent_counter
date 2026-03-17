<?php

namespace App\Filament\Actions\Admin\Buildings;

use App\Models\Building;
use Illuminate\Support\Facades\Validator;

class UpdateBuildingAction
{
    public function handle(Building $building, array $data): Building
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

        $building->update($validated);

        return $building->fresh();
    }
}
