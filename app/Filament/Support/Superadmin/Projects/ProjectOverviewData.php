<?php

declare(strict_types=1);

namespace App\Filament\Support\Superadmin\Projects;

use App\Filament\Support\Formatting\EuMoneyFormatter;
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
                'actor' => $entry->actor?->name ?? __('admin.projects.overview.system'),
                'action' => $entry->action?->value !== null
                    ? Str::of($entry->action->value)->replace('_', ' ')->title()->toString()
                    : __('admin.projects.overview.activity'),
                'description' => $entry->description ?: (string) data_get($entry->metadata, 'context.mutation', __('admin.projects.overview.no_description_recorded')),
                'occurred_at' => $entry->occurred_at?->toDateTimeString() ?? '—',
            ])
            ->all();
    }

    private function identity(Project $project): array
    {
        return [
            ['label' => __('admin.projects.overview.project_name'), 'value' => $project->name],
            ['label' => __('admin.projects.overview.reference_number'), 'value' => $project->reference_number ?: '—'],
            ['label' => __('admin.projects.overview.organization'), 'value' => $project->organization?->name ?? '—'],
            ['label' => __('admin.projects.overview.building'), 'value' => $project->building?->name ?? '—'],
            ['label' => __('admin.projects.overview.property'), 'value' => $project->property?->name ?? '—'],
            ['label' => __('admin.projects.overview.status'), 'value' => $project->status?->getLabel() ?? '—'],
            ['label' => __('admin.projects.overview.priority'), 'value' => $project->priority?->getLabel() ?? '—'],
            ['label' => __('admin.projects.overview.type'), 'value' => $project->type?->getLabel() ?? '—'],
            ['label' => __('admin.projects.overview.manager'), 'value' => $project->manager?->name ?? __('admin.projects.overview.unassigned')],
            ['label' => __('admin.projects.overview.requires_approval'), 'value' => $project->requires_approval ? __('admin.projects.overview.yes') : __('admin.projects.overview.no')],
            ['label' => __('admin.projects.overview.approved_at'), 'value' => $project->approved_at?->toDateTimeString() ?? '—'],
            ['label' => __('admin.projects.overview.approved_by'), 'value' => $project->approver?->name ?? '—'],
            ['label' => __('admin.projects.overview.created_at'), 'value' => $project->created_at?->toDateTimeString() ?? '—'],
            ['label' => __('admin.projects.overview.updated_at'), 'value' => $project->updated_at?->toDateTimeString() ?? '—'],
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
                $varianceDays === null => __('admin.projects.overview.no_estimated_end_date'),
                $varianceDays > 0 => __('admin.projects.overview.days_behind_schedule', ['count' => $varianceDays]),
                $varianceDays < 0 => __('admin.projects.overview.days_ahead_of_schedule', ['count' => abs($varianceDays)]),
                default => __('admin.projects.overview.on_schedule'),
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
                $variance === null => __('admin.projects.overview.no_budget_set'),
                $variance > 0 => __('admin.projects.overview.amount_over_budget', ['amount' => $this->money(abs($variance))]),
                $variance < 0 => __('admin.projects.overview.amount_under_budget', ['amount' => $this->money(abs($variance))]),
                default => __('admin.projects.overview.on_budget'),
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
                'role' => __('admin.projects.overview.manager_role'),
            ]);
        }

        $memberships = $project->projectMemberships instanceof Collection
            ? $project->projectMemberships
            : collect();

        $membershipRows = $memberships
            ->filter(fn (ProjectUser $membership): bool => $membership->user !== null)
            ->map(fn (ProjectUser $membership): array => [
                'name' => $membership->user?->name ?? __('admin.projects.overview.unknown_user'),
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
                ['label' => __('admin.projects.overview.to_do'), 'count' => $toDo, 'tone' => 'gray'],
                ['label' => __('admin.projects.overview.in_progress'), 'count' => $inProgress, 'tone' => 'warning'],
                ['label' => __('admin.projects.overview.completed'), 'count' => $completed, 'tone' => 'success'],
                ['label' => __('admin.projects.overview.blocked'), 'count' => $blocked, 'tone' => 'danger'],
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
                    'tenant' => $assignment->tenant?->name ?? __('admin.projects.overview.unknown_tenant'),
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

        return EuMoneyFormatter::format($amount);
    }
}
