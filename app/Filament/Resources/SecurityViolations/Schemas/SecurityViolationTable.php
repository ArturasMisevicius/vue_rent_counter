<?php

namespace App\Filament\Resources\SecurityViolations\Schemas;

use App\Enums\SecurityViolationSeverity;
use App\Enums\SecurityViolationType;
use App\Filament\Actions\Superadmin\Security\BlockIpAddressAction;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Filament\Support\Superadmin\SecurityViolations\SecurityViolationTablePresenter;
use App\Models\Organization;
use App\Models\SecurityViolation;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SecurityViolationTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('type')
                    ->label(__('superadmin.security_violations.columns.type'))
                    ->state(fn (SecurityViolation $record): string => $record->type->label()),
                TextColumn::make('severity')
                    ->label(__('superadmin.security_violations.columns.severity'))
                    ->badge()
                    ->color(fn (SecurityViolation $record): string => SecurityViolationTablePresenter::severityColor($record))
                    ->state(fn (SecurityViolation $record): string => $record->severity->label()),
                TextColumn::make('ip_address')
                    ->label(__('superadmin.security_violations.columns.ip_address'))
                    ->placeholder(__('superadmin.security_violations.placeholders.empty')),
                TextColumn::make('user_summary')
                    ->label(__('superadmin.security_violations.columns.user'))
                    ->state(fn (SecurityViolation $record): string => $record->user?->name ?? __('superadmin.security_violations.placeholders.anonymous'))
                    ->description(fn (SecurityViolation $record): ?string => $record->user?->email)
                    ->wrap(),
                TextColumn::make('url')
                    ->label(__('superadmin.security_violations.columns.url'))
                    ->state(fn (SecurityViolation $record): string => SecurityViolationTablePresenter::urlPath($record))
                    ->wrap(),
                TextColumn::make('user_agent_summary')
                    ->label(__('superadmin.security_violations.columns.user_agent_summary'))
                    ->state(fn (SecurityViolation $record): string => SecurityViolationTablePresenter::userAgentSummary($record))
                    ->wrap(),
                TextColumn::make('occurred_at')
                    ->label(__('superadmin.security_violations.columns.timestamp'))
                    ->state(fn (SecurityViolation $record): string => $record->occurred_at?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateTimeFormat()) ?? __('superadmin.security_violations.placeholders.empty'))
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label(__('superadmin.security_violations.filters.organization'))
                    ->placeholder(__('superadmin.security_violations.placeholders.all'))
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->searchable()
                    ->preload()
                    ->query(fn (Builder $query, array $data): Builder => $query->forOrganizationValue($data['value'] ?? null)),
                SelectFilter::make('review_status')
                    ->label(__('superadmin.security_violations.filters.review_status'))
                    ->placeholder(__('superadmin.security_violations.placeholders.all'))
                    ->options([
                        'reviewed' => __('superadmin.security_violations.review_status_options.reviewed'),
                        'unreviewed' => __('superadmin.security_violations.review_status_options.unreviewed'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->forReviewStatus($data['value'] ?? null)),
                SelectFilter::make('severity')
                    ->label(__('superadmin.security_violations.filters.severity'))
                    ->placeholder(__('superadmin.security_violations.placeholders.all'))
                    ->options(SecurityViolationSeverity::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->forSeverityValue($data['value'] ?? null)),
                SelectFilter::make('type')
                    ->label(__('superadmin.security_violations.filters.type'))
                    ->placeholder(__('superadmin.security_violations.placeholders.all'))
                    ->options(SecurityViolationType::options())
                    ->query(fn (Builder $query, array $data): Builder => $query->forTypeValue($data['value'] ?? null)),
                Filter::make('occurred_between')
                    ->label(__('superadmin.security_violations.filters.date_range'))
                    ->schema([
                        DatePicker::make('occurred_from')
                            ->label(__('superadmin.security_violations.filters.from')),
                        DatePicker::make('occurred_to')
                            ->label(__('superadmin.security_violations.filters.to')),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query->occurredBetween(
                        $data['occurred_from'] ?? null,
                        $data['occurred_to'] ?? null,
                    )),
            ])
            ->recordActions([
                Action::make('review')
                    ->label(__('superadmin.security_violations.actions.review'))
                    ->color('success')
                    ->authorize(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->hidden(fn (SecurityViolation $record): bool => $record->isReviewed())
                    ->modalHeading(__('superadmin.security_violations.modals.review_heading'))
                    ->modalDescription(__('superadmin.security_violations.modals.review_description'))
                    ->modalSubmitActionLabel(__('superadmin.security_violations.actions.review'))
                    ->modalCancelActionLabel(__('superadmin.security_violations.actions.cancel'))
                    ->form([
                        Textarea::make('note')
                            ->label(__('superadmin.security_violations.forms.note'))
                            ->rows(4)
                            ->required(),
                    ])
                    ->action(function (SecurityViolation $record, array $data): void {
                        $reviewer = self::currentUser();

                        if (! $reviewer instanceof User) {
                            return;
                        }

                        $record->markAsReviewed($reviewer, $data['note'] ?? null);

                        Notification::make()
                            ->title(__('superadmin.security_violations.messages.reviewed'))
                            ->success()
                            ->send();
                    }),
                Action::make('blockIp')
                    ->label(__('superadmin.security_violations.actions.block_ip_address'))
                    ->color('danger')
                    ->authorize(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->hidden(fn (SecurityViolation $record): bool => blank($record->ip_address))
                    ->requiresConfirmation()
                    ->modalHeading(__('superadmin.security_violations.modals.block_ip_heading'))
                    ->modalDescription(fn (SecurityViolation $record): string => __('superadmin.security_violations.modals.block_ip_description', ['ip' => $record->ip_address]))
                    ->modalSubmitActionLabel(__('superadmin.security_violations.actions.block_ip'))
                    ->modalCancelActionLabel(__('superadmin.security_violations.actions.cancel'))
                    ->action(function (SecurityViolation $record, BlockIpAddressAction $blockIpAddressAction): void {
                        $blockIpAddressAction->handle([
                            'ip_address' => $record->ip_address,
                            'reason' => __('superadmin.security_violations.messages.block_reason', ['id' => $record->id]),
                            'blocked_by_user_id' => self::currentUser()?->getKey(),
                        ]);

                        Notification::make()
                            ->title(__('superadmin.security_violations.messages.blocked'))
                            ->success()
                            ->send();
                    }),
            ])
            ->deferFilters(false)
            ->filtersLayout(FiltersLayout::AboveContent)
            ->defaultSort('occurred_at', 'desc');
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
