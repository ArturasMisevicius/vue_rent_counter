<?php

declare(strict_types=1);

use App\Enums\DistributionMethod;

describe('DistributionMethod Enum', function () {
    it('has all expected cases', function () {
        $cases = DistributionMethod::cases();
        $values = array_map(fn ($case) => $case->value, $cases);
        
        expect($values)->toContain('equal');
        expect($values)->toContain('area');
        expect($values)->toContain('by_consumption');
        expect($values)->toContain('custom_formula');
        expect($cases)->toHaveCount(4);
    });

    describe('area data requirements', function () {
        it('correctly identifies methods that require area data', function () {
            expect(DistributionMethod::AREA->requiresAreaData())->toBeTrue();
        });

        it('correctly identifies methods that do not require area data', function () {
            expect(DistributionMethod::EQUAL->requiresAreaData())->toBeFalse();
            expect(DistributionMethod::BY_CONSUMPTION->requiresAreaData())->toBeFalse();
            expect(DistributionMethod::CUSTOM_FORMULA->requiresAreaData())->toBeFalse();
        });
    });

    describe('consumption data requirements', function () {
        it('correctly identifies methods that require consumption data', function () {
            expect(DistributionMethod::BY_CONSUMPTION->requiresConsumptionData())->toBeTrue();
        });

        it('correctly identifies methods that do not require consumption data', function () {
            expect(DistributionMethod::EQUAL->requiresConsumptionData())->toBeFalse();
            expect(DistributionMethod::AREA->requiresConsumptionData())->toBeFalse();
            expect(DistributionMethod::CUSTOM_FORMULA->requiresConsumptionData())->toBeFalse();
        });
    });

    describe('custom formula support', function () {
        it('correctly identifies methods that support custom formulas', function () {
            expect(DistributionMethod::CUSTOM_FORMULA->supportsCustomFormulas())->toBeTrue();
        });

        it('correctly identifies methods that do not support custom formulas', function () {
            expect(DistributionMethod::EQUAL->supportsCustomFormulas())->toBeFalse();
            expect(DistributionMethod::AREA->supportsCustomFormulas())->toBeFalse();
            expect(DistributionMethod::BY_CONSUMPTION->supportsCustomFormulas())->toBeFalse();
        });
    });

    describe('supported area types', function () {
        it('returns area types for area-based distribution', function () {
            $areaTypes = DistributionMethod::AREA->getSupportedAreaTypes();
            
            expect($areaTypes)->toBeArray();
            expect($areaTypes)->toHaveKey('total_area');
            expect($areaTypes)->toHaveKey('heated_area');
            expect($areaTypes)->toHaveKey('commercial_area');
            expect($areaTypes)->toHaveCount(3);
        });

        it('returns empty array for non-area-based methods', function () {
            expect(DistributionMethod::EQUAL->getSupportedAreaTypes())->toBe([]);
            expect(DistributionMethod::BY_CONSUMPTION->getSupportedAreaTypes())->toBe([]);
            expect(DistributionMethod::CUSTOM_FORMULA->getSupportedAreaTypes())->toBe([]);
        });

        it('returns translated labels for area types', function () {
            $areaTypes = DistributionMethod::AREA->getSupportedAreaTypes();
            
            foreach ($areaTypes as $key => $label) {
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
            }
        });
    });

    describe('labels and descriptions', function () {
        it('provides labels for all cases', function () {
            foreach (DistributionMethod::cases() as $method) {
                $label = $method->getLabel();
                expect($label)->toBeString();
                expect($label)->not->toBeEmpty();
            }
        });

        it('provides descriptions for all cases', function () {
            foreach (DistributionMethod::cases() as $method) {
                $description = $method->getDescription();
                expect($description)->toBeString();
                expect($description)->not->toBeEmpty();
            }
        });

        it('has unique labels for each case', function () {
            $labels = array_map(
                fn ($method) => $method->getLabel(),
                DistributionMethod::cases()
            );
            
            expect($labels)->toHaveCount(count(array_unique($labels)));
        });
    });

    describe('backward compatibility', function () {
        it('maintains existing EQUAL and AREA methods', function () {
            expect(DistributionMethod::EQUAL->value)->toBe('equal');
            expect(DistributionMethod::AREA->value)->toBe('area');
        });

        it('preserves requiresAreaData method behavior', function () {
            // This method existed before the enhancement
            expect(DistributionMethod::AREA->requiresAreaData())->toBeTrue();
            expect(DistributionMethod::EQUAL->requiresAreaData())->toBeFalse();
        });
    });

    describe('new capabilities', function () {
        it('adds BY_CONSUMPTION method', function () {
            expect(DistributionMethod::BY_CONSUMPTION->value)->toBe('by_consumption');
            expect(DistributionMethod::BY_CONSUMPTION->requiresConsumptionData())->toBeTrue();
        });

        it('adds CUSTOM_FORMULA method', function () {
            expect(DistributionMethod::CUSTOM_FORMULA->value)->toBe('custom_formula');
            expect(DistributionMethod::CUSTOM_FORMULA->supportsCustomFormulas())->toBeTrue();
        });

        it('adds requiresConsumptionData method', function () {
            // This is a new method added in the enhancement
            expect(method_exists(DistributionMethod::class, 'requiresConsumptionData'))->toBeTrue();
        });

        it('adds supportsCustomFormulas method', function () {
            // This is a new method added in the enhancement
            expect(method_exists(DistributionMethod::class, 'supportsCustomFormulas'))->toBeTrue();
        });

        it('adds getSupportedAreaTypes method', function () {
            // This is a new method added in the enhancement
            expect(method_exists(DistributionMethod::class, 'getSupportedAreaTypes'))->toBeTrue();
        });
    });

    describe('method combinations', function () {
        it('ensures methods have mutually exclusive primary characteristics', function () {
            foreach (DistributionMethod::cases() as $method) {
                $requiresArea = $method->requiresAreaData();
                $requiresConsumption = $method->requiresConsumptionData();
                $supportsFormula = $method->supportsCustomFormulas();
                
                // Count how many primary characteristics this method has
                $characteristics = array_filter([
                    $requiresArea,
                    $requiresConsumption,
                    $supportsFormula,
                ]);
                
                // Each method should have at most one primary characteristic
                // EQUAL has none (it's the simplest)
                expect(count($characteristics))->toBeLessThanOrEqual(1);
            }
        });

        it('EQUAL method is the simplest with no special requirements', function () {
            expect(DistributionMethod::EQUAL->requiresAreaData())->toBeFalse();
            expect(DistributionMethod::EQUAL->requiresConsumptionData())->toBeFalse();
            expect(DistributionMethod::EQUAL->supportsCustomFormulas())->toBeFalse();
        });
    });
});
