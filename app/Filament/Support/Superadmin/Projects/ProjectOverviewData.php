<?php

declare(strict_types=1);

namespace App\Filament\Support\Superadmin\Projects;

use App\Models\AuditLog;
use App\Models\Project;
use App\Models\ProjectUser;
use App\Models\PropertyAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

final class ProjectOverviewData
{
    public function for(Project $project): array
    {
        $project->loadMissing([
            'organization:id,name',
            'building:id,organization_id,name',
            'property:id,organization_id,building_id,name,unit_number',
            'manager:id,organization_id,name,email',
            'approver:id,organization_id,name,email',
            'projectMemberships.user:id,organization_id,name,email',
        ]);

        return [
            'identity' => $this->identity($project),
            'metadata' => $this->metadata($project),
            'details' => $this->details($project),
            'schedule' => $this->schedule($project),
            'budget' => $this->budget($project),
            'team' => $this->team($project),
            'tasks' => $this->tasks($project),
            'recentActivity' => $this->auditEntries($project, 15),
            'costBreakdown' => $project->cost_passed_to_tenant ? $this->costBreakdown($project) : null,
        ];
    }

    public function auditEntries(Project $project, ?int $limit = null): array
    {
        $query = AuditLog::query()
            ->where('subject_type', Project::class)
            ->where('subject_id', $project->getKey())
            ->forAuditFeed();

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get()
            ->map(fn (AuditLog $entry): array => [
                'actor' => $entry->actor?->name ?? 'System',
                'action' => $entry->action?->value !== null
                    ? Str::of($entry->action->value)->replace('_', ' ')->title()->toString()
                    : 'Activity',
                'description' => $entry->description ?: (string) data_get($entry->metadata, 'context.mutation', 'No description recorded'),
                'occurred_at' => $entry->occurred_at?->toDateTimeString() ?? '—',
            ])
            ->all();
    }

    private function identity(Project $project): array
    {
        return [
            ['label' => 'Project name', 'value' => $project->name],
            ['label' => 'Reference number', 'value' => $project->reference_number ?: '—'],
            ['label' => 'Organization', 'value' => $project->organization?->name ?? '—'],
            ['label' => 'Building', 'value' => $project->building?->name ?? '—'],
            ['label' => 'Property', 'value' => $project->property?->name ?? '—'],
            ['label' => 'Status', 'value' => $project->status?->getLabel() ?? '—'],
            ['label' => 'Priority', 'value' => $project->priority?->getLabel() ?? '—'],
            ['label' => 'Type', 'value' => $project->type?->getLabel() ?? '—'],
            ['label' => 'Manager', 'value' => $project->manager?->name ?? 'Unassigned'],
            ['label' => 'Requires approval', 'value' => $project->requires_approval ? 'Yes' : 'No'],
            ['label' => 'Approved at', 'value' => $project->approved_at?->toDateTimeString() ?? '—'],
            ['label' => 'Approved by', 'value' => $project->approver?->name ?? '—'],
            ['label' => 'Created at', 'value' => $project->created_at?->toDateTimeString() ?? '—'],
            ['label' => 'Updated at', 'value' => $project->updated_at?->toDateTimeString() ?? '—'],
        ];
    }

    private function metadata(Project $project): array
    {
        return collect($project->metadata ?? [])
            ->map(function (mixed $value, string $key): array {
                $formattedValue = is_scalar($value)
                    ? (string) $value
                    : (json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '—');

                return [
                    'key' => Str::of($key)->replace(['_', '.'], ' ')->title()->toString(),
                    'value' => $formattedValue,
                ];
            })
            ->values()
            ->all();
    }

    private function details(Project $project): array
    {
        return [
            'description' => filled($project->description) ? (string) $project->description : '—',
            'notes' => filled($project->notes) ? (string) $project->notes : '—',
            'external_contractor' => $project->external_contractor ?: '—',
            'contractor_contact' => $project->contractor_contact ?: '—',
            'contractor_reference' => $project->contractor_reference ?: '—',
            'cancellation_reason' => $project->cancellation_reason ?: '—',
        ];
    }

    private function schedule(Project $project): array
    {
        $varianceDays = $project->scheduleVarianceDays();

        return [
            'estimated_start_date' => $project->estimated_start_date?->toDateString() ?? '—',
            'actual_start_date' => $project->actual_start_date?->toDateString() ?? '—',
            'estimated_end_date' => $project->estimated_end_date?->toDateString() ?? '—',
            'actual_end_date' => $project->actual_end_date?->toDateString() ?? '—',
            'completion_percentage' => max(0, min(100, (int) $project->completion_percentage)),
            'variance_label' => match (true) {
                $varianceDays === null => 'No estimated end date',
                $varianceDays > 0 => "{$varianceDays} day(s) behind schedule",
                $varianceDays < 0 => abs($varianceDays).' day(s) ahead of schedule',
                default => 'On schedule',
            },
            'variance_tone' => match (true) {
                $varianceDays === null => 'gray',
                $varianceDays > 0 => 'danger',
                $varianceDays < 0 => 'success',
                default => 'info',
            },
        ];
    }

