<?php

declare(strict_types=1);

use App\Exceptions\FormulaEvaluationException;
use App\Services\FormulaEvaluator;

describe('FormulaEvaluator', function () {
    it('evaluates basic arithmetic with operator precedence', function () {
        $evaluator = new FormulaEvaluator();

        expect($evaluator->evaluate('1 + 2 * 3'))->toBe(7.0);
        expect($evaluator->evaluate('(1 + 2) * 3'))->toBe(9.0);
    });

    it('supports variables and numeric strings', function () {
        $evaluator = new FormulaEvaluator();

        $result = $evaluator->evaluate('consumption * rate + base_fee', [
            'consumption' => 100,
            'rate' => '0.2',
            'base_fee' => 10,
        ]);

        expect($result)->toBe(30.0);
    });

    it('supports unary operators and power', function () {
        $evaluator = new FormulaEvaluator();

        expect($evaluator->evaluate('-2^3'))->toBe(-8.0);
        expect($evaluator->evaluate('(-2)^3'))->toBe(-8.0);
        expect($evaluator->evaluate('-(2^3)'))->toBe(-8.0);
    });

    it('supports built-in functions', function () {
        $evaluator = new FormulaEvaluator();

        expect($evaluator->evaluate('min(5, 2, 3)'))->toBe(2.0);
        expect($evaluator->evaluate('max(5, 2, 3)'))->toBe(5.0);
        expect($evaluator->evaluate('round(1.2345, 2)'))->toBe(1.23);
        expect($evaluator->evaluate('clamp(10, 0, 5)'))->toBe(5.0);
    });

    it('supports scientific notation', function () {
        $evaluator = new FormulaEvaluator();

        expect($evaluator->evaluate('1e3 + 1'))->toBe(1001.0);
        expect($evaluator->evaluate('1.2e-3 * 1000'))->toBe(1.2);
    });

    it('rejects unknown variables', function () {
        $evaluator = new FormulaEvaluator();

        expect(fn () => $evaluator->evaluate('consumption + missing', [
            'consumption' => 1,
        ]))->toThrow(FormulaEvaluationException::class);
    });

    it('rejects unexpected characters and division by zero', function () {
        $evaluator = new FormulaEvaluator();

        expect(fn () => $evaluator->evaluate('1 + system(1)'))->toThrow(FormulaEvaluationException::class);
        expect(fn () => $evaluator->evaluate('1 / 0'))->toThrow(FormulaEvaluationException::class);
    });
});

