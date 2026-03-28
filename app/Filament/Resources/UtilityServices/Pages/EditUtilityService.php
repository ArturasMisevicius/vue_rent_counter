<?php

namespace App\Filament\Resources\UtilityServices\Pages;

use App\Filament\Actions\Admin\UtilityServices\UpdateUtilityServiceAction;
use App\Filament\Resources\UtilityServices\UtilityServiceResource;
use App\Models\UtilityService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUtilityService extends EditRecord
{
    protected static string $resource = UtilityServiceResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:utility_services,edit';

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(UpdateUtilityServiceAction::class)->handle($record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->using(fn (UtilityService $record): UtilityService => tap($record)->delete()),
        ];
    }
}
