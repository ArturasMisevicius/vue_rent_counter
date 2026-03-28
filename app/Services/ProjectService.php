<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\AuditLogAction;
use App\Enums\InvoiceStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProjectTeamRole;
use App\Enums\ProjectType;
use App\Exceptions\ProjectApprovalRequiredException;
use App\Exceptions\ProjectCostPassthroughException;
use App\Filament\Support\Audit\AuditLogger;
use App\Models\Building;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Organization;
use App\Models\OrganizationSetting;
use App\Models\Project;
use App\Models\Property;
use App\Models\PropertyAssignment;
use App\Models\User;
use App\Notifications\Projects\ProjectApprovalRequestedNotification;
use App\Notifications\Projects\ProjectApprovedNotification;
use App\Notifications\Projects\ProjectEmergencyCreatedNotification;
use App\Notifications\Projects\ProjectOverBudgetNotification;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

final class ProjectService
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, Organization $organization, User $actor): Project
    {
        return DB::transaction(function () use ($data, $organization, $actor): Project {
            $settings = $this->lockProjectSettings($organization);
            $type = $this->resolveProjectType($data['type'] ?? null);
            $status = $this->resolveProjectStatus($data['status'] ?? null, $type);
            $referenceNumber = $this->nextReferenceNumber($settings, $data['reference_number'] ?? null);

            $this->validateScope(
                organizationId: $organization->id,
                buildingId: $data['building_id'] ?? null,
                propertyId: $data['property_id'] ?? null,
                managerId: $data['manager_id'] ?? null,
            );

            $project = Project::query()->create([
                ...$data,
                'organization_id' => $organization->id,
                'created_by_user_id' => $data['created_by_user_id'] ?? $actor->id,
                'reference_number' => $referenceNumber,
                'type' => $type,
                'status' => $status,
                'assigned_to_user_id' => $data['assigned_to_user_id'] ?? $data['manager_id'] ?? null,
                'manager_id' => $data['manager_id'] ?? null,
                'budget_amount' => $data['budget_amount'] ?? $data['budget'] ?? null,
                'budget' => $data['budget'] ?? $data['budget_amount'] ?? null,
                'estimated_start_date' => $data['estimated_start_date'] ?? $data['start_date'] ?? null,
                'start_date' => $data['start_date'] ?? $data['estimated_start_date'] ?? null,
                'estimated_end_date' => $data['estimated_end_date'] ?? $data['due_date'] ?? null,
                'due_date' => $data['due_date'] ?? $data['estimated_end_date'] ?? null,
                'actual_start_date' => $status === ProjectStatus::IN_PROGRESS ? today() : ($data['actual_start_date'] ?? null),
                'completion_percentage' => (int) ($data['completion_percentage'] ?? 0),
            ]);

            if ($project->manager_id !== null) {
                $project->teamMembers()->syncWithoutDetaching([
                    $project->manager_id => [
                        'role' => ProjectTeamRole::MANAGER->value,
                        'invited_at' => now(),
                        'invited_by' => $actor->id,
                    ],
                ]);
            }

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $project,
                [
                    'workspace' => ['organization_id' => $organization->id],
                    'context' => ['mutation' => 'project.created'],
                    'after' => $project->only([
                        'reference_number',
                        'status',
                        'type',
                        'priority',
                        'manager_id',
                    ]),
                ],
                $actor->id,
                'Project created',
            );

            if ($type->isEmergency()) {
                Notification::send($this->approvalRecipients($organization), new ProjectEmergencyCreatedNotification($project));
            }

            if ($project->requires_approval && $project->status === ProjectStatus::PLANNED && $project->approved_at === null) {
                Notification::send($this->approvalRecipients($organization), new ProjectApprovalRequestedNotification($project));
            }

            return $project->fresh([
                'organization',
                'manager',
                'property',
                'building',
                'teamMembers',
            ]);
        });
    }

    public function transitionStatus(
        Project $project,
        ProjectStatus $newStatus,
        User $actor,
        ?string $reason = null,
        bool $force = false,
    ): Project {
        return DB::transaction(function () use ($project, $newStatus, $actor, $reason, $force): Project {
            if ($newStatus === ProjectStatus::IN_PROGRESS && $project->requires_approval && $project->approved_at === null && ! $force) {
                throw ProjectApprovalRequiredException::forStart();
            }

            $before = $project->status;

            $project->transitionTo($newStatus, $reason, $force);
            $project->save();

            if ($newStatus === ProjectStatus::CANCELLED) {
                $project->tasks()
                    ->where('status', '!=', 'completed')
                    ->get()
                    ->each(fn ($task) => $task->closeAsCancelled($reason ?? 'Project cancelled'));

                $project->timeEntries()
                    ->where('approval_status', 'pending')
                    ->update([
                        'approval_status' => 'rejected',
                        'rejected_at' => now(),
                        'rejection_reason' => $reason ?? 'Project cancelled',
                    ]);

                $project->invoiceItems()
                    ->whereNull('voided_at')
                    ->update([
                        'voided_at' => now(),
                        'void_reason' => $reason ?? 'Project cancelled',
                    ]);
            }

            $this->auditLogger->record(
                AuditLogAction::UPDATED,
                $project,
                [
                    'workspace' => ['organization_id' => $project->organization_id],
                    'context' => ['mutation' => 'project.status_changed'],
                    'before' => ['status' => $before instanceof ProjectStatus ? $before->value : (string) $before],
                    'after' => ['status' => $newStatus->value, 'reason' => $reason],
                ],
                $actor->id,
                'Project status changed',
            );

            return $project->fresh([
                'organization',
                'manager',
                'property',
                'building',
                'tasks',
            ]);
        });
    }

    public function approve(Project $project, User $actor): Project
    {
        return DB::transaction(function () use ($project, $actor): Project {
            if ($project->status !== ProjectStatus::PLANNED || ! $project->requires_approval) {
                throw ValidationException::withMessages([
                    'project' => 'Only approval-required planned projects can be approved.',
                ]);
            }

            $project->forceFill([
                'approved_at' => now(),
                'approved_by' => $actor->id,
            ])->save();

            if ($project->manager !== null) {
                $project->manager->notify(new ProjectApprovedNotification($project));
            }

            $this->auditLogger->record(
                AuditLogAction::APPROVED,
                $project,
                [
                    'workspace' => ['organization_id' => $project->organization_id],
                    'context' => ['mutation' => 'project.approved'],
                ],
                $actor->id,
                'Project approved',
            );

            return $project->fresh(['manager', 'approver']);
        });
    }

    /**
     * @return Collection<int, InvoiceItem>
     */
    public function generateCostPassthrough(Project $project, User $actor): Collection
    {
        if ($project->status !== ProjectStatus::COMPLETED || ! $project->cost_passed_to_tenant) {
            throw ProjectCostPassthroughException::invalidState();
        }

        return DB::transaction(function () use ($project, $actor): Collection {
            $assignments = $this->affectedAssignments($project);

            if ($assignments->isEmpty()) {
                return collect();
            }

            $costShare = round((float) $project->actual_cost / $assignments->count(), 2);
            $items = collect();

            foreach ($assignments as $assignment) {
                $invoice = $this->currentDraftInvoice($project, $assignment);

                $item = $invoice->invoiceItems()->create([
                    'project_id' => $project->id,
                    'description' => sprintf('Project cost recovery: %s', $project->name),
                    'quantity' => 1,
                    'unit' => 'project',
                    'unit_price' => $costShare,
                    'total' => $costShare,
                    'metadata' => [
                        'source' => 'project_cost_passthrough',
                        'project_reference_number' => $project->reference_number,
                    ],
                ]);

                $items->push($item);
            }

            $this->auditLogger->record(
                AuditLogAction::CREATED,
                $project,
                [
                    'workspace' => ['organization_id' => $project->organization_id],
                    'context' => ['mutation' => 'project.cost_passthrough_generated'],
                    'after' => ['items_created' => $items->count()],
                ],
                $actor->id,
                'Project passthrough generated',
            );

            return $items;
        });
    }

    public function recalculateActualCost(Project $project): void
    {
        $timeCost = (float) $project->timeEntries()->sum('cost_amount');
        $directCost = (float) $project->costRecords()->sum('amount');
        $actualCost = round($timeCost + $directCost, 2);

        $project->forceFill([
            'actual_cost' => $actualCost,
        ])->save();

        $budgetAmount = $project->budget_amount !== null ? (float) $project->budget_amount : null;

        if ($budgetAmount === null || $budgetAmount <= 0) {
            return;
        }

        $thresholdPercent = (int) ($project->organization?->settings?->project_budget_alert_threshold_percent ?? 10);
        $thresholdAmount = $budgetAmount * (1 + ($thresholdPercent / 100));

        if ($actualCost <= $thresholdAmount) {
            return;
        }

        Notification::send($this->approvalRecipients($project->organization), new ProjectOverBudgetNotification($project->fresh()));
    }

    public function recalculateCompletion(Project $project): void
    {
        $settings = $project->organization?->settings;

        if (! $settings?->usesAutomaticProjectCompletion()) {
            return;
        }

        $totalTasks = $project->tasks()->count();

        if ($totalTasks === 0) {
            return;
        }

        $completedTasks = $project->tasks()->where('status', 'completed')->count();

        $project->forceFill([
            'completion_percentage' => (int) round(($completedTasks / $totalTasks) * 100),
        ])->save();
    }

    private function lockProjectSettings(Organization $organization): OrganizationSetting
    {
        $settings = OrganizationSetting::query()
            ->where('organization_id', $organization->id)
            ->lockForUpdate()
            ->first();

        if ($settings instanceof OrganizationSetting) {
            return $settings;
        }

        return OrganizationSetting::query()->create([
            'organization_id' => $organization->id,
            'billing_contact_name' => $organization->name.' Billing',
            'billing_contact_email' => 'billing+'.$organization->id.'@example.com',
            'project_reference_prefix' => 'PROJ-',
            'project_reference_sequence' => 0,
            'project_completion_mode' => 'manual',
            'project_budget_alert_threshold_percent' => 10,
            'project_schedule_alert_threshold_days' => 30,
            'notification_preferences' => [],
        ]);
    }

    private function nextReferenceNumber(OrganizationSetting $settings, mixed $override): string
    {
        $settings->increment('project_reference_sequence');
        $settings->refresh();

        if (filled($override)) {
            return (string) $override;
        }

        return sprintf(
            '%s%04d',
            (string) $settings->project_reference_prefix,
            (int) $settings->project_reference_sequence,
        );
    }

    private function resolveProjectType(mixed $value): ProjectType
    {
        if ($value instanceof ProjectType) {
            return $value;
        }

        return ProjectType::from((string) ($value ?: ProjectType::MAINTENANCE->value));
    }

    private function resolveProjectStatus(mixed $value, ProjectType $type): ProjectStatus
    {
        if ($value instanceof ProjectStatus) {
            return $value;
        }

        if ($type->isEmergency() && blank($value)) {
            return ProjectStatus::IN_PROGRESS;
        }

        return ProjectStatus::from((string) ($value ?: ProjectStatus::DRAFT->value));
    }

    private function validateScope(
        int $organizationId,
        mixed $buildingId,
        mixed $propertyId,
        mixed $managerId,
    ): void {
        if (filled($buildingId)) {
            $building = Building::query()
                ->select(['id', 'organization_id'])
                ->find($buildingId);

            if ($building === null || $building->organization_id !== $organizationId) {
                throw ValidationException::withMessages([
                    'building_id' => 'The selected building does not belong to the organization.',
                ]);
            }
        }

        if (filled($propertyId)) {
            $property = Property::query()
                ->select(['id', 'organization_id', 'building_id'])
                ->find($propertyId);

            if ($property === null || $property->organization_id !== $organizationId) {
                throw ValidationException::withMessages([
                    'property_id' => 'The selected property does not belong to the organization.',
                ]);
            }

            if (filled($buildingId) && $property->building_id !== (int) $buildingId) {
                throw ValidationException::withMessages([
                    'property_id' => 'The selected property does not belong to the selected building.',
                ]);
            }
        }

        if (filled($managerId)) {
            $manager = User::query()
                ->select(['id', 'organization_id'])
                ->find($managerId);

            if ($manager === null || $manager->organization_id !== $organizationId) {
                throw ValidationException::withMessages([
                    'manager_id' => 'The selected manager does not belong to the organization.',
                ]);
            }
        }
    }

    /**
     * @return EloquentCollection<int, User>
     */
    private function approvalRecipients(?Organization $organization): EloquentCollection
    {
        if (! $organization instanceof Organization) {
            return new EloquentCollection;
        }

        $users = User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'role'])
            ->where('organization_id', $organization->id)
            ->whereIn('role', ['admin'])
            ->get();

        if ($organization->owner_user_id !== null && ! $users->contains('id', $organization->owner_user_id)) {
            $owner = User::query()
                ->select(['id', 'organization_id', 'name', 'email', 'role'])
                ->find($organization->owner_user_id);

            if ($owner instanceof User) {
                $users->push($owner);
            }
        }

        return $users;
    }

    /**
     * @return EloquentCollection<int, PropertyAssignment>
     */
    private function affectedAssignments(Project $project): EloquentCollection
    {
        return PropertyAssignment::query()
            ->select(['id', 'organization_id', 'property_id', 'tenant_user_id', 'unit_area_sqm', 'assigned_at', 'unassigned_at'])
            ->forOrganization($project->organization_id)
            ->current()
            ->when(
                $project->property_id !== null,
                fn ($query) => $query->where('property_id', $project->property_id),
                fn ($query) => $project->building_id !== null
                    ? $query->whereHas('property', fn ($propertyQuery) => $propertyQuery->where('building_id', $project->building_id))
                    : $query,
            )
            ->get();
    }

    private function currentDraftInvoice(Project $project, PropertyAssignment $assignment): Invoice
    {
        $draftInvoice = Invoice::query()
            ->where('organization_id', $project->organization_id)
            ->where('property_id', $assignment->property_id)
            ->where('tenant_user_id', $assignment->tenant_user_id)
            ->where('status', InvoiceStatus::DRAFT->value)
            ->whereDate('billing_period_start', now()->startOfMonth()->toDateString())
            ->whereDate('billing_period_end', now()->endOfMonth()->toDateString())
            ->first();

        if ($draftInvoice instanceof Invoice) {
            return $draftInvoice;
        }

        return Invoice::query()->create([
            'organization_id' => $project->organization_id,
            'property_id' => $assignment->property_id,
            'tenant_user_id' => $assignment->tenant_user_id,
            'status' => InvoiceStatus::DRAFT,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end' => now()->endOfMonth()->toDateString(),
            'invoice_number' => 'INV-TEMP-'.$project->id.'-'.$assignment->tenant_user_id,
            'currency' => 'EUR',
            'total_amount' => 0,
            'amount_paid' => 0,
            'paid_amount' => 0,
            'due_date' => now()->endOfMonth()->addDays(14)->toDateString(),
            'items' => [],
            'snapshot_data' => [],
        ]);
    }
}
