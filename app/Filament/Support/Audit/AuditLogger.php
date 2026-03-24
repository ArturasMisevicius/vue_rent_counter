<?php

namespace App\Filament\Support\Audit;

use App\Enums\AuditLogAction;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;

class AuditLogger
{
    public function created(Model $subject): void
    {
        $this->write(AuditLogAction::CREATED, $subject, [
            'after' => $this->snapshot($subject),
        ]);
    }

    public function updated(Model $subject): void
    {
        $changes = Arr::except($subject->getChanges(), ['updated_at']);

        if ($changes === []) {
            return;
        }

        $this->write(AuditLogAction::UPDATED, $subject, [
            'before' => $this->sanitize(Arr::only($subject->getOriginal(), array_keys($changes))),
            'after' => $this->sanitize($changes),
        ]);
    }

    public function deleted(Model $subject): void
    {
        $this->write(AuditLogAction::DELETED, $subject, [
            'before' => $this->snapshot($subject),
        ]);
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    protected function write(AuditLogAction $action, Model $subject, array $metadata = []): void
    {
        if (! Schema::hasTable((new AuditLog)->getTable())) {
            return;
        }

        AuditLog::query()->create([
            'organization_id' => $this->organizationId($subject),
            'actor_user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subject::class,
            'subject_id' => $subject->getKey(),
            'description' => class_basename($subject).' '.$action->value,
            'metadata' => $metadata,
            'occurred_at' => now(),
        ]);

        $organizationId = $this->organizationId($subject);

        if ($organizationId === null || ! Schema::hasTable((new OrganizationActivityLog)->getTable())) {
            return;
        }

        OrganizationActivityLog::query()->create([
            'organization_id' => $organizationId,
            'user_id' => auth()->id(),
            'action' => $action->value,
            'resource_type' => $subject::class,
            'resource_id' => $subject->getKey(),
            'metadata' => $metadata,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function snapshot(Model $subject): array
    {
        return $this->sanitize($subject->getAttributes());
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    protected function sanitize(array $values): array
    {
        return Arr::except($values, [
            'password',
            'remember_token',
        ]);
    }

    protected function organizationId(Model $subject): ?int
    {
        if ($subject instanceof Organization) {
            return (int) $subject->getKey();
        }

        $organizationId = $subject->getAttribute('organization_id');

        if (is_int($organizationId)) {
            return $organizationId;
        }

        if (is_numeric($organizationId)) {
            return (int) $organizationId;
        }

        return null;
    }
}
