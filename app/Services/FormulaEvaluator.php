<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\FormulaEvaluationException;

/**
 * Safe mathematical expression evaluator.
 *
 * Supported:
 * - Numbers: 1, 1.5, .5, 1e3, 1.2e-3
 * - Variables: consumption, days, month, etc. (A-Z, a-z, 0-9, _; must start with letter/_)
 * - Operators: +, -, *, /, ^ (power)
 * - Parentheses: ( )
 * - Functions: abs, ceil, floor, round, sqrt, pow, min, max, clamp
 *
 * Not supported (by design, for safety):
 * - Property access / method calls
 * - Arrays / objects
 * - Arbitrary PHP evaluation
 */
final class FormulaEvaluator
{
    private const MAX_EXPRESSION_LENGTH = 2048;
    private const MAX_TOKENS = 512;

    /**
     * @param array<string, array{minArgs:int, maxArgs:int}> $functions
     */
    private array $functions = [
        'abs' => ['minArgs' => 1, 'maxArgs' => 1],
        'ceil' => ['minArgs' => 1, 'maxArgs' => 1],
        'floor' => ['minArgs' => 1, 'maxArgs' => 1],
        'round' => ['minArgs' => 1, 'maxArgs' => 2],
        'sqrt' => ['minArgs' => 1, 'maxArgs' => 1],
        'pow' => ['minArgs' => 2, 'maxArgs' => 2],
        'min' => ['minArgs' => 2, 'maxArgs' => 16],
        'max' => ['minArgs' => 2, 'maxArgs' => 16],
        'clamp' => ['minArgs' => 3, 'maxArgs' => 3],
    ];

    /**
     * Evaluate a formula using the provided variables.
     *
     * @param array<string, mixed> $variables
     */
    public function evaluate(string $expression, array $variables = []): float
    {
        $expression = trim($expression);

        if ($expression === '') {
            throw new FormulaEvaluationException('Formula is empty.');
        }

        if (mb_strlen($expression) > self::MAX_EXPRESSION_LENGTH) {
            throw new FormulaEvaluationException('Formula is too long.');
        }

        $tokens = $this->tokenize($expression);
        $rpn = $this->toReversePolishNotation($tokens);

        return $this->evaluateRpn($rpn, $variables);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);

        for ($i = 0; $i < $length; ) {
            $char = $expression[$i];

            if (ctype_space($char)) {
                $i++;
                continue;
            }

            if (
                ctype_digit($char) ||
                ($char === '.' && ($i + 1) < $length && ctype_digit($expression[$i + 1]))
            ) {
                $start = $i;
                $i++;

                while ($i < $length && (ctype_digit($expression[$i]) || $expression[$i] === '.')) {
                    $i++;
                }

                if ($i < $length && ($expression[$i] === 'e' || $expression[$i] === 'E')) {
                    $i++;

                    if ($i < $length && ($expression[$i] === '+' || $expression[$i] === '-')) {
                        $i++;
                    }

                    if ($i >= $length || !ctype_digit($expression[$i])) {
                        throw new FormulaEvaluationException('Invalid scientific notation in formula.');
                    }

                    while ($i < $length && ctype_digit($expression[$i])) {
                        $i++;
                    }
                }

                $rawNumber = substr($expression, $start, $i - $start);
                if (!is_numeric($rawNumber)) {
                    throw new FormulaEvaluationException("Invalid number '{$rawNumber}' in formula.");
                }

                $tokens[] = ['type' => 'number', 'value' => (float) $rawNumber];
                continue;
            }

            if (ctype_alpha($char) || $char === '_') {
                $start = $i;
                $i++;

                while ($i < $length && (ctype_alnum($expression[$i]) || $expression[$i] === '_')) {
                    $i++;
                }

                $identifier = substr($expression, $start, $i - $start);

                $j = $i;
                while ($j < $length && ctype_space($expression[$j])) {
                    $j++;
                }

                if ($j < $length && $expression[$j] === '(') {
                    $tokens[] = ['type' => 'function', 'name' => $identifier];
                } else {
                    $tokens[] = ['type' => 'identifier', 'name' => $identifier];
                }

                continue;
            }

            if (in_array($char, ['+', '-', '*', '/', '^'], true)) {
                $tokens[] = ['type' => 'operator', 'op' => $char];
                $i++;
                continue;
            }

            if ($char === '(') {
                $tokens[] = ['type' => 'lparen'];
                $i++;
                continue;
            }

            if ($char === ')') {
                $tokens[] = ['type' => 'rparen'];
                $i++;
                continue;
            }

            if ($char === ',') {
                $tokens[] = ['type' => 'comma'];
                $i++;
                continue;
            }

            $position = $i + 1; // 1-based
            throw new FormulaEvaluationException("Unexpected character '{$char}' at position {$position}.");
        }

        if (count($tokens) > self::MAX_TOKENS) {
            throw new FormulaEvaluationException('Formula is too complex.');
        }

