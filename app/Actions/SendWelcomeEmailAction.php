<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Property;
use App\Models\User;
use App\Notifications\WelcomeEmail;
use Illuminate\Support\Facades\Log;

/**
 * Send Welcome Email Action
 * 
 * Single responsibility: Send welcome email to a new user.
 * Handles notification queuing and error logging.
 * 
 * @package App\Actions
 */
final class SendWelcomeEmailAction
{
    /**
     * Execute the action to send a welcome email.
     *
     * @param User $user The user to send email to
     * @param Property|null $property The associated property (for tenants)
     * @param string|null $temporaryPassword Temporary password to include
     * @return bool True if email was queued successfully
     */
    public function execute(User $user, ?Property $property = null, ?string $temporaryPassword = null): bool
    {
        try {
            // Queue the welcome email notification
            $user->notify(new WelcomeEmail($property, $temporaryPassword));

            Log::info('Welcome email queued', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'user_role' => $user->role->value,
                'has_property' => $property !== null,
                'tenant_id' => $user->tenant_id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to queue welcome email', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
