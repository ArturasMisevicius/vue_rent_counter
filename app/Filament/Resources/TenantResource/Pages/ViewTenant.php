<?php

declare(strict_types=1);

namespace App\Filament\Resources\TenantResource\Pages;

use App\Filament\Resources\TenantResource;
use App\Models\Tenant;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\IconEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\TextEntry;
use Filament\Schemas\Schema;

final class ViewTenant extends ViewRecord
{
    protected static string $resource = TenantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
            
            Actions\Action::make('generate_invoice')
                ->label('Generate Invoice')
                ->icon('heroicon-o-document-text')
                ->color('success')
                ->form([
                    \Filament\Forms\Components\DatePicker::make('period_start')
                        ->label('Billing Period Start')
                        ->required()
                        ->default(now()->startOfMonth())
                        ->native(false),
                        
                    \Filament\Forms\Components\DatePicker::make('period_end')
                        ->label('Billing Period End')
                        ->required()
                        ->default(now()->endOfMonth())
                        ->native(false)
                        ->after('period_start'),
                ])
                ->action(function (array $data): void {
                    try {
                        $billingService = app(\App\Services\BillingService::class);
                        
                        $invoice = $billingService->generateInvoice(
                            $this->getRecord(),
                            \Carbon\Carbon::parse($data['period_start']),
                            \Carbon\Carbon::parse($data['period_end'])
                        );
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Invoice Generated Successfully')
                            ->body("Invoice #{$invoice->id} has been created")
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('view')
                                    ->button()
                                    ->url(route('filament.admin.resources.invoices.view', $invoice)),
                            ])
                            ->send();
                            
                    } catch (\Exception $e) {
                        \Filament\Notifications\Notification::make()
                            ->title('Invoice Generation Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                })
                ->requiresConfirmation()
                ->modalHeading('Generate Invoice')
                ->modalDescription('Generate a new invoice for this tenant for the specified billing period.')
                ->visible(fn (Tenant $record) => $record->is_active),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Tenant Information')
                    ->schema([
                        TextEntry::make('tenant_id')
                            ->label('Tenant ID')
                            ->copyable(),
                            
                        TextEntry::make('full_name')
                            ->label('Full Name')
                            ->getStateUsing(fn ($record) => "{$record->first_name} {$record->last_name}"),
                            
                        TextEntry::make('email')
                            ->copyable()
                            ->placeholder('No email provided'),
                            
                        TextEntry::make('phone')
                            ->copyable()
                            ->placeholder('No phone provided'),
                    ])
                    ->columns(2),
                    
                Section::make('Property Information')
                    ->schema([
                        TextEntry::make('property.building.name')
                            ->label('Building'),
                            
                        TextEntry::make('property.unit_number')
                            ->label('Unit Number'),
                            
                        TextEntry::make('property.area_sqm')
                            ->label('Area')
                            ->suffix(' m²'),
                            
                        TextEntry::make('property.property_type')
                            ->label('Property Type')
                            ->badge(),
                    ])
                    ->columns(2),
                    
                Section::make('Lease Information')
                    ->schema([
                        TextEntry::make('lease_start_date')
                            ->date(),
                            
                        TextEntry::make('lease_end_date')
                            ->date()
                            ->placeholder('Ongoing lease'),
                            
                        TextEntry::make('monthly_rent')
                            ->money('EUR')
                            ->placeholder('Not specified'),
                            
                        TextEntry::make('deposit_amount')
                            ->money('EUR')
                            ->placeholder('No deposit'),
                            
                        IconEntry::make('is_active')
                            ->label('Active Status')
                            ->boolean()
                            ->trueIcon('heroicon-o-check-circle')
                            ->falseIcon('heroicon-o-x-circle')
                            ->trueColor('success')
                            ->falseColor('danger'),
                    ])
                    ->columns(3),
                    
                Section::make('Additional Information')
                    ->schema([
                        TextEntry::make('notes')
                            ->placeholder('No notes')
                            ->columnSpanFull(),
                            
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                            
                        TextEntry::make('updated_at')
                            ->label('Last Updated')
                            ->dateTime(),
                    ])
                    ->columns(2),
            ]);
    }
}