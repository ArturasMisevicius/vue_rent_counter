<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Filament\Support\Admin\OrganizationContext;
use App\Filament\Support\Formatting\EuMoneyFormatter;
use App\Models\Organization;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class CreateInvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make(__('admin.invoices.sections.details'))
                    ->schema([
                        Hidden::make('line_items_generated')
                            ->default(false)
                            ->dehydrated(false),
                        Select::make('organization_id')
                            ->label(__('superadmin.organizations.singular'))
                            ->options(fn (): array => Organization::query()
                                ->forSuperadminControlPlane()
                                ->pluck('name', 'id')
                                ->all())
                            ->searchable()
                            ->preload()
                            ->live()
                            ->required(fn (): bool => self::requiresOrganizationSelection())
                            ->visible(fn (): bool => self::requiresOrganizationSelection())
                            ->afterStateUpdated(fn (Set $set): mixed => self::resetGeneratedState($set)),
                        Select::make('tenant_user_id')
                            ->label(__('admin.invoices.fields.tenant'))
                            ->options(fn (Get $get): array => self::tenantOptions($get('organization_id')))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => self::resetGeneratedState($set)),
                        DatePicker::make('billing_period_start')
                            ->label(__('admin.invoices.fields.billing_period_from'))
                            ->required()
                            ->default(now()->startOfMonth()->toDateString())
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => self::resetGeneratedState($set)),
                        DatePicker::make('billing_period_end')
                            ->label(__('admin.invoices.fields.billing_period_to'))
                            ->required()
                            ->default(now()->endOfMonth()->toDateString())
                            ->live()
                            ->afterStateUpdated(fn (Set $set): mixed => self::resetGeneratedState($set)),
                        Actions::make([
                            Action::make('generateLineItems')
                                ->label(__('admin.invoices.actions.generate_line_items'))
                                ->action('generateLineItems'),
                        ])
                            ->columnSpanFull()
                            ->visible(fn (Get $get): bool => self::canGenerateLineItems($get)),
                    ])
                    ->columns(2),
                Section::make(__('admin.invoices.sections.line_items'))
                    ->schema([
                        Repeater::make('items')
                            ->label(__('admin.invoices.fields.line_items'))
                            ->schema([
                                TextInput::make('description')
                                    ->label(__('admin.invoices.fields.description'))
                                    ->required()
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('period')
                                    ->label(__('admin.invoices.fields.period'))
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('unit')
                                    ->label(__('admin.invoices.fields.unit'))
                                    ->maxLength(50),
                                TextInput::make('quantity')
                                    ->label(__('admin.invoices.fields.quantity'))
                                    ->numeric()
                                    ->required()
                                    ->minValue(0)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set): mixed => self::syncLineItemTotal($get, $set)),
                                TextInput::make('rate')
                                    ->label(__('admin.invoices.fields.rate'))
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Get $get, Set $set): mixed => self::syncLineItemTotal($get, $set)),
                                TextInput::make('total')
                                    ->label(__('admin.invoices.fields.total_amount'))
                                    ->numeric()
                                    ->required()
                                    ->live(onBlur: true),
                            ])
                            ->columns(8)
                            ->defaultItems(0)
                            ->addActionLabel(__('admin.invoices.actions.add_manual_line'))
                            ->deleteAction(fn (Action $action): Action => $action->label(__('admin.invoices.actions.remove_line')))
                            ->itemLabel(fn (array $state): ?string => filled($state['description'] ?? null) ? (string) $state['description'] : null)
                            ->reorderable(false)
                            ->columnSpanFull(),
                        Repeater::make('adjustments')
                            ->label(__('admin.invoices.fields.adjustments'))
                            ->schema([
                                TextInput::make('label')
                                    ->label(__('admin.invoices.fields.description'))
                                    ->maxLength(255)
                                    ->columnSpan(2),
                                TextInput::make('amount')
                                    ->label(__('admin.invoices.fields.adjustment'))
                                    ->numeric()
                                    ->live(onBlur: true),
                            ])
                            ->columns(3)
                            ->defaultItems(0)
                            ->addActionLabel(__('admin.invoices.actions.add_adjustment'))
                            ->deleteAction(fn (Action $action): Action => $action->label(__('admin.invoices.actions.remove_line')))
                            ->itemLabel(fn (array $state): ?string => filled($state['label'] ?? null) ? (string) $state['label'] : null)
                            ->reorderable(false)
                            ->columnSpanFull(),
                        Text::make(fn (Get $get): string => __('admin.invoices.fields.subtotal').': '.self::formatMoney(self::subtotal($get)))
                            ->columnSpanFull(),
                        Text::make(fn (Get $get): string => __('admin.invoices.fields.adjustments_total').': '.self::formatMoney(self::adjustmentsTotal($get)))
                            ->columnSpanFull(),
                        Text::make(fn (Get $get): string => __('admin.invoices.fields.final_total').': '.self::formatMoney(self::finalTotal($get)))
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label(__('admin.invoices.fields.invoice_notes'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Get $get): bool => (bool) $get('line_items_generated'))
                    ->columns(1),
            ]);
    }

    /**
     * @return array<int, string>
     */
    private static function tenantOptions(mixed $organizationId): array
    {
        $resolvedOrganizationId = self::resolvedOrganizationId($organizationId);

        if ($resolvedOrganizationId === null) {
            return [];
        }

        return User::query()
            ->select(['id', 'organization_id', 'name', 'email', 'status'])
            ->forOrganization($resolvedOrganizationId)
            ->tenants()
            ->active()
            ->orderedByName()
            ->get()
            ->mapWithKeys(fn (User $tenant): array => [
                $tenant->id => filled($tenant->email)
                    ? "{$tenant->name} · {$tenant->email}"
                    : $tenant->name,
            ])
            ->all();
    }

    private static function requiresOrganizationSelection(): bool
    {
        $user = Auth::user();

        if (! $user instanceof User) {
            return false;
        }

        return $user->isSuperadmin()
            && app(OrganizationContext::class)->currentOrganizationId() === null;
    }

    private static function canGenerateLineItems(Get $get): bool
    {
        return self::resolvedOrganizationId($get('organization_id')) !== null
            && filled($get('tenant_user_id'))
            && filled($get('billing_period_start'))
            && filled($get('billing_period_end'));
    }

    private static function resetGeneratedState(Set $set): void
    {
        $set('line_items_generated', false);
        $set('items', []);
        $set('adjustments', []);
    }

    private static function syncLineItemTotal(Get $get, Set $set): void
    {
        $quantity = is_numeric($get('quantity')) ? (float) $get('quantity') : 0.0;
        $rate = is_numeric($get('rate')) ? (float) $get('rate') : 0.0;

        $formatter = new \NumberFormatter(app()->getLocale(), \NumberFormatter::DECIMAL);
        $formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 2);
        $formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 2);

        $set('total', (string) $formatter->format($quantity * $rate));
    }

    private static function subtotal(Get $get): float
    {
        return collect($get('items') ?? [])
            ->sum(fn (mixed $item): float => is_array($item) && is_numeric($item['total'] ?? null) ? (float) $item['total'] : 0.0);
    }

    private static function adjustmentsTotal(Get $get): float
    {
        return collect($get('adjustments') ?? [])
            ->sum(fn (mixed $adjustment): float => is_array($adjustment) && is_numeric($adjustment['amount'] ?? null) ? (float) $adjustment['amount'] : 0.0);
    }

    private static function finalTotal(Get $get): float
    {
        return self::subtotal($get) + self::adjustmentsTotal($get);
    }

    private static function formatMoney(float $amount): string
    {
        return EuMoneyFormatter::format($amount);
    }

    private static function resolvedOrganizationId(mixed $organizationId): ?int
    {
        if (is_numeric($organizationId)) {
            return (int) $organizationId;
        }

        $currentOrganizationId = app(OrganizationContext::class)->currentOrganizationId();

        return is_numeric($currentOrganizationId) ? (int) $currentOrganizationId : null;
    }
}
