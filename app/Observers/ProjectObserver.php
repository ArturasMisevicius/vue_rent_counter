<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ProjectStatus;
use App\Exceptions\ProjectDeletionBlockedException;
use App\Filament\Support\Audit\AuditLogger;
use App\Jobs\Projects\RescopeProjectChildrenJob;
use App\Models\Project;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

final class ProjectObserver
{
    public function creating(Project $project): void
    {
        $this->syncLegacyColumns($project);
        $this->validateScope($project);
        $this->syncStatusGuardrails($project);
    }

    public function updating(Project $project): void
    {
        $this->syncLegacyColumns($project);

        if (! $project->isDirty('organization_id')) {
            $this->validateScope($project);
        }

        $this->syncStatusGuardrails($project);
    }

    public function created(Project $project): void
    {
        app(AuditLogger::class)->created($project);
    }

    public function updated(Project $project): void
    {
        app(AuditLogger::class)->updated($project);

        if ($project->wasChanged('organization_id')) {
            RescopeProjectChildrenJob::dispatch($project->id);
        }
    }

    public function deleting(Project $project): void
    {
        $reasons = [];

        if ($project->timeEntries()->exists()) {
            $reasons[] = 'logged time entries';
        }

        if ($project->tasks()->where('status', 'completed')->exists()) {
            $reasons[] = 'completed tasks';
        }

        if ($project->invoiceItems()->whereNull('voided_at')->exists()) {
            $reasons[] = 'committed invoice items';
        }

        if ($reasons !== []) {
            throw ProjectDeletionBlockedException::because(
                'Project cannot be deleted until '.implode(', ', $reasons).' are resolved.',
            );
        }
    }

    public function deleted(Project $project): void
    {
        app(AuditLogger::class)->deleted($project);
    }

    private function validateScope(Project $project): void
    {
        if ($project->building_id !== null) {
            $building = $project->building()->select(['id', 'organization_id'])->first();

            if ($building !== null && $building->organization_id !== $project->organization_id) {
                throw ValidationException::withMessages([
                    'building_id' => 'The selected building does not belong to the project organization.',
                ]);
            }
        }

        if ($project->property_id !== null) {
            $property = $project->property()->select(['id', 'organization_id', 'building_id'])->first();

            if ($property !== null && $property->organization_id !== $project->organization_id) {
                throw ValidationException::withMessages([
                    'property_id' => 'The selected property does not belong to the project organization.',
                ]);
            }

            if ($project->building_id !== null && $property !== null && $property->building_id !== $project->building_id) {
                throw ValidationException::withMessages([
                    'property_id' => 'The selected property does not belong to the selected building.',
                ]);
            }
        }
    }

    private function syncLegacyColumns(Project $project): void
    {
        if ($project->property_id !== null) {
            $property = $project->relationLoaded('property')
                ? $project->property
                : $project->property()->select(['id', 'building_id'])->first();

            if ($property?->building_id !== null) {
                $project->building_id = $property->building_id;
            }
        }

        if ($project->manager_id !== null && blank($project->assigned_to_user_id)) {
            $project->assigned_to_user_id = $project->manager_id;
        }

        if ($project->estimated_start_date !== null && blank($project->start_date)) {
            $project->start_date = $project->estimated_start_date;
        }

        if ($project->estimated_end_date !== null && blank($project->due_date)) {
            $project->due_date = $project->estimated_end_date;
        }

        if ($project->budget_amount !== null && blank($project->budget)) {
            $project->budget = $project->budget_amount;
        }

        $metadata = $project->metadata ?? [];

        Arr::set($metadata, 'approval_required', $project->requires_approval);

        $project->metadata = $metadata;
    }

    private function syncStatusGuardrails(Project $project): void
    {
        $status = $project->status instanceof ProjectStatus
            ? $project->status
            : ProjectStatus::from((string) $project->status);

        $metadata = $project->metadata ?? [];

        if ($status === ProjectStatus::IN_PROGRESS && $project->actual_start_date === null) {
            $project->actual_start_date = today();
        }

        if ($status === ProjectStatus::ON_HOLD) {
            if (blank(Arr::get($metadata, 'on_hold_reason'))) {
                throw ValidationException::withMessages([
                    'reason' => __('validation.required', ['attribute' => 'hold reason']),
                ]);
            }

            Arr::set($metadata, 'on_hold_started_at', Arr::get($metadata, 'on_hold_started_at', now()->toDateTimeString()));
            Arr::set($metadata, 'on_hold_reason_updated_at', Arr::get($metadata, 'on_hold_reason_updated_at', now()->toDateTimeString()));
        }

        if ($status === ProjectStatus::COMPLETED) {
            $project->actual_end_date = $project->actual_end_date ?? today();
            $project->completed_at = $project->completed_at ?? now();
            $project->completion_percentage = 100;
        }

        if ($status === ProjectStatus::CANCELLED) {
            if (blank($project->cancellation_reason)) {
                throw ValidationException::withMessages([
                    'cancellation_reason' => __('validation.required', ['attribute' => 'cancellation reason']),
                ]);
            }

            $project->cancelled_at = $project->cancelled_at ?? now();
        }

        $project->metadata = $metadata;
    }
}
