<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Enums\UserRole;
use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * Mutate form data before creating the record.
     * Auto-assign tenant_id and parent_user_id based on authenticated user.
     * 
     * Requirements: 5.2, 13.2
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $authUser = auth()->user();
        
        // For tenant users, inherit admin's tenant_id and set parent_user_id
        if (isset($data['role']) && $data['role'] === UserRole::TENANT->value) {
            $data['tenant_id'] = $authUser->tenant_id;
            $data['parent_user_id'] = $authUser->id;
        }
        
        // For admin users, generate unique tenant_id
        if (isset($data['role']) && $data['role'] === UserRole::ADMIN->value) {
            $data['tenant_id'] = $this->generateUniqueTenantId();
        }
        
        // For manager users, inherit admin's tenant_id
        if (isset($data['role']) && $data['role'] === UserRole::MANAGER->value) {
            $data['tenant_id'] = $authUser->tenant_id;
        }
        
        return $data;
    }

    /**
     * Generate a unique tenant_id.
     */
    protected function generateUniqueTenantId(): int
    {
        do {
            $tenantId = random_int(1000, 999999);
        } while (\App\Models\User::where('tenant_id', $tenantId)->exists());

        return $tenantId;
    }
}
