<?php

declare(strict_types=1);

namespace App\Filament\Resources\Projects\Pages;

use App\Filament\Resources\Pages\Concerns\HasContainedSuperadminSurface;
use App\Filament\Resources\Projects\ProjectResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\ProjectService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateProject extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = ProjectResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $organization = Organization::query()->findOrFail((int) $data['organization_id']);

        /** @var User $actor */
        $actor = auth()->user();

        return app(ProjectService::class)->create($data, $organization, $actor);
    }
}
