<?php

namespace App\Filament\Resources\OrganizationResource\Pages;


use BackedEnum;
use App\Enums\SubscriptionPlanType;
use App\Filament\Resources\OrganizationResource;
use Filament\Actions;
use Filament\Forms;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Illuminate\Support\Facades\DB;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('suspend')
                ->icon('heroicon-o-pause-circle')
                ->color('warning')
                ->requiresConfirmation()
                ->form([
                    Forms\Components\Textarea::make('reason')
                        ->required()
                        ->label(__('organizations.labels.suspension_reason'))
                        ->maxLength(500),
                ])
                ->action(function ($record, array $data): void {
                    $record->suspend($data['reason']);
                })
                ->visible(fn ($record): bool => ! $record->isSuspended())
                ->successNotificationTitle(__('organizations.actions.suspend')),
            Actions\Action::make('reactivate')
                ->icon('heroicon-o-play-circle')
                ->color('success')
                ->requiresConfirmation()
                ->action(fn ($record): bool => $record->reactivate())
                ->visible(fn ($record): bool => $record->isSuspended())
                ->successNotificationTitle(__('organizations.actions.reactivate')),
            Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalHeading(__('organizations.modals.delete_heading'))
                ->modalDescription(__('organizations.modals.delete_description'))
                ->before(function (Actions\DeleteAction $action, $record) {
                    // Check if organization has any relations
                    $hasUsers = $record->users()->exists();
                    $hasProperties = $record->properties()->exists();
                    $hasBuildings = $record->buildings()->exists();
                    $hasInvoices = $record->invoices()->exists();
                    $hasMeters = $record->meters()->exists();
                    $hasTenants = $record->tenants()->exists();
                    
                    if ($hasUsers || $hasProperties || $hasBuildings || $hasInvoices || $hasMeters || $hasTenants) {
                        \Filament\Notifications\Notification::make()
                            ->title(__('organizations.notifications.cannot_delete'))
                            ->body(__('organizations.notifications.has_relations', [
                                'users' => $record->users()->count(),
                                'properties' => $record->properties()->count(),
                                'buildings' => $record->buildings()->count(),
                                'invoices' => $record->invoices()->count(),
                                'meters' => $record->meters()->count(),
                                'tenants' => $record->tenants()->count(),
                            ]))
                            ->danger()
                            ->persistent()
                            ->send();
                        
                        $action->cancel();
                    }
                })
                ->after(function ($record) {
                    // Delete all relations in proper order
                    DB::transaction(function () use ($record) {
                        // Delete activity logs
                        $record->activityLogs()->delete();
                        
                        // Delete invitations
                        $record->invitations()->delete();
                    });
                })
                ->successNotificationTitle(__('organizations.notifications.deleted'))
                ->successRedirectUrl(OrganizationResource::getUrl('index')),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('organizations.sections.details'))
                    ->schema([
                        Infolists\Components\TextEntry::make('name')->label(__('organizations.labels.name')),
                        Infolists\Components\TextEntry::make('slug')->label(__('organizations.labels.slug')),
                        Infolists\Components\TextEntry::make('email')->label(__('organizations.labels.email')),
                        Infolists\Components\TextEntry::make('phone')->label(__('organizations.labels.phone')),
                        Infolists\Components\TextEntry::make('domain')->label(__('organizations.labels.domain')),
                    ])->columns(2),

                Section::make(__('organizations.sections.subscription'))
                    ->schema([
                        Infolists\Components\TextEntry::make('plan')
                            ->badge()
                            ->formatStateUsing(fn ($state) => enum_label($state, SubscriptionPlanType::class))
                            ->color(fn ($state): string => match ($state instanceof BackedEnum ? $state->value : $state) {
                                SubscriptionPlanType::BASIC->value => 'gray',
                                SubscriptionPlanType::PROFESSIONAL->value => 'info',
                                SubscriptionPlanType::ENTERPRISE->value => 'success',
                                default => 'gray',
                            }),
                        Infolists\Components\TextEntry::make('max_properties')
                            ->label(__('organizations.labels.max_properties')),
                        Infolists\Components\TextEntry::make('max_users')
                            ->label(__('organizations.labels.max_users')),
                        Infolists\Components\TextEntry::make('trial_ends_at')
                            ->dateTime()
                            ->placeholder(__('organizations.labels.not_on_trial')),
                        Infolists\Components\TextEntry::make('subscription_ends_at')
                            ->dateTime()
                            ->color(fn ($record) => $record->subscription_ends_at?->isPast() ? 'danger' : 'success'),
                    ])->columns(3),

                Section::make(__('organizations.sections.usage_statistics'))
                    ->schema([
                        Infolists\Components\TextEntry::make('users_count')
                            ->label(__('organizations.labels.total_users'))
                            ->state(fn ($record) => $record->users()->count()),
                        Infolists\Components\TextEntry::make('properties_count')
                            ->label(__('organizations.labels.total_properties'))
                            ->state(fn ($record) => $record->properties()->count()),
                        Infolists\Components\TextEntry::make('buildings_count')
                            ->label(__('organizations.labels.total_buildings'))
                            ->state(fn ($record) => $record->buildings()->count()),
                        Infolists\Components\TextEntry::make('invoices_count')
                            ->label(__('organizations.labels.total_invoices'))
                            ->state(fn ($record) => $record->invoices()->count()),
                        Infolists\Components\TextEntry::make('remaining_properties')
                            ->label(__('organizations.labels.remaining_properties'))
                            ->state(fn ($record) => $record->getRemainingProperties()),
                        Infolists\Components\TextEntry::make('remaining_users')
                            ->label(__('organizations.labels.remaining_users'))
                            ->state(fn ($record) => $record->getRemainingUsers()),
                    ])->columns(3),

                Section::make(__('organizations.sections.regional'))
                    ->schema([
                        Infolists\Components\TextEntry::make('timezone')->label(__('organizations.labels.timezone')),
                        Infolists\Components\TextEntry::make('locale')->label(__('organizations.labels.locale')),
                        Infolists\Components\TextEntry::make('currency')->label(__('organizations.labels.currency')),
                    ])->columns(3),

                Section::make(__('organizations.sections.status'))
                    ->schema([
                        Infolists\Components\IconEntry::make('is_active')
                            ->boolean()
                            ->label(__('organizations.labels.is_active')),
                        Infolists\Components\TextEntry::make('suspended_at')
                            ->dateTime()
                            ->placeholder(__('organizations.labels.not_suspended')),
                        Infolists\Components\TextEntry::make('suspension_reason')
                            ->placeholder(__('app.common.na')),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label(__('organizations.labels.created_at'))
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label(__('organizations.labels.updated_at'))
                            ->dateTime(),
                    ])->columns(2),

                Section::make(__('organizations.sections.relations'))
                    ->schema([
                        Infolists\Components\TextEntry::make('users_relation')
                            ->label(__('organizations.labels.users'))
                            ->state(function ($record) {
                                $count = $record->users()->count();
                                if ($count === 0) return __('app.common.none');
                                
                                $users = $record->users()->limit(5)->get();
                                $list = $users->pluck('name')->join(', ');
                                
                                return $count > 5 
                                    ? $list . ' ' . __('organizations.labels.and_more', ['count' => $count - 5])
                                    : $list;
                            })
                            ->badge()
                            ->color(fn ($record) => $record->users()->count() > 0 ? 'success' : 'gray'),
                        
                        Infolists\Components\TextEntry::make('properties_relation')
                            ->label(__('organizations.labels.properties'))
                            ->state(function ($record) {
                                $count = $record->properties()->count();
                                if ($count === 0) return __('app.common.none');
                                
                                $properties = $record->properties()->limit(5)->get();
                                $list = $properties->pluck('address')->join(', ');
                                
                                return $count > 5 
                                    ? $list . ' ' . __('organizations.labels.and_more', ['count' => $count - 5])
                                    : $list;
                            })
                            ->badge()
                            ->color(fn ($record) => $record->properties()->count() > 0 ? 'success' : 'gray'),
                        
                        Infolists\Components\TextEntry::make('buildings_relation')
                            ->label(__('organizations.labels.buildings'))
                            ->state(function ($record) {
                                $count = $record->buildings()->count();
                                if ($count === 0) return __('app.common.none');
                                
                                $buildings = $record->buildings()->limit(5)->get();
                                $list = $buildings->pluck('name')->join(', ');
                                
                                return $count > 5 
                                    ? $list . ' ' . __('organizations.labels.and_more', ['count' => $count - 5])
                                    : $list;
                            })
                            ->badge()
                            ->color(fn ($record) => $record->buildings()->count() > 0 ? 'success' : 'gray'),
                        
                        Infolists\Components\TextEntry::make('invoices_relation')
                            ->label(__('organizations.labels.invoices'))
                            ->state(function ($record) {
                                $count = $record->invoices()->count();
                                if ($count === 0) return __('app.common.none');
                                
                                return __('organizations.labels.invoice_count', ['count' => $count]);
                            })
                            ->badge()
                            ->color(fn ($record) => $record->invoices()->count() > 0 ? 'info' : 'gray'),
                        
                        Infolists\Components\TextEntry::make('meters_relation')
                            ->label(__('organizations.labels.meters'))
                            ->state(function ($record) {
                                $count = $record->meters()->count();
                                if ($count === 0) return __('app.common.none');
                                
                                return __('organizations.labels.meter_count', ['count' => $count]);
                            })
                            ->badge()
                            ->color(fn ($record) => $record->meters()->count() > 0 ? 'info' : 'gray'),
                        
                        Infolists\Components\TextEntry::make('tenants_relation')
                            ->label(__('organizations.labels.tenants'))
                            ->state(function ($record) {
                                $count = $record->tenants()->count();
                                if ($count === 0) return __('app.common.none');
                                
                                $tenants = $record->tenants()->limit(5)->get();
                                $list = $tenants->pluck('name')->join(', ');
                                
                                return $count > 5 
                                    ? $list . ' ' . __('organizations.labels.and_more', ['count' => $count - 5])
                                    : $list;
                            })
                            ->badge()
                            ->color(fn ($record) => $record->tenants()->count() > 0 ? 'warning' : 'gray'),
                        
                        Infolists\Components\TextEntry::make('invitations_relation')
                            ->label(__('organizations.labels.invitations'))
                            ->state(function ($record) {
                                $count = $record->invitations()->count();
                                if ($count === 0) return __('app.common.none');
                                
                                return __('organizations.labels.invitation_count', ['count' => $count]);
                            })
                            ->badge()
                            ->color(fn ($record) => $record->invitations()->count() > 0 ? 'info' : 'gray'),
                        
                        Infolists\Components\TextEntry::make('activity_logs_relation')
                            ->label(__('organizations.labels.activity_logs'))
                            ->state(function ($record) {
                                $count = $record->activityLogs()->count();
                                if ($count === 0) return __('app.common.none');
                                
                                return __('organizations.labels.log_count', ['count' => $count]);
                            })
                            ->badge()
                            ->color(fn ($record) => $record->activityLogs()->count() > 0 ? 'gray' : 'gray'),
                    ])->columns(2)
                    ->description(__('organizations.sections.relations_description')),
           ]);
   }
}
