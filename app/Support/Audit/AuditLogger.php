<?php

namespace App\Support\Audit;

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function log(
        AuditLogAction $action,
        Model $auditable,
        ?string $description = null,
        array $metadata = [],
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_id' => Auth::id(),
            'action' => $action,
            'auditable_type' => $auditable::class,
            'auditable_id' => $auditable->getKey(),
            'description' => $description,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);
    }
}
