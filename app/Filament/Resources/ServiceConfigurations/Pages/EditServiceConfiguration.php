<?php

namespace App\Filament\Resources\ServiceConfigurations\Pages;

use App\Filament\Actions\Admin\ServiceConfigurations\UpdateServiceConfigurationAction;
use App\Filament\Actions\Help\ContextualHelpAction;
use App\Filament\Resources\ServiceConfigurations\ServiceConfigurationResource;
use App\Models\ServiceConfiguration;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditServiceConfiguration extends EditRecord
{
    protected static string $resource = ServiceConfigurationResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:service_configurations,edit';

    protected function getHeaderActions(): array
    {
        return [
            ContextualHelpAction::make('service_configurations.edit'),
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        abort_unless($record instanceof ServiceConfiguration, 404);

        return app(UpdateServiceConfigurationAction::class)->handle($record, $data);
    }
}
