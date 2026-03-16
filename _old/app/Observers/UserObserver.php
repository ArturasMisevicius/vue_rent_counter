<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * User Observer
 * 
 * Handles model events for User to provide audit logging
 * and maintain data integrity.
 */
class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        Log::info('User created', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role->value,
            'tenant_id' => $user->tenant_id,
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        $changes = $user->getChanges();
        
        // Log critical changes
        if (isset($changes['role']) || isset($changes['is_active']) || isset($changes['tenant_id'])) {
            Log::warning('Critical user attribute changed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'changes' => $changes,
                'changed_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        Log::warning('User deleted', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role->value,
            'deleted_by' => auth()->id(),
        ]);
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        Log::info('User restored', [
            'user_id' => $user->id,
            'email' => $user->email,
            'restored_by' => auth()->id(),
        ]);
    }
}
