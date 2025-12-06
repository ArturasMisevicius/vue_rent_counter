<?php

declare(strict_types=1);

namespace App\Filament\Resources\TariffResource\Concerns;

use App\Enums\TariffType;
use App\Enums\WeekendLogic;
use App\Models\Provider;
use Filament\Forms;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Trait for building tariff form fields with manual entry mode support.
 * 
 * Extracts form field construction logic from TariffResource to improve
 * maintainability and reduce method complexity. Implements conditional
 * field visibility and validation based on manual entry mode.
 *
 * Features:
 * - Manual entry mode toggle for provider-independent tariffs
 * - Conditional provider and remote_id fields based on mode
 * - Dynamic validation rules that adapt to manual mode state
 * - External system integration via remote_id field
 * - Comprehensive validation mirroring FormRequest rules
 *
 * Manual Entry Mode:
 * When enabled, allows users to create tariffs without linking to a provider
 * integration. This is useful for:
 * - Manually entered tariff rates from paper documents
 * - Historical tariff data without provider integration
 * - Custom tariff configurations not available via provider API
 * - Testing and development scenarios
 *
 * Provider Integration Mode:
 * When manual mode is disabled (default), requires provider selection and
 * optionally accepts a remote_id for external system synchronization.
 *
 * @see \App\Filament\Resources\TariffResource
 * @see \App\Models\Tariff::isManual()
 * @see \Tests\Feature\Filament\TariffManualModeTest
 * @see database/migrations/2025_12_05_163137_add_remote_id_to_tariffs_table.php
 */
trait BuildsTariffFormFields
{
    /**
     * Build the basic information section fields.
     *
     * Constructs the form fields for tariff basic information including:
     * - Manual mode toggle (UI-only, not persisted)
     * - Provider selection (conditional on manual mode)
     * - Remote ID for external system integration (conditional on manual mode)
     * - Tariff name with XSS protection
     *
     * Field Behavior:
     * - manual_mode: Toggle that controls visibility of provider/remote_id fields
     *   - Not saved to database (dehydrated: false)
     *   - Live reactive to update dependent fields immediately
     *   - Default: false (provider mode)
     *
     * - provider_id: Provider selection dropdown
     *   - Visible: Only when manual_mode is false
     *   - Required: Only when manual_mode is false
     *   - Uses cached provider options for performance
     *   - Searchable for large provider lists
     *
     * - remote_id: External system identifier
     *   - Visible: Only when manual_mode is false
     *   - Optional: Can be null even with provider selected
     *   - Max length: 255 characters
     *   - Use case: Synchronization with external billing systems
     *
     * - name: Tariff display name
     *   - Always visible and required
     *   - XSS protection via regex and sanitization
     *   - Max length: 255 characters
     *
     * Validation Strategy:
     * Uses conditional validation rules via closures to adapt validation
     * based on manual_mode state. This ensures:
     * - Provider is required only in provider mode
     * - Remote_id validation adapts to mode
     * - Consistent validation between UI and API
     *
     * @return array<Forms\Components\Component> Array of form field components
     *
     * @see \App\Models\Provider::getCachedOptions()
     * @see \App\Services\InputSanitizer::sanitizeText()
     */
    protected static function buildBasicInformationFields(): array
    {
        return [
            Forms\Components\Toggle::make('manual_mode')
                ->label(__('tariffs.forms.manual_mode'))
                ->helperText(__('tariffs.forms.manual_mode_helper'))
                ->default(false)
                ->live()
                ->columnSpanFull()
                ->dehydrated(false), // Don't save this field to database
            
            Forms\Components\Select::make('provider_id')
                ->label(__('tariffs.forms.provider'))
                ->options(fn () => Provider::getCachedOptions())
                ->searchable()
                ->visible(fn (Get $get): bool => !$get('manual_mode'))
                ->required(fn (Get $get): bool => !$get('manual_mode'))
                ->rules([
                    'nullable',
                    'exists:providers,id',
                ])
                ->validationMessages([
                    'required' => __('tariffs.validation.provider_id.required'),
                    'exists' => __('tariffs.validation.provider_id.exists'),
                ]),
            
            Forms\Components\TextInput::make('remote_id')
                ->label(__('tariffs.forms.remote_id'))
                ->maxLength(255)
                ->visible(fn (Get $get): bool => !$get('manual_mode'))
                ->helperText(__('tariffs.forms.remote_id_helper'))
                ->rules([
                    'nullable',
                    'string',
                    'max:255',
                    'regex:/^[a-zA-Z0-9\-\_\.]+$/', // Security: Alphanumeric, hyphens, underscores, dots only
                    fn (Get $get): string => $get('remote_id') && !$get('provider_id') ? 'required_with:provider_id' : '',
                ])
                ->validationMessages([
                    'max' => __('tariffs.validation.remote_id.max'),
                    'regex' => __('tariffs.validation.remote_id.format'),
                    'required_with' => __('tariffs.validation.provider_id.required_with'),
                ])
                ->dehydrateStateUsing(fn (?string $state): ?string => 
                    $state ? app(\App\Services\InputSanitizer::class)->sanitizeIdentifier($state) : null
                ),
            
            Forms\Components\TextInput::make('name')
                ->label(__('tariffs.forms.name'))
                ->required()
                ->maxLength(255)
                ->rules(['required', 'string', 'max:255', 'regex:/^[a-zA-Z0-9\s\-\_\.\,\(\)]+$/u'])
                ->validationMessages([
                    'required' => __('tariffs.validation.name.required'),
                    'string' => __('tariffs.validation.name.string'),
                    'max' => __('tariffs.validation.name.max'),
                    'regex' => __('tariffs.validation.name.regex'),
                ])
                ->dehydrateStateUsing(fn (string $state): string => app(\App\Services\InputSanitizer::class)->sanitizeText($state)),
        ];
    }

