<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('org.{organizationId}', function (User $user, int $organizationId): bool {
    return $user->isSuperadmin() || $user->organization_id === $organizationId;
});
