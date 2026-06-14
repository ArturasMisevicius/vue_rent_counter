<?php

namespace App\Filament\Resources\ServiceConfigurations\Tables;

use App\Enums\BillingMethod;
use App\Enums\ServiceConfigurationStatus;
use App\Filament\Actions\Admin\ServiceConfigurations\ArchiveServiceConfigurationAction;
use App\Filament\Actions\Admin\ServiceConfigurations\DuplicateServiceConfigurationAction;
use App\Filament\Resources\ServiceConfigurations\ServiceConfigurationResource;
use App\Filament\Support\Admin\ServiceConfigurations\ValidateServiceConfiguration;
use App\Filament\Support\Formatting\LocalizedDateFormatter;
use App\Models\Organization;
use App\Models\Property;
use App\Models\ServiceConfiguration;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class ServiceConfigurationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('property.name')
                    ->label(__('admin.service_configurations.columns.property'))
                    ->state(fn (ServiceConfiguration $record): string => $record->property?->displayName() ?? '—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('service_name')
                    ->label(__('admin.service_configurations.columns.service_name'))
                    ->searchable()
                    ->sortable()
                    ->default('—'),
                TextColumn::make('utilityService.name')
                    ->label(__('admin.service_configurations.columns.utility_service'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('billing_method')
                    ->label(__('admin.service_configurations.columns.billing_method'))
                    ->badge()
                    ->state(fn (ServiceConfiguration $record): string => $record->billing_method?->getLabel() ?? '—'),
                TextColumn::make('status')
                    ->label(__('admin.service_configurations.columns.status'))
                    ->badge()
                    ->state(fn (ServiceConfiguration $record): string => $record->status?->getLabel() ?? '—'),
                TextColumn::make('configuration_health')
                    ->label(__('admin.service_configurations.columns.health'))
                    ->badge()
                    ->state(fn (ServiceConfiguration $record): string => self::healthLabel($record))
                    ->color(fn (ServiceConfiguration $record): string => self::healthColor($record)),
                TextColumn::make('provider.name')
                    ->label(__('admin.service_configurations.columns.provider'))
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('tariff.name')
                    ->label(__('admin.service_configurations.fields.tariff'))
                    ->toggleable(),
                TextColumn::make('pricing_model')
                    ->label(__('admin.service_configurations.columns.pricing_model'))
                    ->badge()
                    ->state(fn (ServiceConfiguration $record): string => $record->pricing_model?->getLabel() ?? '—'),
                TextColumn::make('distribution_method')
                    ->label(__('admin.service_configurations.fields.distribution_method'))
                    ->badge()
                    ->state(fn (ServiceConfiguration $record): string => $record->distribution_method?->getLabel() ?? '—')
                    ->toggleable(),
                TextColumn::make('effective_from')
                    ->label(__('admin.service_configurations.fields.effective_from'))
                    ->state(fn (ServiceConfiguration $record): string => $record->effective_from?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('effective_until')
                    ->label(__('admin.service_configurations.fields.effective_until'))
                    ->state(fn (ServiceConfiguration $record): string => $record->effective_until?->locale(app()->getLocale())->translatedFormat(LocalizedDateFormatter::dateFormat()) ?? '—')
                    ->placeholder('—')
                    ->toggleable(),
                IconColumn::make('is_active')
                    ->label(__('admin.service_configurations.fields.is_active'))
                    ->boolean(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label(__('superadmin.organizations.singular'))
                    ->visible(fn (): bool => static::currentUser()?->isSuperadmin() ?? false)
                    ->options(fn (): array => Organization::query()
                        ->select(['id', 'name'])
                        ->ordered()
                        ->pluck('name', 'id')
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->forOrganizationValue($data['value'] ?? null)),
                SelectFilter::make('property')
                    ->label(__('admin.service_configurations.fields.property'))
                    ->options(fn (): array => Property::query()
                        ->select(['id', 'organization_id', 'building_id', 'name'])
                        ->when(
                            ! (static::currentUser()?->isSuperadmin() ?? false),
                            fn (Builder $query): Builder => $query->where('organization_id', static::currentUser()?->organization_id),
                        )
                        ->orderBy('name')
                        ->orderBy('id')
                        ->get()
                        ->mapWithKeys(fn (Property $property): array => [$property->id => $property->displayName()])
                        ->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->forPropertyValue($data['value'] ?? null)),
                TernaryFilter::make('is_active')
                    ->label(__('admin.service_configurations.fields.is_active'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->forActiveValue(true),
                        false: fn (Builder $query): Builder => $query->forActiveValue(false),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                SelectFilter::make('billing_method')
                    ->label(__('admin.service_configurations.fields.billing_method'))
                    ->options(BillingMethod::options()),
                SelectFilter::make('status')
                    ->label(__('admin.service_configurations.fields.status'))
                    ->options(ServiceConfigurationStatus::options()),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
                EditAction::make()
                    ->label(__('admin.actions.edit')),
                Action::make('duplicate')
                    ->label(__('admin.service_configurations.actions.duplicate'))
                    ->icon('heroicon-m-square-2-stack')
                    ->authorize(fn (ServiceConfiguration $record): bool => ServiceConfigurationResource::canCreate())
                    ->action(function (ServiceConfiguration $record, DuplicateServiceConfigurationAction $duplicateServiceConfigurationAction): void {
                        $duplicateServiceConfigurationAction->handle($record);

                        Notification::make()
                            ->title(__('admin.service_configurations.messages.duplicated'))
                            ->success()
                            ->send();
                    }),
                Action::make('testCalculation')
                    ->label(__('admin.service_configurations.actions.test_calculation'))
                    ->icon('heroicon-m-calculator')
                    ->modalHeading(__('admin.service_configurations.preview.title'))
                    ->modalSubmitActionLabel(__('admin.service_configurations.actions.test_calculation'))
                    ->action(function (ServiceConfiguration $record): void {
                        $result = app(ValidateServiceConfiguration::class)->handle($record);
                        $notification = Notification::make()
                            ->title($result['blocking_errors'] === []
                                ? __('admin.service_configurations.preview.valid')
                                : __('admin.service_configurations.preview.blocked'))
                            ->body(collect($result['blocking_errors'])->implode("\n"));

                        ($result['blocking_errors'] === [] ? $notification->success() : $notification->danger())
                            ->send();
                    }),
                Action::make('archive')
                    ->label(__('admin.service_configurations.actions.archive'))
                    ->icon('heroicon-m-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->authorize(fn (ServiceConfiguration $record): bool => ServiceConfigurationResource::canEdit($record))
                    ->visible(fn (ServiceConfiguration $record): bool => $record->status !== ServiceConfigurationStatus::ARCHIVED)
                    ->action(function (ServiceConfiguration $record, ArchiveServiceConfigurationAction $archiveServiceConfigurationAction): void {
                        $archiveServiceConfigurationAction->handle($record);

                        Notification::make()
                            ->title(__('admin.service_configurations.messages.archived'))
                            ->success()
                            ->send();
                    }),
                DeleteAction::make()
                    ->label(__('admin.actions.delete'))
                    ->visible(fn (ServiceConfiguration $record): bool => $record->canBeDeletedFromAdminWorkspace())
                    ->authorize(fn (ServiceConfiguration $record): bool => ServiceConfigurationResource::canDelete($record)),
            ]);
    }

    private static function healthLabel(ServiceConfiguration $record): string
    {
        $result = app(ValidateServiceConfiguration::class)->handle($record);

        if ($result['blocking_errors'] !== []) {
            return __('admin.service_configurations.health.configuration_error');
        }

        if ($result['warnings'] !== []) {
            return __('admin.service_configurations.health.warning');
        }

        return __('admin.service_configurations.health.valid');
    }

    private static function healthColor(ServiceConfiguration $record): string
    {
        $result = app(ValidateServiceConfiguration::class)->handle($record);

        if ($result['blocking_errors'] !== []) {
            return 'danger';
        }

        if ($result['warnings'] !== []) {
            return 'warning';
        }

        return 'success';
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }
}
