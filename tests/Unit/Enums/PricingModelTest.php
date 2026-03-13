<?php

declare(strict_types=1);

use App\Enums\PricingModel;
use App\Enums\TariffType;

describe('PricingModel Enum', function () {
    it('has all expected cases', function () {
        $cases = PricingModel::cases();
        $values = array_map(fn ($case) => $case->value, $cases);
        
        expect($values)->toContain(
            'fixed_monthly',
            'consumption_based',
            'tiered_rates',
            'hybrid',
            'custom_formula',
            'flat',
            'time_of_use'
        );
    });

    it('implements required interfaces', function () {
        $model = PricingModel::FIXED_MONTHLY;
        
        expect($model)->toBeInstanceOf(\Filament\Support\Contracts\HasLabel::class);
        expect($model)->toBeInstanceOf(\Filament\Support\Contracts\HasColor::class);
        expect($model)->toBeInstanceOf(\Filament\Support\Contracts\HasIcon::class);
    });

    describe('consumption data requirements', function () {
        it('correctly identifies models that require consumption data', function () {
            expect(PricingModel::CONSUMPTION_BASED->requiresConsumptionData())->toBeTrue();
            expect(PricingModel::TIERED_RATES->requiresConsumptionData())->toBeTrue();
            expect(PricingModel::HYBRID->requiresConsumptionData())->toBeTrue();
            expect(PricingModel::TIME_OF_USE->requiresConsumptionData())->toBeTrue();
        });

        it('correctly identifies models that do not require consumption data', function () {
            expect(PricingModel::FIXED_MONTHLY->requiresConsumptionData())->toBeFalse();
            expect(PricingModel::CUSTOM_FORMULA->requiresConsumptionData())->toBeFalse();
            expect(PricingModel::FLAT->requiresConsumptionData())->toBeFalse();
        });
    });

    describe('time-based pricing support', function () {
        it('correctly identifies models that support time-based pricing', function () {
            expect(PricingModel::TIME_OF_USE->supportsTimeBasedPricing())->toBeTrue();
            expect(PricingModel::HYBRID->supportsTimeBasedPricing())->toBeTrue();
            expect(PricingModel::CUSTOM_FORMULA->supportsTimeBasedPricing())->toBeTrue();
        });

        it('correctly identifies models that do not support time-based pricing', function () {
            expect(PricingModel::FIXED_MONTHLY->supportsTimeBasedPricing())->toBeFalse();
            expect(PricingModel::CONSUMPTION_BASED->supportsTimeBasedPricing())->toBeFalse();
            expect(PricingModel::TIERED_RATES->supportsTimeBasedPricing())->toBeFalse();
            expect(PricingModel::FLAT->supportsTimeBasedPricing())->toBeFalse();
        });
    });

    describe('custom formula support', function () {
        it('only custom formula model supports custom formulas', function () {
            expect(PricingModel::CUSTOM_FORMULA->supportsCustomFormulas())->toBeTrue();
            
            foreach (PricingModel::cases() as $model) {
                if ($model !== PricingModel::CUSTOM_FORMULA) {
                    expect($model->supportsCustomFormulas())->toBeFalse();
                }
            }
        });
    });

    describe('tiered rates support', function () {
        it('correctly identifies models that support tiered rates', function () {
            expect(PricingModel::TIERED_RATES->supportsTieredRates())->toBeTrue();
            expect(PricingModel::HYBRID->supportsTieredRates())->toBeTrue();
        });

        it('correctly identifies models that do not support tiered rates', function () {
            expect(PricingModel::FIXED_MONTHLY->supportsTieredRates())->toBeFalse();
            expect(PricingModel::CONSUMPTION_BASED->supportsTieredRates())->toBeFalse();
            expect(PricingModel::CUSTOM_FORMULA->supportsTieredRates())->toBeFalse();
            expect(PricingModel::FLAT->supportsTieredRates())->toBeFalse();
            expect(PricingModel::TIME_OF_USE->supportsTieredRates())->toBeFalse();
        });
    });

    describe('fixed components support', function () {
        it('correctly identifies models with fixed components', function () {
            expect(PricingModel::FIXED_MONTHLY->hasFixedComponents())->toBeTrue();
            expect(PricingModel::HYBRID->hasFixedComponents())->toBeTrue();
            expect(PricingModel::FLAT->hasFixedComponents())->toBeTrue();
        });

        it('correctly identifies models without fixed components', function () {
            expect(PricingModel::CONSUMPTION_BASED->hasFixedComponents())->toBeFalse();
            expect(PricingModel::TIERED_RATES->hasFixedComponents())->toBeFalse();
            expect(PricingModel::CUSTOM_FORMULA->hasFixedComponents())->toBeFalse();
            expect(PricingModel::TIME_OF_USE->hasFixedComponents())->toBeFalse();
        });
    });

    describe('complexity levels', function () {
        it('correctly categorizes simple models', function () {
            expect(PricingModel::FIXED_MONTHLY->getComplexityLevel())->toBe('simple');
            expect(PricingModel::FLAT->getComplexityLevel())->toBe('simple');
        });

        it('correctly categorizes moderate models', function () {
            expect(PricingModel::CONSUMPTION_BASED->getComplexityLevel())->toBe('moderate');
            expect(PricingModel::TIME_OF_USE->getComplexityLevel())->toBe('moderate');
        });

        it('correctly categorizes complex models', function () {
            expect(PricingModel::TIERED_RATES->getComplexityLevel())->toBe('complex');
            expect(PricingModel::HYBRID->getComplexityLevel())->toBe('complex');
            expect(PricingModel::CUSTOM_FORMULA->getComplexityLevel())->toBe('complex');
        });
    });

    describe('legacy compatibility', function () {
        it('converts from TariffType string correctly', function () {
            expect(PricingModel::fromTariffType('flat'))->toBe(PricingModel::FLAT);
            expect(PricingModel::fromTariffType('time_of_use'))->toBe(PricingModel::TIME_OF_USE);
        });

        it('throws exception for invalid TariffType string', function () {
            expect(fn () => PricingModel::fromTariffType('invalid'))
                ->toThrow(InvalidArgumentException::class, 'Invalid tariff type: invalid');
        });

        it('converts from TariffType enum correctly', function () {
            expect(PricingModel::fromTariffTypeEnum(TariffType::FLAT))->toBe(PricingModel::FLAT);
            expect(PricingModel::fromTariffTypeEnum(TariffType::TIME_OF_USE))->toBe(PricingModel::TIME_OF_USE);
        });

        it('converts to TariffType string correctly', function () {
            expect(PricingModel::FLAT->toTariffType())->toBe('flat');
            expect(PricingModel::TIME_OF_USE->toTariffType())->toBe('time_of_use');
        });

        it('throws exception when converting non-legacy models to TariffType', function () {
            expect(fn () => PricingModel::FIXED_MONTHLY->toTariffType())
                ->toThrow(InvalidArgumentException::class, 'Cannot convert fixed_monthly to TariffType');
        });

        it('correctly identifies legacy compatible models', function () {
            expect(PricingModel::FLAT->isLegacyCompatible())->toBeTrue();
            expect(PricingModel::TIME_OF_USE->isLegacyCompatible())->toBeTrue();
            expect(PricingModel::FIXED_MONTHLY->isLegacyCompatible())->toBeFalse();
            expect(PricingModel::CONSUMPTION_BASED->isLegacyCompatible())->toBeFalse();
        });
    });

    describe('static helper methods', function () {
        it('returns correct modern models', function () {
            $modernModels = PricingModel::modernModels();
            
            expect($modernModels)->toContain(
                PricingModel::FIXED_MONTHLY,
                PricingModel::CONSUMPTION_BASED,
                PricingModel::TIERED_RATES,
                PricingModel::HYBRID,
                PricingModel::CUSTOM_FORMULA
            );
            
            expect($modernModels)->not->toContain(
                PricingModel::FLAT,
                PricingModel::TIME_OF_USE
            );
        });

        it('returns correct legacy models', function () {
            $legacyModels = PricingModel::legacyModels();
            
            expect($legacyModels)->toContain(
                PricingModel::FLAT,
                PricingModel::TIME_OF_USE
            );
            
            expect($legacyModels)->not->toContain(
                PricingModel::FIXED_MONTHLY,
                PricingModel::CONSUMPTION_BASED
            );
        });

        it('returns correct meter reading models', function () {
            $meterReadingModels = PricingModel::meterReadingModels();
            
            foreach ($meterReadingModels as $model) {
                expect($model->requiresConsumptionData())->toBeTrue();
            }
        });
    });

    describe('Filament integration', function () {
        it('provides labels for all cases', function () {
            foreach (PricingModel::cases() as $model) {
                expect($model->getLabel())->toBeString();
                expect($model->getLabel())->not->toBeEmpty();
            }
        });

        it('provides colors for all cases', function () {
            foreach (PricingModel::cases() as $model) {
                expect($model->getColor())->toBeString();
                expect($model->getColor())->not->toBeEmpty();
            }
        });

        it('provides icons for all cases', function () {
            foreach (PricingModel::cases() as $model) {
                expect($model->getIcon())->toBeString();
                expect($model->getIcon())->not->toBeEmpty();
                expect($model->getIcon())->toStartWith('heroicon-');
            }
        });

        it('provides descriptions for all cases', function () {
            foreach (PricingModel::cases() as $model) {
                expect($model->getDescription())->toBeString();
                expect($model->getDescription())->not->toBeEmpty();
            }
        });
    });
});