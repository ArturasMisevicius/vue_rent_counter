<?php

declare(strict_types=1);

use App\Services\FormulaEvaluator;

describe('FormulaEvaluator', function () {
    it('evaluates basic arithmetic with operator precedence', function () {
        $evaluator = new FormulaEvaluator;

        expect($evaluator->evaluate('1 + 2 * 3'))->toBe(7.0);
        expect($evaluator->evaluate('(1 + 2) * 3'))->toBe(9.0);
    });

    it('supports variables and numeric strings', function () {
        $evaluator = new FormulaEvaluator;

        $result = $evaluator->evaluate('consumption * rate + base_fee', [
            'consumption' => 100,
            'rate' => '0.2',
            'base_fee' => 10,
        ]);

        expect($result)->toBe(30.0);
    });

    it('supports unary minus expressions', function () {
        $evaluator = new FormulaEvaluator;

        expect($evaluator->evaluate('-2 * 3'))->toBe(-6.0);
        expect($evaluator->evaluate('-(2 + 3)'))->toBe(-5.0);
    });

    it('rejects unsupported functions and scientific notation', function () {
        $evaluator = new FormulaEvaluator;

        expect(fn () => $evaluator->evaluate('min(5, 2, 3)'))->toThrow(InvalidArgumentException::class);
        expect(fn () => $evaluator->evaluate('1e3 + 1'))->toThrow(InvalidArgumentException::class);
    });

    it('rejects unknown variables', function () {
        $evaluator = new FormulaEvaluator;

        expect(fn () => $evaluator->evaluate('consumption + missing', [
            'consumption' => 1,
        ]))->toThrow(InvalidArgumentException::class);
    });

    it('rejects unexpected characters and division by zero', function () {
        $evaluator = new FormulaEvaluator;

        expect(fn () => $evaluator->evaluate('1 + system(1)'))->toThrow(InvalidArgumentException::class);
        expect(fn () => $evaluator->evaluate('1 / 0'))->toThrow(InvalidArgumentException::class);
    });
});
