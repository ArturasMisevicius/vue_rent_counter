<?php

declare(strict_types=1);

namespace App\Filament\Resources\UserKycProfiles\Tables;

use App\Enums\KycVerificationStatus;
use App\Filament\Resources\UserKycProfiles\UserKycProfileResource;
use App\Models\Organization;
use App\Models\User;
use App\Models\UserKycProfile;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class UserKycProfilesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => self::applyAttentionQuery($query))
            ->columns([
                TextColumn::make('organization.name')
                    ->label(__('superadmin.user_kyc_profiles.columns.organization'))
                    ->visible(fn (): bool => self::currentUser()?->isSuperadmin() ?? false),
                TextColumn::make('full_legal_name')
                    ->label(__('superadmin.user_kyc_profiles.columns.full_legal_name'))
                    ->url(fn (UserKycProfile $record): string => UserKycProfileResource::getUrl('view', ['record' => $record]))
                    ->description(fn (UserKycProfile $record): string => $record->user?->name ?? '—')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('verification_status')
                    ->label(__('superadmin.user_kyc_profiles.columns.verification_status'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('submitted_at')
                    ->label(__('superadmin.user_kyc_profiles.columns.submitted_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('organization')
                    ->label(__('superadmin.user_kyc_profiles.filters.organization'))
                    ->visible(fn (): bool => self::currentUser()?->isSuperadmin() ?? false)
                    ->options(fn (): array => Organization::query()->orderBy('name')->pluck('name', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('organization_id', (int) $data['value'])
                        : $query),
                SelectFilter::make('verification_status')
                    ->label(__('superadmin.user_kyc_profiles.filters.verification_status'))
                    ->options(KycVerificationStatus::options())
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'] ?? null)
                        ? $query->where('verification_status', $data['value'])
                        : $query),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    private static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    private static function applyAttentionQuery(Builder $query): Builder
    {
        $verificationStatus = request()->query('verification_status');

        if (is_string($verificationStatus) && $verificationStatus !== '') {
            $query->where('verification_status', $verificationStatus);
        }

        $attention = request()->query('attention');

        if (! is_string($attention) || $attention === '') {
            return $query;
        }

        return match ($attention) {
            'documents_expiring' => $query->whereHas('attachments', fn (Builder $attachmentQuery): Builder => $attachmentQuery
                ->whereNotNull('metadata')),
            'recent_uploads' => $query->whereHas('attachments', fn (Builder $attachmentQuery): Builder => $attachmentQuery
                ->where('created_at', '>=', now()->subDays(7))),
            'internal_documents' => $query->whereHas('attachments', fn (Builder $attachmentQuery): Builder => $attachmentQuery
                ->where('tenant_visible', false)
                ->whereNotNull('document_type')),
            'orphan_documents' => $query->whereKey(-1),
            default => $query,
        };
    }
}