        return $tokens;
    }

    /**
     * Convert tokens to Reverse Polish Notation via shunting-yard algorithm.
     *
     * @param array<int, array<string, mixed>> $tokens
     * @return array<int, array<string, mixed>>
     */
    private function toReversePolishNotation(array $tokens): array
    {
        $output = [];
        $stack = [];
        $functionContexts = [];

        $previousType = null;

        foreach ($tokens as $token) {
            $type = $token['type'];

            if ($type === 'number') {
                $this->markFunctionArgumentStart($functionContexts);
                $output[] = $token;
                $previousType = 'number';
                continue;
            }

            if ($type === 'identifier') {
                $this->markFunctionArgumentStart($functionContexts);
                $output[] = $token;
                $previousType = 'identifier';
                continue;
            }

            if ($type === 'function') {
                $this->markFunctionArgumentStart($functionContexts);
                $stack[] = $token;
                $previousType = 'function';
                continue;
            }

            if ($type === 'comma') {
                if (empty($functionContexts)) {
                    throw new FormulaEvaluationException('Unexpected comma outside of a function call.');
                }

                while (!empty($stack) && ($stack[array_key_last($stack)]['type'] ?? null) !== 'lparen') {
                    $output[] = array_pop($stack);
                }

                if (empty($stack)) {
                    throw new FormulaEvaluationException('Mismatched parentheses in formula.');
                }

                $functionContexts[array_key_last($functionContexts)]['expectingArgument'] = true;
                $previousType = 'comma';
                continue;
            }

            if ($type === 'operator') {
                $op = $token['op'];
                $isUnary = in_array($op, ['+', '-'], true) && in_array($previousType, [null, 'operator', 'lparen', 'comma'], true);

                if ($isUnary) {
                    $this->markFunctionArgumentStart($functionContexts);
                    $op = $op === '-' ? 'u-' : 'u+';
                }

                $o1 = ['type' => 'operator', 'op' => $op];

                while (!empty($stack)) {
                    $top = $stack[array_key_last($stack)];
                    if (($top['type'] ?? null) !== 'operator') {
                        break;
                    }

                    $o2 = $top;

                    if (
                        ($this->isLeftAssociative($o1['op']) && $this->precedence($o1['op']) <= $this->precedence($o2['op'])) ||
                        (!$this->isLeftAssociative($o1['op']) && $this->precedence($o1['op']) < $this->precedence($o2['op']))
                    ) {
                        $output[] = array_pop($stack);
                        continue;
                    }

                    break;
                }

                $stack[] = $o1;
                $previousType = 'operator';
                continue;
            }

            if ($type === 'lparen') {
                $this->markFunctionArgumentStart($functionContexts);
                $stack[] = $token;

                $previousWasFunction = $previousType === 'function';
                if ($previousWasFunction) {
                    $functionContexts[] = [
                        'argc' => 0,
                        'expectingArgument' => true,
                    ];
                }

                $previousType = 'lparen';
                continue;
            }

            if ($type === 'rparen') {
                while (!empty($stack) && ($stack[array_key_last($stack)]['type'] ?? null) !== 'lparen') {
                    $output[] = array_pop($stack);
                }

                if (empty($stack)) {
                    throw new FormulaEvaluationException('Mismatched parentheses in formula.');
                }

                array_pop($stack); // pop lparen

                $top = $stack[array_key_last($stack)] ?? null;
                if (($top['type'] ?? null) === 'function') {
                    $functionToken = array_pop($stack);
                    $context = array_pop($functionContexts);

                    if (($context['expectingArgument'] ?? true) === true) {
                        throw new FormulaEvaluationException("Function '{$functionToken['name']}' has a missing argument.");
                    }

                    $functionToken['argc'] = (int) ($context['argc'] ?? 0);
                    $output[] = $functionToken;
                }

                $previousType = 'rparen';
                continue;
            }

            throw new FormulaEvaluationException('Invalid token encountered during parsing.');
        }

        while (!empty($stack)) {
            $top = array_pop($stack);
            if (($top['type'] ?? null) === 'lparen') {
                throw new FormulaEvaluationException('Mismatched parentheses in formula.');
            }
            $output[] = $top;
        }

        if (!empty($functionContexts)) {
            throw new FormulaEvaluationException('Mismatched function parentheses in formula.');
        }

        return $output;
    }

    /**
     * If we're inside a function and are expecting a new argument, mark it as started.
     *
     * @param array<int, array{argc:int, expectingArgument:bool}> $functionContexts
     */
    private function markFunctionArgumentStart(array &$functionContexts): void
    {
        if (empty($functionContexts)) {
            return;
        }

        $idx = array_key_last($functionContexts);

        if (($functionContexts[$idx]['expectingArgument'] ?? false) !== true) {
            return;
        }

        $functionContexts[$idx]['argc']++;
        $functionContexts[$idx]['expectingArgument'] = false;
    }

    private function precedence(string $operator): int
    {
        return match ($operator) {
            '^' => 4,
            'u-', 'u+' => 3,
            '*', '/' => 2,
            '+', '-' => 1,
            default => throw new FormulaEvaluationException("Unknown operator '{$operator}'."),
        };
    }

    private function isLeftAssociative(string $operator): bool
    {
        return match ($operator) {
            '^', 'u-', 'u+' => false,
            default => true,
        };
    }

    /**
     * @param array<int, array<string, mixed>> $rpn
     * @param array<string, mixed> $variables
     */
    private function evaluateRpn(array $rpn, array $variables): float
    {
        $stack = [];

        foreach ($rpn as $token) {
            $type = $token['type'] ?? null;

            if ($type === 'number') {
                $stack[] = (float) $token['value'];
                continue;
            }

            if ($type === 'identifier') {
                $name = (string) $token['name'];

                if (!array_key_exists($name, $variables)) {
                    throw new FormulaEvaluationException("Unknown variable '{$name}'.");
                }

                $stack[] = $this->toNumeric($variables[$name], $name);
                continue;
            }

            if ($type === 'operator') {
                $op = (string) $token['op'];

                if (in_array($op, ['u-', 'u+'], true)) {
                    $value = array_pop($stack);
                    if (!is_float($value) && !is_int($value)) {
                        throw new FormulaEvaluationException('Invalid unary operator operand.');
                    }

                    $stack[] = $op === 'u-' ? -(float) $value : (float) $value;
                    continue;
                }

                $right = array_pop($stack);
                $left = array_pop($stack);

                if (!is_numeric($left) || !is_numeric($right)) {
                    throw new FormulaEvaluationException('Invalid operator operands.');
                }

                $stack[] = match ($op) {
                    '+' => (float) $left + (float) $right,
                    '-' => (float) $left - (float) $right,
                    '*' => (float) $left * (float) $right,
                    '/' => $this->safeDivide((float) $left, (float) $right),
                    '^' => (float) pow((float) $left, (float) $right),
                    default => throw new FormulaEvaluationException("Unknown operator '{$op}'."),
                };

                continue;
            }

            if ($type === 'function') {
                $name = strtolower((string) ($token['name'] ?? ''));
                $argc = (int) ($token['argc'] ?? 0);

                $spec = $this->functions[$name] ?? null;
                if (!$spec) {
                    throw new FormulaEvaluationException("Unknown function '{$name}'.");
                }

                if ($argc < $spec['minArgs'] || $argc > $spec['maxArgs']) {
                    throw new FormulaEvaluationException("Function '{$name}' expects {$spec['minArgs']}..{$spec['maxArgs']} arguments, got {$argc}.");
                }

                if ($argc > count($stack)) {
                    throw new FormulaEvaluationException("Not enough arguments for function '{$name}'.");
                }

                $args = array_splice($stack, -$argc, $argc);
                $args = array_map(fn ($v) => (float) $v, $args);

                $stack[] = $this->callFunction($name, $args);
                continue;
            }

            throw new FormulaEvaluationException('Invalid RPN token encountered during evaluation.');
        }

        if (count($stack) !== 1) {
            throw new FormulaEvaluationException('Formula did not evaluate to a single value.');
        }

        $result = (float) $stack[0];

        if (is_nan($result) || is_infinite($result)) {
            throw new FormulaEvaluationException('Formula result is not a finite number.');
        }

        return $result;
    }

    private function safeDivide(float $left, float $right): float
    {
        if (abs($right) < 1e-12) {
            throw new FormulaEvaluationException('Division by zero.');
        }

        return $left / $right;
    }

    private function callFunction(string $name, array $args): float
    {
        return match ($name) {
            'abs' => abs($args[0]),
            'ceil' => (float) ceil($args[0]),
            'floor' => (float) floor($args[0]),
            'round' => (float) round($args[0], isset($args[1]) ? (int) $args[1] : 0),
            'sqrt' => $this->safeSqrt($args[0]),
            'pow' => (float) pow($args[0], $args[1]),
            'min' => (float) min($args),
            'max' => (float) max($args),
            'clamp' => (float) max($args[1], min($args[2], $args[0])),
            default => throw new FormulaEvaluationException("Unknown function '{$name}'."),
        };
    }

    private function safeSqrt(float $value): float
    {
        if ($value < 0) {
            throw new FormulaEvaluationException('sqrt() does not accept negative values.');
        }

        return (float) sqrt($value);
    }

    private function toNumeric(mixed $value, string $name): float
    {
        if (is_bool($value)) {
            return $value ? 1.0 : 0.0;
        }

        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        if (is_string($value) && is_numeric($value)) {
            return (float) $value;
        }

        throw new FormulaEvaluationException("Variable '{$name}' must be numeric.");
    }
}

