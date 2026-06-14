<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraChargeTypes\Pages;

use App\Filament\Actions\Admin\ExtraCharges\UpdateExtraChargeTypeAction;
use App\Filament\Resources\ExtraChargeTypes\ExtraChargeTypeResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditExtraChargeType extends EditRecord
{
    protected static string $resource = ExtraChargeTypeResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:extra_charges,edit';

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            abort(403);
        }

        unset($data['organization_id']);

        return app(UpdateExtraChargeTypeAction::class)->handle($actor, $record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->authorize(fn (): bool => ExtraChargeTypeResource::canDelete($this->record)),
        ];
    }
}
