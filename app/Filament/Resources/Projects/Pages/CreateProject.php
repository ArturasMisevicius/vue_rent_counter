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
use Illuminate\Support\Facades\Auth;

class CreateProject extends CreateRecord
{
    use HasContainedSuperadminSurface;

    protected static string $resource = ProjectResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $organization = Organization::query()->findOrFail((int) $data['organization_id']);

        $user = Auth::guard()->user();

        abort_unless($user instanceof User, 403);

        return app(ProjectService::class)->create($data, $organization, $user);
    }
}
