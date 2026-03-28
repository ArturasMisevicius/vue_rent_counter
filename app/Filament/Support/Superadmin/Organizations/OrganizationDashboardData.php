<?php

declare(strict_types=1);

namespace App\Filament\Support\Superadmin\Organizations;

use App\Filament\Support\Superadmin\AuditLogs\AuditLogTablePresenter;
use App\Models\AuditLog;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;

final class OrganizationDashboardData
{
    /**
     * @return list<array{
     *     actor: string,
     *     what: string,
     *     record: string,
     *     occurred_at: string,
     *     deep_link: string,
     *     tone: string
     * }>
     */
    public function activityFeedFor(Organization $organization): array
    {
        return AuditLog::query()
            ->forOrganizationDashboardFeed()
            ->forOrganization($organization->getKey())
            ->limit(40)
            ->get()
            ->filter(fn (AuditLog $record): bool => $this->shouldIncludeInActivityFeed($record))
            ->take(10)
            ->map(fn (AuditLog $record): array => [
                'actor' => AuditLogTablePresenter::actorLabel($record),
                'what' => AuditLogTablePresenter::feedLabel($record),
                'record' => $this->recordLabel($record),
                'occurred_at' => $record->occurred_at?->locale(app()->getLocale())->diffForHumans()
                    ?? __('superadmin.organizations.overview.placeholders.not_available'),
                'deep_link' => $this->auditTimelineUrlForAuditLog($organization, $record),
                'tone' => $this->feedTone($record),
            ])
            ->values()
            ->all();
    }

    public function organizationAuditTimelineUrl(Organization $organization): string
    {
        return $this->auditLogsUrl([
            'organization' => [
                'value' => $organization->getKey(),
            ],
        ]);
    }

    public function auditTimelineUrlForActivityLog(Organization $organization, OrganizationActivityLog $activityLog): string
    {
        $filters = [
            'organization' => [
                'value' => $organization->getKey(),
            ],
            'occurred_between' => [
                'occurred_from' => $activityLog->created_at?->toDateString(),
                'occurred_to' => $activityLog->created_at?->toDateString(),
            ],
        ];

        if (filled($activityLog->resource_type)) {
            $filters['subject_type'] = [
                'value' => $activityLog->resource_type,
            ];
        }

        if ($activityLog->resource_id !== null) {
            $filters['record_id'] = [
                'subject_id' => $activityLog->resource_id,
            ];
        }

        if (filled($activityLog->user?->email)) {
            $filters['user'] = [
                'query' => $activityLog->user?->email,
            ];
        }

        return $this->auditLogsUrl($filters);
    }

    public function auditTimelineUrlForAuditLog(Organization $organization, AuditLog $auditLog): string
    {
        $filters = [
            'organization' => [
                'value' => $organization->getKey(),
            ],
            'subject_type' => [
                'value' => $auditLog->subject_type,
            ],
            'record_id' => [
                'subject_id' => $auditLog->subject_id,
            ],
            'occurred_between' => [
                'occurred_from' => $auditLog->occurred_at?->toDateString(),
                'occurred_to' => $auditLog->occurred_at?->toDateString(),
            ],
        ];

        if (filled($auditLog->actor?->email)) {
            $filters['user'] = [
                'query' => $auditLog->actor?->email,
            ];
        }

        $actionType = AuditLogTablePresenter::actionFilterValue($auditLog);

        if ($actionType !== '') {
            $filters['action_type'] = [
                'value' => $actionType,
            ];
        }

        return $this->auditLogsUrl($filters);
    }

    private function auditLogsUrl(array $filters): string
    {
        return route('filament.admin.resources.audit-logs.index').'?'.http_build_query([
            'tableFilters' => $filters,
        ]);
    }

    private function recordLabel(AuditLog $record): string
    {
        $label = AuditLogTablePresenter::recordTypeLabel($record->subject_type);

        if ($record->subject_id === null) {
            return $label;
        }

        return "{$label} #{$record->subject_id}";
    }

    private function feedTone(AuditLog $record): string
    {
        $label = mb_strtolower(AuditLogTablePresenter::feedLabel($record));

        return match (true) {
            str_contains($label, 'plan') || str_contains($label, 'план') => 'info',
            str_contains($label, 'invite') || str_contains($label, 'приглаш') => 'warning',
            str_contains($label, 'invoice') || str_contains($label, 'счет') || str_contains($label, 'sąsk') => 'success',
            default => 'default',
        };
    }

    private function shouldIncludeInActivityFeed(AuditLog $record): bool
    {
        if ($record->actor_user_id !== null) {
            return true;
        }

        if (filled(data_get($record->metadata, 'context.mutation'))) {
            return true;
        }

        $description = trim((string) $record->description);

        if ($description === '') {
            return false;
        }

        return $description !== $this->defaultAuditDescription($record);
    }

    private function defaultAuditDescription(AuditLog $record): string
    {
        return trim(sprintf(
            '%s %s',
            class_basename((string) $record->subject_type),
            $record->action?->value ?? (string) $record->getAttribute('action'),
        ));
    }
}
