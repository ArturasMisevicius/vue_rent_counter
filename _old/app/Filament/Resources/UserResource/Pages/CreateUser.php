<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Mutate form data before creating the record.
     * 
     * Automatically applies tenant_id from authenticated user for non-superadmin users.
     * Requirements: 6.5, 6.6
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();

        // Auto-apply tenant_id for non-superadmin users creating manager/tenant users
        if ($user instanceof User && $user->tenant_id && ! isset($data['tenant_id'])) {
            $data['tenant_id'] = $user->tenant_id;
        }

        return $data;
    }
}
