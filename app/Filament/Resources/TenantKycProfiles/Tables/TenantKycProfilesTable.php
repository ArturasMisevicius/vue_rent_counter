<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenantKycProfiles\Tables;

use App\Enums\TenantKycProfileStatus;
use App\Filament\Actions\TenantKyc\ApproveTenantKycProfile;
use App\Filament\Actions\TenantKyc\RejectTenantKycProfile;
use App\Filament\Resources\TenantKycProfiles\TenantKycProfileResource;
use App\Models\TenantKycProfile;
use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TenantKycProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => self::applyAttentionQuery($query))
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('admin.tenant_kyc.columns.organization'))
                    ->visible(fn (): bool => TenantKycProfileResource::currentUser()?->isSuperadmin() ?? false)
                    ->toggleable(),
                TextColumn::make('tenant.name')
                    ->label(__('admin.tenant_kyc.columns.tenant'))
                    ->url(fn (TenantKycProfile $record): string => TenantKycProfileResource::getUrl('view', ['record' => $record]))
                    ->description(fn (TenantKycProfile $record): string => $record->tenant?->email ?? '—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('status')
                    ->label(__('admin.tenant_kyc.columns.status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('documents_count')
                    ->label(__('admin.tenant_kyc.columns.documents'))
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->label(__('admin.tenant_kyc.columns.submitted_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('expires_at')
                    ->label(__('admin.tenant_kyc.columns.expires_at'))
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.tenant_kyc.filters.status'))
                    ->multiple()
                    ->options(TenantKycProfileStatus::options())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['values'] ?? [])
                        ? $query->whereIn('status', $data['values'])
                        : $query),
            ])
            ->recordActions([
                ViewAction::make()
                    ->label(__('admin.actions.view')),
                Action::make('approveProfile')
                    ->label(__('admin.tenant_kyc.actions.approve_profile'))
                    ->icon('heroicon-m-check-circle')
                    ->color('success')
                    ->visible(fn (TenantKycProfile $record): bool => $record->status !== TenantKycProfileStatus::VERIFIED)
                    ->authorize(fn (TenantKycProfile $record): bool => TenantKycProfileResource::allows('approve', $record))
                    ->action(function (TenantKycProfile $record, ApproveTenantKycProfile $approveTenantKycProfile): void {
                        $approveTenantKycProfile->handle($record, TenantKycProfileResource::currentUserOrFail());

                        Notification::make()
                            ->success()
                            ->title(__('admin.tenant_kyc.messages.profile_approved'))
                            ->send();
                    }),
                Action::make('rejectProfile')
                    ->label(__('admin.tenant_kyc.actions.reject_profile'))
                    ->icon('heroicon-m-x-circle')
                    ->color('danger')
                    ->authorize(fn (TenantKycProfile $record): bool => TenantKycProfileResource::allows('reject', $record))
                    ->schema([
                        Textarea::make('rejection_reason')
                            ->label(__('admin.tenant_kyc.fields.rejection_reason'))
                            ->required()
                            ->rows(4),
                    ])
                    ->action(function (TenantKycProfile $record, array $data, RejectTenantKycProfile $rejectTenantKycProfile): void {
                        $rejectTenantKycProfile->handle($record, TenantKycProfileResource::currentUserOrFail(), $data);

                        Notification::make()
                            ->success()
                            ->title(__('admin.tenant_kyc.messages.profile_rejected'))
                            ->send();
                    }),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    private static function applyAttentionQuery(Builder $query): Builder
    {
        $attention = request()->query('attention');

        if (! is_string($attention) || $attention === '') {
            return $query;
        }

        return match ($attention) {
            'pending' => $query->forStatus(TenantKycProfileStatus::PENDING_REVIEW),
            'rejected' => $query->forStatus(TenantKycProfileStatus::REJECTED),
            'expired' => $query->forStatus(TenantKycProfileStatus::EXPIRED),
            'expires_soon' => $query->whereNotNull('expires_at')->whereBetween('expires_at', [now(), now()->addDays(30)]),
            default => $query,
        };
    }
}
