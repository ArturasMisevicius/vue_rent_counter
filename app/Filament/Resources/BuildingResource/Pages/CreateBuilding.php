<?php

namespace App\Filament\Resources\BuildingResource\Pages;

use App\Filament\Resources\BuildingResource;
use App\Http\Requests\StoreBuildingRequest;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CreateBuilding extends CreateRecord
{
    protected static string $resource = BuildingResource::class;

    /**
     * Mutate the form data before creating the record.
     * Automatically assigns tenant_id from authenticated user.
     *
     * @param array $data
     * @return array
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Automatically set tenant_id from authenticated user (Requirements 7.4)
        $data['tenant_id'] = auth()->user()->tenant_id;
        
        return $data;
    }

    /**
     * Validate the form data using StoreBuildingRequest rules.
     *
     * @throws ValidationException
     */
    protected function getFormValidationRules(): array
    {
        $request = new StoreBuildingRequest();
        return $request->rules();
    }
}