    private function budget(Project $project): array
    {
        $budgetAmount = (float) ($project->budget_amount ?? 0);
        $actualCost = (float) ($project->actual_cost ?? 0);
        $variance = $project->budgetVarianceAmount();
        $maxAmount = max($budgetAmount, $actualCost, 1);

        return [
            'budget_amount' => $this->money($project->budget_amount),
            'actual_cost' => $this->money($project->actual_cost),
            'variance_label' => match (true) {
                $variance === null => 'No budget set',
                $variance > 0 => $this->money(abs($variance)).' over budget',
                $variance < 0 => $this->money(abs($variance)).' under budget',
                default => 'On budget',
            },
            'variance_tone' => match (true) {
                $variance === null => 'gray',
                $variance > 0 => 'danger',
                $variance < 0 => 'success',
                default => 'info',
            },
            'budget_bar_width' => (int) round(($budgetAmount / $maxAmount) * 100),
            'actual_bar_width' => (int) round(($actualCost / $maxAmount) * 100),
        ];
    }

    private function team(Project $project): array
    {
        $rows = collect();

        if ($project->manager !== null) {
            $rows->push([
                'name' => $project->manager->name,
                'email' => $project->manager->email,
                'role' => 'Manager',
            ]);
        }

        $memberships = $project->projectMemberships instanceof Collection
            ? $project->projectMemberships
            : collect();

        $membershipRows = $memberships
            ->filter(fn (ProjectUser $membership): bool => $membership->user !== null)
            ->map(fn (ProjectUser $membership): array => [
                'name' => $membership->user?->name ?? 'Unknown user',
                'email' => $membership->user?->email ?? '—',
                'role' => Str::of((string) $membership->role)->replace('_', ' ')->title()->toString(),
            ]);

        return $rows
            ->merge($membershipRows)
            ->unique(fn (array $row): string => $row['email'].'|'.$row['role'])
            ->values()
            ->all();
    }

    private function tasks(Project $project): array
    {
        $counts = $project->tasks()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn (mixed $count): int => (int) $count);

        $toDo = (int) ($counts->get('pending', 0) + $counts->get('draft', 0) + $counts->get('todo', 0));
        $inProgress = (int) $counts->get('in_progress', 0);
        $completed = (int) $counts->get('completed', 0);
        $blocked = max(0, $counts->sum() - $toDo - $inProgress - $completed);

        return [
            'total' => $counts->sum(),
            'columns' => [
                ['label' => 'To do', 'count' => $toDo, 'tone' => 'gray'],
                ['label' => 'In progress', 'count' => $inProgress, 'tone' => 'warning'],
                ['label' => 'Completed', 'count' => $completed, 'tone' => 'success'],
                ['label' => 'Blocked', 'count' => $blocked, 'tone' => 'danger'],
            ],
        ];
    }

    private function costBreakdown(Project $project): array
    {
        $assignments = PropertyAssignment::query()
            ->forOrganization($project->organization_id)
            ->current()
            ->withTenantSummary()
            ->withPropertySummary()
            ->when(
                $project->property_id !== null,
                fn ($query) => $query->where('property_id', $project->property_id),
                fn ($query) => $project->building_id !== null
                    ? $query->whereHas('property', fn ($propertyQuery) => $propertyQuery->where('building_id', $project->building_id))
                    : $query,
            )
            ->get();

        $share = $assignments->count() > 0
            ? round((float) $project->actual_cost / $assignments->count(), 2)
            : 0.0;

        return [
            'affected_tenants_count' => $assignments->count(),
            'share_label' => $this->money($share),
            'rows' => $assignments
                ->map(fn (PropertyAssignment $assignment): array => [
                    'tenant' => $assignment->tenant?->name ?? 'Unknown tenant',
                    'property' => $assignment->property?->name ?? '—',
                    'building' => $assignment->property?->building?->name ?? '—',
                    'share' => $this->money($share),
                ])
                ->all(),
        ];
    }

    private function money(float|int|string|null $amount): string
    {
        if ($amount === null || $amount === '') {
            return '—';
        }

        return '€'.number_format((float) $amount, 2);
    }
}
