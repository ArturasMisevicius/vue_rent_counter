<?php

namespace App\Filament\Resources\Tenants\Schemas;

use App\Filament\Resources\Properties\PropertyResource;
use App\Models\User;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TenantInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.tenants.sections.summary'))
                    ->schema([
                        TextEntry::make('currentPropertyAssignment.property.name')
                            ->label(__('admin.tenants.fields.property'))
                            ->state(fn (User $record): string => $record->currentProperty?->name ?? __('admin.tenants.empty.unassigned'))
                            ->url(fn (User $record): ?string => $record->currentProperty !== null
                                ? PropertyResource::getUrl('view', ['record' => $record->currentProperty])
                                : null),
                        TextEntry::make('current_unit_area')
                            ->label(__('admin.tenants.fields.unit_area'))
                            ->state(fn (User $record): string => $record->currentUnitAreaDisplay()),
                        TextEntry::make('status')
                            ->label(__('admin.tenants.fields.account_status'))
                            ->badge(),
                        TextEntry::make('total_paid')
                            ->label(__('admin.tenants.fields.total_paid'))
                            ->state(fn (User $record): string => $record->totalPaidDisplay()),
                    ])
                    ->columns(4),
                Section::make(__('admin.tenants.sections.personal_information'))
                    ->schema([
                        TextEntry::make('organization.name')
                            ->label(__('superadmin.organizations.singular'))
                            ->placeholder(__('superadmin.users.placeholders.no_organization')),
                        TextEntry::make('name')
                            ->label(__('admin.tenants.fields.full_name')),
                        TextEntry::make('email')
                            ->label(__('admin.tenants.fields.email_address')),
                        TextEntry::make('phone')
                            ->label(__('admin.tenants.fields.phone_number'))
                            ->default('—'),
                        TextEntry::make('locale')
                            ->label(__('admin.tenants.fields.preferred_language'))
                            ->state(fn (User $record): string => (string) (config('tenanto.locales')[$record->locale] ?? $record->locale)),
                    ])
                    ->columns(2),
                Section::make(__('admin.tenants.sections.account_activity'))
                    ->schema([
                        TextEntry::make('email_verified_at')
                            ->label(__('admin.tenants.fields.email_verified_at'))
                            ->state(fn (User $record): string => $record->email_verified_at?->locale(app()->getLocale())->isoFormat('LLL') ?? __('admin.tenants.empty.not_verified')),
                        TextEntry::make('created_at')
                            ->label(__('admin.tenants.fields.account_created'))
                            ->state(fn (User $record): string => $record->created_at?->locale(app()->getLocale())->isoFormat('LLL') ?? '—'),
                        TextEntry::make('updated_at')
                            ->label(__('admin.tenants.fields.updated_at'))
                            ->state(fn (User $record): string => $record->updated_at?->locale(app()->getLocale())->isoFormat('LLL') ?? '—'),
                        TextEntry::make('last_login_at')
                            ->label(__('admin.tenants.fields.last_login'))
                            ->state(fn (User $record): string => $record->last_login_at?->locale(app()->getLocale())->isoFormat('LLL') ?? __('admin.tenants.empty.never')),
                        TextEntry::make('suspended_at')
                            ->label(__('admin.tenants.fields.suspended_at'))
                            ->state(fn (User $record): string => $record->suspended_at?->locale(app()->getLocale())->isoFormat('LLL') ?? '—')
                            ->visible(fn (User $record): bool => $record->suspended_at !== null),
                        TextEntry::make('suspension_reason')
                            ->label(__('admin.tenants.fields.suspension_reason'))
                            ->default('—')
                            ->visible(fn (User $record): bool => filled($record->suspension_reason)),
                    ])
                    ->columns(2),
                Section::make(__('admin.tenants.sections.property_assignment'))
                    ->schema([
                        TextEntry::make('currentPropertyAssignment.property.name')
                            ->label(__('admin.tenants.fields.current_property'))
                            ->state(fn (User $record): string => $record->currentProperty?->name ?? __('admin.tenants.empty.unassigned'))
                            ->url(fn (User $record): ?string => $record->currentProperty !== null
                                ? PropertyResource::getUrl('view', ['record' => $record->currentProperty])
                                : null),
                        TextEntry::make('currentPropertyAssignment.property.building.name')
                            ->label(__('admin.tenants.fields.building'))
                            ->default('—'),
                        TextEntry::make('currentPropertyAssignment.property.floor')
                            ->label(__('admin.tenants.fields.floor'))
                            ->state(fn (User $record): string => $record->currentProperty?->floorDisplay() ?? '—'),
                        TextEntry::make('currentPropertyAssignment.assigned_at')
                            ->label(__('admin.tenants.fields.assigned_since'))
                            ->state(fn (User $record): string => $record->currentPropertyAssignment?->assigned_at?->locale(app()->getLocale())->isoFormat('ll') ?? '—'),
                    ])
                    ->columns(2),
            ]);
    }
}