    /**
     * Build the effective period section fields.
     *
     * @return array<Forms\Components\Component>
     */
    protected static function buildEffectivePeriodFields(): array
    {
        return [
            Forms\Components\DatePicker::make('active_from')
                ->label(__('tariffs.forms.active_from'))
                ->required()
                ->native(false)
                ->rules(['required', 'date'])
                ->validationMessages([
                    'required' => __('tariffs.validation.active_from.required'),
                    'date' => __('tariffs.validation.active_from.date'),
                ]),
            
            Forms\Components\DatePicker::make('active_until')
                ->label(__('tariffs.forms.active_until'))
                ->nullable()
                ->native(false)
                ->after('active_from')
                ->rules(['nullable', 'date', 'after:active_from'])
                ->validationMessages([
                    'after' => __('tariffs.validation.active_until.after'),
                    'date' => __('tariffs.validation.active_until.date'),
                ]),
        ];
    }

    /**
     * Build the configuration section fields.
     *
     * @return array<Forms\Components\Component>
     */
    protected static function buildConfigurationFields(): array
    {
        return [
            static::buildTariffTypeField(),
            static::buildCurrencyField(),
            static::buildFlatRateField(),
            static::buildTimeOfUseZonesField(),
            static::buildWeekendLogicField(),
            static::buildFixedFeeField(),
        ];
    }

