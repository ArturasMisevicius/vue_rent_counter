<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\InvoiceResource\Pages;
use App\Filament\Resources\InvoiceResource\RelationManagers;
use App\Models\Invoice;
use App\Models\Tenant;
use BackedEnum;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Schemas\Schema;
use UnitEnum;

/**
 * Filament resource for managing invoices.
 *
 * Provides CRUD operations for invoices with:
 * - Tenant-scoped data access
 * - Role-based navigation visibility
 * - Invoice finalization protection
 * - Bulk status updates
 * - Relationship management (items, tenants)
 *
 * @see \App\Models\Invoice
 * @see \App\Policies\InvoicePolicy
 */
class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static ?string $navigationLabel = null;

    protected static ?int $navigationSort = 3;

    public static function getNavigationIcon(): string|BackedEnum|null
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationLabel(): string
    {
        return __('invoices.admin.navigation');
    }

    public static function getNavigationGroup(): string|UnitEnum|null
    {
        return __('app.nav_groups.operations');
    }

    /**
     * Eager load relationships to prevent N+1 queries.
     *
     * @return array<string>
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['items', 'tenant.property']);
    }

    // Integrate InvoicePolicy for authorization (Requirement 9.5)
    public static function canViewAny(): bool
    {
        return auth()->check() && auth()->user()->can('viewAny', Invoice::class);
    }

    public static function canCreate(): bool
    {
        return auth()->check() && auth()->user()->can('create', Invoice::class);
    }

    public static function canEdit($record): bool
    {
        return auth()->check() && auth()->user()->can('update', $record);
    }

    public static function canDelete($record): bool
    {
        return auth()->check() && auth()->user()->can('delete', $record);
    }

    // Visible to all authenticated users (Requirements 9.1, 9.2, 9.3)
    // Tenants can view their own invoices
    public static function shouldRegisterNavigation(): bool
    {
        return auth()->check();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\Select::make('tenant_renter_id')
                    ->label(__('invoices.admin.labels.tenant'))
                    ->relationship('tenant', 'name', function (Builder $query) {
                        // Filter tenants by authenticated user's tenant_id (Requirement 11.1, 12.4)
                        $user = auth()->user();
                        if ($user && $user->tenant_id) {
                            $query->where('tenant_id', $user->tenant_id);
                        }
                    })
                    ->searchable()
                    ->preload()
                    ->required()
                    ->disabled(fn (?Invoice $record): bool => $record?->isFinalized() ?? false)
                    ->validationMessages([
                        'required' => 'Tenant is required',
                        'exists' => 'Selected tenant does not exist',
                    ]),
                
                Forms\Components\DatePicker::make('billing_period_start')
                    ->label(__('invoices.admin.labels.billing_period_start'))
                    ->required()
                    ->native(false)
                    ->disabled(fn (?Invoice $record): bool => $record?->isFinalized() ?? false)
                    ->validationMessages([
                        'required' => 'Billing period start date is required',
                        'date' => 'Billing period start must be a valid date',
                    ]),
                
                Forms\Components\DatePicker::make('billing_period_end')
                    ->label(__('invoices.admin.labels.billing_period_end'))
                    ->required()
                    ->native(false)
                    ->after('billing_period_start')
                    ->disabled(fn (?Invoice $record): bool => $record?->isFinalized() ?? false)
                    ->validationMessages([
                        'required' => 'Billing period end date is required',
                        'date' => 'Billing period end must be a valid date',
                        'after' => 'Billing period end must be after billing period start',
                    ]),
                
                Forms\Components\TextInput::make('total_amount')
                    ->label(__('invoices.admin.labels.total_amount'))
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->prefix(__('app.units.euro'))
                    ->step(0.01)
                    ->disabled(fn (?Invoice $record): bool => $record?->isFinalized() ?? false)
                    ->validationMessages([
                        'required' => 'Total amount is required',
                        'numeric' => 'Total amount must be a number',
                        'min' => 'Total amount must be at least 0',
                    ]),
                
                Forms\Components\Select::make('status')
                    ->label(__('invoices.admin.labels.status'))
                    ->options(InvoiceStatus::class)
                    ->required()
                    ->native(false)
                    ->default(InvoiceStatus::DRAFT->value)
                    ->disabled(fn (?Invoice $record): bool => 
                        // Allow status changes from finalized to paid
                        $record?->isFinalized() ?? false
                    )
                    ->validationMessages([
                        'required' => 'Status is required',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->searchable()
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('invoices.admin.labels.invoice_number'))
                    ->searchable()
                    ->sortable()
                    ->formatStateUsing(fn (int $state): string => "INV-{$state}"),
                
                Tables\Columns\TextColumn::make('tenant.property.address')
                    ->label(__('invoices.admin.labels.property'))
                    ->searchable()
                    ->sortable()
                    ->description(fn (Invoice $record): ?string => $record->tenant?->name),
                
                Tables\Columns\TextColumn::make('billing_period_start')
                    ->label(__('invoices.admin.labels.billing_period'))
                    ->date('Y-m-d')
                    ->sortable()
                    ->description(fn (Invoice $record): string => __('invoices.admin.format.billing_range', [
                        'from' => $record->billing_period_start->format('Y-m-d'),
                        'to' => $record->billing_period_end->format('Y-m-d'),
                    ])),
                
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('invoices.admin.labels.total_amount'))
                    ->money('EUR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('status')
                    ->label(__('invoices.admin.labels.status'))
                    ->badge()
                    ->color(fn (InvoiceStatus $state): string => match ($state) {
                        InvoiceStatus::DRAFT => 'gray',
                        InvoiceStatus::FINALIZED => 'warning',
                        InvoiceStatus::PAID => 'success',
                    })
                    ->formatStateUsing(fn (?InvoiceStatus $state): ?string => $state?->label())
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('invoices.admin.labels.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label(__('invoices.admin.labels.status'))
                    ->options(InvoiceStatus::labels())
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('updateStatus')
                        ->label(__('invoices.admin.labels.update_status'))
                        ->icon('heroicon-o-pencil-square')
                        ->form([
                            Forms\Components\Select::make('status')
                                ->label(__('invoices.admin.labels.new_status'))
                                ->options(InvoiceStatus::labels())
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (array $data, $records) {
                            foreach ($records as $record) {
                                // Only allow status updates if not finalized, or if changing from finalized to paid
                                if (!$record->isFinalized() || $data['status'] === InvoiceStatus::PAID->value) {
                                    $record->update(['status' => $data['status']]);
                                }
                            }
                        })
                        ->deselectRecordsAfterCompletion()
                        ->successNotificationTitle(__('invoices.admin.bulk.status_updated')),
                    
                    Tables\Actions\DeleteBulkAction::make()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                // Only allow deletion of draft invoices
                                if (!$record->isFinalized()) {
                                    $record->delete();
                                }
                            }
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'create' => Pages\CreateInvoice::route('/create'),
            'view' => Pages\ViewInvoice::route('/{record}'),
            'edit' => Pages\EditInvoice::route('/{record}/edit'),
        ];
    }
}
