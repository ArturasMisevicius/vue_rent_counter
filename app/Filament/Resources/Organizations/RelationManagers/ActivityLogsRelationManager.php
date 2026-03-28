<?php

declare(strict_types=1);

namespace App\Filament\Resources\Organizations\RelationManagers;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Filament\Support\Admin\Tenants\OrganizationActivityLogPresenter;
use App\Filament\Support\Superadmin\Organizations\OrganizationDashboardData;
use App\Models\Organization;
use App\Models\OrganizationActivityLog;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

class ActivityLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'activityLogs';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return OrganizationResource::canAccess();
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('superadmin.organizations.relations.activity_logs.title');
    }

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = $ownerRecord->getAttribute('activity_logs_count');

        return $count === null ? (string) $ownerRecord->activityLogs()->count() : (string) $count;
    }

    public function getRelationship(): Relation
    {
        /** @var Organization $organization */
        $organization = $this->getOwnerRecord();

        return $organization->activityLogs()
            ->withActorSummary()
            ->recent();
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Action::make('openAuditTimeline')
                    ->label(__('superadmin.organizations.relations.activity_logs.actions.open_audit_timeline'))
                    ->url(fn (): string => app(OrganizationDashboardData::class)->organizationAuditTimelineUrl($this->getOwnerRecord())),
            ])
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('superadmin.organizations.relations.activity_logs.columns.actor'))
                    ->default(__('superadmin.organizations.relations.activity_logs.placeholders.system'))
                    ->searchable(),
                TextColumn::make('action')
                    ->label(__('superadmin.organizations.relations.activity_logs.columns.action'))
                    ->badge()
                    ->state(fn (OrganizationActivityLog $record): string => OrganizationActivityLogPresenter::actionLabel($record)),
                TextColumn::make('resource_label')
                    ->label(__('superadmin.organizations.relations.activity_logs.columns.record'))
                    ->state(fn (OrganizationActivityLog $record): string => OrganizationActivityLogPresenter::resourceLabel($record))
                    ->wrap(),
                TextColumn::make('ip_address')
                    ->label(__('superadmin.organizations.relations.activity_logs.columns.ip_address'))
                    ->default(__('superadmin.organizations.relations.activity_logs.placeholders.unknown')),
                TextColumn::make('created_at')
                    ->label(__('superadmin.organizations.relations.activity_logs.columns.when'))
                    ->since()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('viewChanges')
                    ->label(__('superadmin.organizations.relations.activity_logs.actions.view_changes'))
                    ->modalHeading(__('superadmin.organizations.relations.activity_logs.modals.changes_heading'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('superadmin.organizations.relations.activity_logs.actions.close'))
                    ->modalContent(fn (OrganizationActivityLog $record): View => view(
                        'filament.resources.organizations.activity-log-diff',
                        [
                            'activityLog' => $record,
                            'resourceLabel' => OrganizationActivityLogPresenter::resourceLabel($record),
                            'rows' => OrganizationActivityLogPresenter::diffRows($record),
                        ],
                    )),
                Action::make('openAuditTimeline')
                    ->label(__('superadmin.organizations.relations.activity_logs.actions.open_audit_timeline'))
                    ->url(fn (OrganizationActivityLog $record): string => app(OrganizationDashboardData::class)->auditTimelineUrlForActivityLog(
                        $this->getOwnerRecord(),
                        $record,
                    )),
            ])
            ->recordAction('viewChanges')
            ->defaultSort('created_at', 'desc');
    }
}
