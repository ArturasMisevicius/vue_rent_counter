<?php

declare(strict_types=1);

namespace App\Filament\Clusters\SuperAdmin\Resources\SystemUserResource\Pages;

use App\Contracts\SuperAdminUserInterface;
use App\Filament\Clusters\SuperAdmin\Resources\SystemUserResource;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use App\ValueObjects\ActivityReport;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\Grid as InfoGrid;
use Filament\Infolists\Components\Section as InfoSection;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\Page;
use Filament\Tables\Actions\Action as TableAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

final class UserActivityReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = SystemUserResource::class;

    protected static string $view = 'filament.superadmin.pages.user-activity-report';

    public User $record;
    public ActivityReport $activityReport;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);
        $this->generateActivityReport();
    }

    public function getTitle(): string
    {
        return __('superadmin.users.pages.activity_report.title', [
            'user' => $this->record->name,
        ]);
    }

    public function getBreadcrumb(): string
    {
        return __('superadmin.users.pages.activity_report.breadcrumb');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('superadmin.users.actions.refresh_report'))
                ->icon('heroicon-o-arrow-path')
                ->action(fn () => $this->generateActivityReport()),

            Action::make('export')
                ->label(__('superadmin.users.actions.export_report'))
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(function () {
                    // TODO: Implement export functionality
                    \Filament\Notifications\Notification::make()
                        ->title(__('superadmin.users.notifications.export_started'))
                        ->body(__('superadmin.users.notifications.export_started_body'))
                        ->info()
                        ->send();
                }),

            Action::make('back')
                ->label(__('superadmin.users.actions.back_to_user'))
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->url(fn () => static::$resource::getUrl('view', ['record' => $this->record])),
        ];
    }

    public function activitySummaryInfolist(): Infolist
    {
        return Infolist::make()
            ->record($this->record)
            ->schema([
                InfoSection::make(__('superadmin.users.sections.activity_overview'))
                    ->schema([
                        InfoGrid::make(4)->schema([
                            TextEntry::make('total_sessions')
                                ->label(__('superadmin.users.fields.total_sessions'))
                                ->getStateUsing(fn () => $this->activityReport->totalSessions)
                                ->icon('heroicon-o-device-phone-mobile')
                                ->color('info'),

                            TextEntry::make('audit_entries')
                                ->label(__('superadmin.users.fields.audit_entries'))
                                ->getStateUsing(fn () => $this->activityReport->auditLogEntries)
                                ->icon('heroicon-o-document-text')
                                ->color('warning'),

                            TextEntry::make('last_login')
                                ->label(__('superadmin.users.fields.last_login'))
                                ->getStateUsing(fn () => $this->activityReport->lastLoginAt?->diffForHumans() ?? __('superadmin.users.values.never'))
                                ->icon('heroicon-o-clock')
                                ->color('success'),

                            TextEntry::make('report_generated')
                                ->label(__('superadmin.users.fields.report_generated'))
                                ->getStateUsing(fn () => $this->activityReport->generatedAt->format('M j, Y H:i'))
                                ->icon('heroicon-o-calendar')
                                ->color('gray'),
                        ]),
                    ])
                    ->columns(1),

                InfoSection::make(__('superadmin.users.sections.tenant_information'))
                    ->schema([
                        InfoGrid::make(2)->schema([
                            TextEntry::make('organization.name')
                                ->label(__('superadmin.users.fields.organization'))
                                ->icon('heroicon-o-building-office')
                                ->url(fn () => $this->record->organization ? 
                                    route('filament.superadmin.resources.tenants.view', $this->record->organization) : null)
                                ->color('primary'),

                            TextEntry::make('organization.status')
                                ->label(__('superadmin.users.fields.tenant_status'))
                                ->badge()
                                ->color(fn ($state) => $state?->getColor()),
                        ]),
                    ])
                    ->columns(1)
                    ->visible(fn () => $this->record->organization !== null),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SuperAdminAuditLog::query()
                    ->where(function (Builder $query) {
                        $query->where('admin_id', $this->record->id)
                              ->orWhere('target_id', $this->record->id);
                    })
                    ->latest()
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('superadmin.audit.fields.timestamp'))
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->description(fn ($record) => $record->created_at->format('M j, Y H:i:s')),

                TextColumn::make('action')
                    ->label(__('superadmin.audit.fields.action'))
                    ->badge()
                    ->color(fn ($state) => $state->getColor())
                    ->formatStateUsing(fn ($state) => $state->getLabel()),

                TextColumn::make('admin.name')
                    ->label(__('superadmin.audit.fields.admin'))
                    ->description(fn ($record) => $record->admin?->email)
                    ->searchable(['name', 'email'])
                    ->sortable(),

                TextColumn::make('target_type')
                    ->label(__('superadmin.audit.fields.target'))
                    ->formatStateUsing(function ($record) {
                        if (!$record->target_type) {
                            return '—';
                        }
                        
                        $type = class_basename($record->target_type);
                        $id = $record->target_id;
                        
                        return "{$type} #{$id}";
                    })
                    ->description(function ($record) {
                        if ($record->target_type === User::class && $record->target_id) {
                            $user = User::find($record->target_id);
                            return $user ? $user->email : null;
                        }
                        return null;
                    }),

                TextColumn::make('ip_address')
                    ->label(__('superadmin.audit.fields.ip_address'))
                    ->toggleable()
                    ->copyable(),

                TextColumn::make('impersonation_session_id')
                    ->label(__('superadmin.audit.fields.impersonation'))
                    ->badge()
                    ->color('warning')
                    ->visible(fn ($record) => $record->impersonation_session_id !== null)
                    ->formatStateUsing(fn () => __('superadmin.audit.values.impersonated'))
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('action')
                    ->label(__('superadmin.audit.filters.action'))
                    ->options(\App\Enums\AuditAction::class)
                    ->multiple(),

                Filter::make('date_range')
                    ->label(__('superadmin.audit.filters.date_range'))
                    ->form([
                        Grid::make(2)->schema([
                            DatePicker::make('from')
                                ->label(__('superadmin.audit.fields.from_date')),
                            DatePicker::make('to')
                                ->label(__('superadmin.audit.fields.to_date')),
                        ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['to'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),

                SelectFilter::make('impersonation')
                    ->label(__('superadmin.audit.filters.impersonation'))
                    ->options([
                        'yes' => __('superadmin.audit.values.impersonated_only'),
                        'no' => __('superadmin.audit.values.direct_only'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if ($data['value'] === 'yes') {
                            return $query->whereNotNull('impersonation_session_id');
                        }
                        if ($data['value'] === 'no') {
                            return $query->whereNull('impersonation_session_id');
                        }
                        return $query;
                    }),
            ])
            ->actions([
                TableAction::make('view_details')
                    ->label(__('superadmin.audit.actions.view_details'))
                    ->icon('heroicon-o-eye')
                    ->modalHeading(__('superadmin.audit.modals.details.heading'))
                    ->modalContent(fn ($record) => view('filament.superadmin.modals.audit-log-details', [
                        'auditLog' => $record,
                    ]))
                    ->modalWidth('2xl'),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s')
            ->striped();
    }

    private function generateActivityReport(): void
    {
        $userService = app(SuperAdminUserInterface::class);
        $this->activityReport = $userService->getUserActivityAcrossTenants($this->record);
    }

    protected function resolveRecord(int|string $id): User
    {
        return static::$resource::resolveRecordRouteBinding($id);
    }
}