<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use App\Enums\InvoiceStatus;
use App\Enums\UserRole;
use App\Filament\Tenant\Resources\InvoiceResource\Pages;
use App\Models\Invoice;
use Filament\Schemas\Components\DatePicker;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Select;
use Filament\Schemas\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Invoice Resource for Tenant Panel
 * 
 * Allows tenants to view their invoices and download PDFs.
 * Read-only access - tenants cannot modify invoices.
 */
final class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    public static function getNavigationIcon(): ?string
    {
        return 'heroicon-o-document-text';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationGroup(): ?string
    {
        return __('app.nav_groups.billing');
    }

    public static function getNavigationLabel(): string
    {
        return __('app.navigation.invoices');
    }

    public static function getModelLabel(): string
    {
        return __('app.labels.invoice');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.labels.invoices');
    }

    public static function canAccess(): bool
    {
        $user = Auth::user();
        
        return $user && $user->role === UserRole::TENANT && $user->property_id;
    }

    public static function canCreate(): bool
    {
        return false; // Tenants cannot create invoices
    }

    public static function canEdit($record): bool
    {
        return false; // Tenants cannot edit invoices
    }

    public static function canDelete($record): bool
    {
        return false; // Tenants cannot delete invoices
    }

    public static function getEloquentQuery(): Builder
    {
        $user = Auth::user();
        
        return parent::getEloquentQuery()
            ->where('property_id', $user?->property_id)
            ->with(['property', 'items'])
            ->latest('issue_date');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make(__('app.sections.invoice_details'))
                    ->schema([
                        TextInput::make('number')
                            ->label(__('app.labels.invoice_number'))
                            ->disabled(),
                        
                        Select::make('status')
                            ->label(__('app.labels.status'))
                            ->options(InvoiceStatus::class)
                            ->disabled(),
                        
                        DatePicker::make('issue_date')
                            ->label(__('app.labels.issue_date'))
                            ->disabled(),
                        
                        DatePicker::make('due_date')
                            ->label(__('app.labels.due_date'))
                            ->disabled(),
                        
                        TextInput::make('total_amount')
                            ->label(__('app.labels.total_amount'))
                            ->numeric()
                            ->prefix('€')
                            ->disabled(),
                    ]),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('app.sections.invoice_details'))
                    ->schema([
                        TextEntry::make('number')
                            ->label(__('app.labels.invoice_number')),
                        
                        TextEntry::make('status')
                            ->label(__('app.labels.status'))
                            ->badge()
                            ->color(fn (InvoiceStatus $state): string => match ($state) {
                                InvoiceStatus::DRAFT => 'gray',
                                InvoiceStatus::FINALIZED => 'warning',
                                InvoiceStatus::PAID => 'success',
                                InvoiceStatus::OVERDUE => 'danger',
                                InvoiceStatus::CANCELLED => 'gray',
                            }),
                        
                        TextEntry::make('issue_date')
                            ->label(__('app.labels.issue_date'))
                            ->date(),
                        
                        TextEntry::make('due_date')
                            ->label(__('app.labels.due_date'))
                            ->date(),
                        
                        TextEntry::make('total_amount')
                            ->label(__('app.labels.total_amount'))
                            ->money('EUR'),
                    ]),
                
                Section::make(__('app.sections.billing_period'))
                    ->schema([
                        TextEntry::make('billing_period_start')
                            ->label(__('app.labels.billing_period_start'))
                            ->date(),
                        
                        TextEntry::make('billing_period_end')
                            ->label(__('app.labels.billing_period_end'))
                            ->date(),
                        
                        TextEntry::make('property.name')
                            ->label(__('app.labels.property')),
                        
                        TextEntry::make('property.address')
                            ->label(__('app.labels.address')),
                    ]),
                
                Section::make(__('app.sections.invoice_items'))
                    ->schema([
                        TextEntry::make('items')
                            ->label(__('app.labels.services'))
                            ->listWithLineBreaks()
                            ->getStateUsing(function ($record) {
                                return $record->items->map(function ($item) {
                                    return sprintf(
                                        '%s: %s %s × €%s = €%s',
                                        $item->description,
                                        number_format($item->quantity, 2),
                                        $item->unit,
                                        number_format($item->unit_price, 2),
                                        number_format($item->total_price, 2)
                                    );
                                })->toArray();
                            }),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('number')
                    ->label(__('app.labels.invoice_number'))
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('status')
                    ->label(__('app.labels.status'))
                    ->badge()
                    ->color(fn (InvoiceStatus $state): string => match ($state) {
                        InvoiceStatus::DRAFT => 'gray',
                        InvoiceStatus::FINALIZED => 'warning',
                        InvoiceStatus::PAID => 'success',
                        InvoiceStatus::OVERDUE => 'danger',
                        InvoiceStatus::CANCELLED => 'gray',
                    }),
                
                TextColumn::make('issue_date')
                    ->label(__('app.labels.issue_date'))
                    ->date()
                    ->sortable(),
                
                TextColumn::make('due_date')
                    ->label(__('app.labels.due_date'))
                    ->date()
                    ->sortable(),
                
                TextColumn::make('total_amount')
                    ->label(__('app.labels.total_amount'))
                    ->money('EUR')
                    ->sortable(),
                
                TextColumn::make('billing_period_start')
                    ->label(__('app.labels.billing_period'))
                    ->date()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('app.labels.status'))
                    ->options(InvoiceStatus::class),
            ])
            ->actions([
                Action::make('download')
                    ->label(__('app.actions.download_pdf'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Invoice $record): string => route('invoices.pdf', $record))
                    ->openUrlInNewTab()
                    ->visible(fn (Invoice $record): bool => $record->status !== InvoiceStatus::DRAFT),
            ])
            ->defaultSort('issue_date', 'desc')
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoices::route('/'),
            'view' => Pages\ViewInvoice::route('/{record}'),
        ];
    }
}