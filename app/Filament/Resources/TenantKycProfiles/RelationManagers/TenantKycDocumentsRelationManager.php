<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenantKycProfiles\RelationManagers;

use App\Enums\TenantKycDocumentStatus;
use App\Filament\Actions\TenantKyc\ApproveKycDocument;
use App\Filament\Actions\TenantKyc\RejectKycDocument;
use App\Filament\Resources\TenantKycProfiles\TenantKycProfileResource;
use App\Models\TenantKycDocument;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class TenantKycDocumentsRelationManager extends RelationManager
{
    protected static string $relationship = 'documents';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return TenantKycProfileResource::canView($ownerRecord);
    }

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.tenant_kyc.documents.title');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withReviewRelations()->latestActivityFirst())
            ->columns(self::columns())
            ->filters([
                SelectFilter::make('status')
                    ->label(__('admin.tenant_kyc.filters.status'))
                    ->multiple()
                    ->options(TenantKycDocumentStatus::options()),
            ])
            ->recordActions(self::recordActions())
            ->defaultSort('updated_at', 'desc');
    }

    /**
     * @return array<int, TextColumn>
     */
    public static function columns(): array
    {
        return [
            TextColumn::make('document_type')
                ->label(__('admin.tenant_kyc.documents.columns.type'))
                ->badge()
                ->sortable(),
            TextColumn::make('status')
                ->label(__('admin.tenant_kyc.documents.columns.status'))
                ->badge()
                ->sortable(),
            TextColumn::make('fileDocument.original_filename')
                ->label(__('admin.tenant_kyc.documents.columns.file'))
                ->default('—'),
            TextColumn::make('expires_at')
                ->label(__('admin.tenant_kyc.documents.columns.expires_at'))
                ->date()
                ->sortable(),
            TextColumn::make('rejection_reason')
                ->label(__('admin.tenant_kyc.documents.columns.rejection_reason'))
                ->limit(40)
                ->placeholder('—'),
            TextColumn::make('internal_note')
                ->label(__('admin.tenant_kyc.documents.columns.internal_note'))
                ->limit(40)
                ->placeholder('—'),
            TextColumn::make('updated_at')
                ->label(__('admin.tenant_kyc.documents.columns.updated_at'))
                ->dateTime()
                ->sortable(),
        ];
    }

    /**
     * @return array<int, Action>
     */
    public static function recordActions(): array
    {
        return [
            Action::make('approve')
                ->label(__('admin.tenant_kyc.actions.approve_document'))
                ->icon('heroicon-m-check-circle')
                ->color('success')
                ->visible(fn (TenantKycDocument $record): bool => $record->status !== TenantKycDocumentStatus::APPROVED)
                ->authorize(fn (TenantKycDocument $record): bool => TenantKycProfileResource::allows('approve', $record))
                ->schema([
                    Textarea::make('internal_note')
                        ->label(__('admin.tenant_kyc.fields.internal_note'))
                        ->rows(3),
                ])
                ->action(function (TenantKycDocument $record, array $data, ApproveKycDocument $approveKycDocument): void {
                    $approveKycDocument->handle($record, TenantKycProfileResource::currentUserOrFail(), $data['internal_note'] ?? null);

                    Notification::make()
                        ->success()
                        ->title(__('admin.tenant_kyc.messages.document_approved'))
                        ->send();
                }),
            Action::make('requestReplacement')
                ->label(__('admin.tenant_kyc.actions.request_replacement'))
                ->icon('heroicon-m-arrow-path')
                ->color('danger')
                ->authorize(fn (TenantKycDocument $record): bool => TenantKycProfileResource::allows('reject', $record))
                ->schema([
                    Textarea::make('rejection_reason')
                        ->label(__('admin.tenant_kyc.fields.rejection_reason'))
                        ->required()
                        ->rows(4),
                    Textarea::make('internal_note')
                        ->label(__('admin.tenant_kyc.fields.internal_note'))
                        ->rows(3),
                ])
                ->action(function (TenantKycDocument $record, array $data, RejectKycDocument $rejectKycDocument): void {
                    $rejectKycDocument->handle($record, TenantKycProfileResource::currentUserOrFail(), $data);

                    Notification::make()
                        ->success()
                        ->title(__('admin.tenant_kyc.messages.replacement_requested'))
                        ->send();
                }),
            Action::make('download')
                ->label(__('admin.tenant_kyc.actions.download_document'))
                ->icon('heroicon-m-arrow-down-tray')
                ->authorize(fn (TenantKycDocument $record): bool => TenantKycProfileResource::allows('download', $record))
                ->url(fn (TenantKycDocument $record): string => route('tenant.kyc-documents.download', $record)),
        ];
    }
}