    /**
     * Build the tariff type selection field.
     *
     * @return Forms\Components\Select
     */
    protected static function buildTariffTypeField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('configuration.type')
            ->label(__('tariffs.forms.type'))
            ->options(TariffType::labels())
            ->required()
            ->native(false)
            ->live()
            ->rules(['required', 'string', 'in:flat,time_of_use'])
            ->validationMessages([
                'required' => __('tariffs.validation.configuration.type.required'),
                'string' => __('tariffs.validation.configuration.type.string'),
                'in' => __('tariffs.validation.configuration.type.in'),
            ]);
    }

    /**
     * Build the currency selection field.
     *
     * @return Forms\Components\Select
     */
    protected static function buildCurrencyField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('configuration.currency')
            ->label(__('tariffs.forms.currency'))
            ->options([
                'EUR' => 'EUR (€)',
            ])
            ->default('EUR')
            ->required()
            ->native(false)
            ->rules(['required', 'string', 'in:EUR'])
            ->validationMessages([
                'required' => __('tariffs.validation.configuration.currency.required'),
                'string' => __('tariffs.validation.configuration.currency.string'),
                'in' => __('tariffs.validation.configuration.currency.in'),
            ]);
    }

    /**
     * Build the flat rate field (conditional on tariff type).
     *
     * @return Forms\Components\TextInput
     */
    protected static function buildFlatRateField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('configuration.rate')
            ->label(__('tariffs.forms.flat_rate'))
            ->numeric()
            ->minValue(0)
            ->step(0.0001)
            ->suffix(__('app.units.euro'))
            ->visible(fn (Get $get): bool => $get('configuration.type') === 'flat')
            ->required(fn (Get $get): bool => $get('configuration.type') === 'flat')
            ->rules([
                fn (Get $get): string => $get('configuration.type') === 'flat' ? 'required' : 'nullable',
                'numeric',
                'min:0',
                'max:999999.9999',
            ])
            ->validationMessages([
                'required' => __('tariffs.validation.configuration.rate.required_if'),
                'numeric' => __('tariffs.validation.configuration.rate.numeric'),
                'min' => __('tariffs.validation.configuration.rate.min'),
                'max' => __('tariffs.validation.configuration.rate.max'),
            ]);
    }

    /**
     * Build the time-of-use zones repeater field.
     *
     * @return Forms\Components\Repeater
     */
    protected static function buildTimeOfUseZonesField(): Forms\Components\Repeater
    {
        return Forms\Components\Repeater::make('configuration.zones')
            ->label(__('tariffs.forms.zones'))
            ->schema(static::buildZoneFields())
            ->columns(4)
            ->visible(fn (Get $get): bool => $get('configuration.type') === 'time_of_use')
            ->required(fn (Get $get): bool => $get('configuration.type') === 'time_of_use')
            ->minItems(1)
            ->defaultItems(0)
            ->addActionLabel(__('tariffs.forms.add_zone'))
            ->rules([
                fn (Get $get): string => $get('configuration.type') === 'time_of_use' ? 'required' : 'nullable',
                'array',
                'min:1',
            ])
            ->validationMessages([
                'required' => __('tariffs.validation.configuration.zones.required_if'),
                'array' => __('tariffs.validation.configuration.zones.array'),
                'min' => __('tariffs.validation.configuration.zones.min'),
            ]);
    }

    /**
     * Build the zone fields for time-of-use tariffs.
     *
     * @return array<Forms\Components\Component>
     */
    protected static function buildZoneFields(): array
    {
        return [
            Forms\Components\TextInput::make('id')
                ->label(__('tariffs.forms.zone_id'))
                ->required()
                ->maxLength(50)
                ->placeholder(__('tariffs.forms.zone_placeholder'))
                ->rules(['required', 'string', 'max:50', 'regex:/^[a-zA-Z0-9\_\-]+$/'])
                ->validationMessages([
                    'required' => __('tariffs.validation.configuration.zones.id.required_with'),
                    'string' => __('tariffs.validation.configuration.zones.id.string'),
                    'max' => __('tariffs.validation.configuration.zones.id.max'),
                    'regex' => __('tariffs.validation.configuration.zones.id.regex'),
                ])
                ->dehydrateStateUsing(fn (string $state): string => app(\App\Services\InputSanitizer::class)->sanitizeIdentifier($state)),
            
            Forms\Components\TextInput::make('start')
                ->label(__('tariffs.forms.start_time'))
                ->required()
                ->placeholder(__('tariffs.forms.start_placeholder'))
                ->regex('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/')
                ->rules(['required', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'])
                ->validationMessages([
                    'required' => __('tariffs.validation.configuration.zones.start.required_with'),
                    'string' => __('tariffs.validation.configuration.zones.start.string'),
                    'regex' => __('tariffs.validation.configuration.zones.start.regex'),
                ]),
            
            Forms\Components\TextInput::make('end')
                ->label(__('tariffs.forms.end_time'))
                ->required()
                ->placeholder(__('tariffs.forms.end_placeholder'))
                ->regex('/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/')
                ->rules(['required', 'string', 'regex:/^([0-1][0-9]|2[0-3]):[0-5][0-9]$/'])
                ->validationMessages([
                    'required' => __('tariffs.validation.configuration.zones.end.required_with'),
                    'string' => __('tariffs.validation.configuration.zones.end.string'),
                    'regex' => __('tariffs.validation.configuration.zones.end.regex'),
                ]),
            
            Forms\Components\TextInput::make('rate')
                ->label(__('tariffs.forms.zone_rate'))
                ->numeric()
                ->minValue(0)
                ->step(0.0001)
                ->suffix(__('app.units.euro'))
                ->required()
                ->rules(['required', 'numeric', 'min:0', 'max:999999.9999'])
                ->validationMessages([
                    'required' => __('tariffs.validation.configuration.zones.rate.required_with'),
                    'numeric' => __('tariffs.validation.configuration.zones.rate.numeric'),
                    'min' => __('tariffs.validation.configuration.zones.rate.min'),
                    'max' => __('tariffs.validation.configuration.zones.rate.max'),
                ]),
        ];
    }

    /**
     * Build the weekend logic field.
     *
     * @return Forms\Components\Select
     */
    protected static function buildWeekendLogicField(): Forms\Components\Select
    {
        return Forms\Components\Select::make('configuration.weekend_logic')
            ->label(__('tariffs.forms.weekend_logic'))
            ->options(WeekendLogic::labels())
            ->nullable()
            ->native(false)
            ->visible(fn (Get $get): bool => $get('configuration.type') === 'time_of_use')
            ->helperText(__('tariffs.forms.weekend_helper'))
            ->rules(['nullable', 'string', 'in:apply_night_rate,apply_day_rate,apply_weekend_rate'])
            ->validationMessages([
                'string' => __('tariffs.validation.configuration.weekend_logic.string'),
                'in' => __('tariffs.validation.configuration.weekend_logic.in'),
            ]);
    }

    /**
     * Build the fixed fee field.
     *
     * @return Forms\Components\TextInput
     */
    protected static function buildFixedFeeField(): Forms\Components\TextInput
    {
        return Forms\Components\TextInput::make('configuration.fixed_fee')
            ->label(__('tariffs.forms.fixed_fee'))
            ->numeric()
            ->minValue(0)
            ->step(0.01)
            ->suffix(__('app.units.euro'))
            ->nullable()
            ->helperText(__('tariffs.forms.fixed_fee_helper'))
            ->rules(['nullable', 'numeric', 'min:0', 'max:999999.99'])
            ->validationMessages([
                'numeric' => __('tariffs.validation.configuration.fixed_fee.numeric'),
                'min' => __('tariffs.validation.configuration.fixed_fee.min'),
                'max' => __('tariffs.validation.configuration.fixed_fee.max'),
            ]);
    }
}
