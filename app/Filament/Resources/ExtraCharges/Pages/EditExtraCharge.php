<?php

declare(strict_types=1);

namespace App\Filament\Resources\ExtraCharges\Pages;

use App\Filament\Actions\Admin\ExtraCharges\UpdateExtraChargeAction;
use App\Filament\Resources\ExtraCharges\ExtraChargeResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditExtraCharge extends EditRecord
{
    protected static string $resource = ExtraChargeResource::class;

    protected static string|array $routeMiddleware = 'manager.permission:extra_charges,edit';

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $actor = Auth::user();

        if (! $actor instanceof User) {
            abort(403);
        }

        unset($data['organization_id']);

        return app(UpdateExtraChargeAction::class)->handle($actor, $record, $data);
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->authorize(fn (): bool => ExtraChargeResource::canDelete($this->record)),
        ];
    }
}
