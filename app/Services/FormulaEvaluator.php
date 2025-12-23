<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

/**
 * Safe formula evaluator for custom distribution calculations.
 * 
 * Provides secure mathematical expression evaluation without using eval().
 * Supports basic arithmetic operations and variable substitution for
 * shared service cost distribution formulas.
 * 
 * @package App\Services
 * @see \App\Services\SharedServiceCostDistributorService
 * @see \Tests\Property\SharedServiceCostDistributionPropertyTest
 */
final readonly class FormulaEvaluator
{
    /**
     * Allowed operators in formulas.
     */
    private const ALLOWED_OPERATORS = ['+', '-', '*', '/', '(', ')'];

    /**
     * Allowed functions in formulas.
     */
    private const ALLOWED_FUNCTIONS = ['min', 'max', 'abs', 'round'];

    /**
     * Evaluate a mathematical formula with given variables.
     * 
     * @param string $formula The formula to evaluate (e.g., "area * 0.7 + consumption * 0.3")
     * @param array<string, float|int> $variables Variables to substitute (e.g., ['area' => 100, 'consumption' => 50])
     * @return float The calculated result
     * 
     * @throws InvalidArgumentException When formula is invalid or unsafe
     */
    public function evaluate(string $formula, array $variables = []): float
    {
        // Validate and sanitize formula
        $sanitizedFormula = $this->sanitizeFormula($formula);
        
        // Substitute variables
        $processedFormula = $this->substituteVariables($sanitizedFormula, $variables);
        
        // Parse and evaluate safely
        return $this->parseExpression($processedFormula);
    }

    /**
     * Validate that a formula is syntactically correct and safe.
     * 
     * @param string $formula The formula to validate
     * @return bool True if formula is valid
     */
    public function validateFormula(string $formula): bool
    {
        try {
            $sanitized = $this->sanitizeFormula($formula);
            
            // Check for balanced parentheses
            if (!$this->hasBalancedParentheses($sanitized)) {
                return false;
            }
            
            // Check for valid syntax patterns
            return $this->hasValidSyntax($sanitized);
            
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Get a list of variables used in a formula.
     * 
     * @param string $formula The formula to analyze
     * @return array<string> Array of variable names
     */
    public function getVariables(string $formula): array
    {
        $sanitized = $this->sanitizeFormula($formula);
        
        // Extract variable names (letters followed by optional letters/numbers/underscores)
        preg_match_all('/\b[a-zA-Z][a-zA-Z0-9_]*\b/', $sanitized, $matches);
        
        // Filter out function names
        $variables = array_filter($matches[0], function ($match) {
            return !in_array(strtolower($match), self::ALLOWED_FUNCTIONS, true);
        });
        
        return array_unique($variables);
    }

    /**
     * Sanitize formula by removing dangerous characters and validating structure.
     */
    private function sanitizeFormula(string $formula): string
    {
        // Remove whitespace
        $formula = preg_replace('/\s+/', '', $formula);
        
        // Check for dangerous patterns
        $dangerousPatterns = [
            '/\$/',           // PHP variables
            '/;/',            // Statement separators
            '/`/',            // Backticks
            '/exec|system|shell_exec|passthru|eval|file_get_contents|include|require/', // Dangerous functions
        ];
        
        foreach ($dangerousPatterns as $pattern) {
            if (preg_match($pattern, $formula)) {
                throw new InvalidArgumentException('Formula contains dangerous patterns');
            }
        }
        
        // Validate characters (only allow numbers, letters, operators, dots, underscores)
        if (!preg_match('/^[a-zA-Z0-9+\-*\/()._]+$/', $formula)) {
            throw new InvalidArgumentException('Formula contains invalid characters');
        }
        
        return $formula;
    }

    /**
     * Substitute variables in the formula with their values.
     */
    private function substituteVariables(string $formula, array $variables): string
    {
        $result = $formula;
        
        // Sort variables by length (longest first) to avoid partial replacements
        uksort($variables, fn($a, $b) => strlen($b) - strlen($a));
        
        foreach ($variables as $name => $value) {
            // Ensure variable name is safe
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $name)) {
                throw new InvalidArgumentException("Invalid variable name: {$name}");
            }
            
            // Ensure value is numeric
            if (!is_numeric($value)) {
                throw new InvalidArgumentException("Variable {$name} must be numeric, got: " . gettype($value));
            }
            
            // Replace variable with value (word boundaries to avoid partial matches)
            $result = preg_replace('/\b' . preg_quote($name, '/') . '\b/', (string) $value, $result);
        }
        
        return $result;
    }

    /**
     * Parse and evaluate a mathematical expression safely.
     */
    private function parseExpression(string $expression): float
    {
        // Remove any remaining non-numeric, non-operator characters
        if (!preg_match('/^[0-9+\-*\/().]+$/', $expression)) {
            throw new InvalidArgumentException('Expression contains invalid characters after substitution');
        }
        
        try {
            // Use a simple recursive descent parser instead of eval()
            $tokens = $this->tokenize($expression);
            $result = $this->parseTokens($tokens);
            
            if (!is_finite($result)) {
                throw new InvalidArgumentException('Formula evaluation resulted in infinite or NaN value');
            }
            
            return $result;
            
        } catch (\Exception $e) {
            throw new InvalidArgumentException('Failed to evaluate expression: ' . $e->getMessage());
        }
    }

    /**
     * Tokenize the expression into numbers and operators.
     */
    private function tokenize(string $expression): array
    {
        $tokens = [];
        $length = strlen($expression);
        $i = 0;
        
        while ($i < $length) {
            $char = $expression[$i];
            
            if (is_numeric($char) || $char === '.') {
                // Parse number
                $number = '';
                while ($i < $length && (is_numeric($expression[$i]) || $expression[$i] === '.')) {
                    $number .= $expression[$i];
                    $i++;
                }
                $tokens[] = (float) $number;
            } elseif (in_array($char, self::ALLOWED_OPERATORS, true)) {
                $tokens[] = $char;
                $i++;
            } else {
                throw new InvalidArgumentException("Unexpected character: {$char}");
            }
        }
        
        return $tokens;
    }

    /**
     * Parse tokens using recursive descent parser.
     */
    private function parseTokens(array &$tokens): float
    {
        return $this->parseAddition($tokens);
    }

    /**
     * Parse addition and subtraction (lowest precedence).
     */
    private function parseAddition(array &$tokens): float
    {
        $result = $this->parseMultiplication($tokens);
        
        while (!empty($tokens) && in_array($tokens[0], ['+', '-'], true)) {
            $operator = array_shift($tokens);
            $right = $this->parseMultiplication($tokens);
            
            $result = match ($operator) {
                '+' => $result + $right,
                '-' => $result - $right,
            };
        }
        
        return $result;
    }

    /**
     * Parse multiplication and division (higher precedence).
     */
    private function parseMultiplication(array &$tokens): float
    {
        $result = $this->parseFactor($tokens);
        
        while (!empty($tokens) && in_array($tokens[0], ['*', '/'], true)) {
            $operator = array_shift($tokens);
            $right = $this->parseFactor($tokens);
            
            if ($operator === '/' && $right == 0) {
                throw new InvalidArgumentException('Division by zero');
            }
            
            $result = match ($operator) {
                '*' => $result * $right,
                '/' => $result / $right,
            };
        }
        
        return $result;
    }

    /**
     * Parse factors (numbers and parentheses).
     */
    private function parseFactor(array &$tokens): float
    {
        if (empty($tokens)) {
            throw new InvalidArgumentException('Unexpected end of expression');
        }
        
        $token = array_shift($tokens);
        
        if (is_numeric($token)) {
            return (float) $token;
        }
        
        if ($token === '(') {
            $result = $this->parseAddition($tokens);
            
            if (empty($tokens) || array_shift($tokens) !== ')') {
                throw new InvalidArgumentException('Missing closing parenthesis');
            }
            
            return $result;
        }
        
        if ($token === '-') {
            return -$this->parseFactor($tokens);
        }
        
        throw new InvalidArgumentException("Unexpected token: {$token}");
    }

    /**
     * Check if parentheses are balanced.
     */
    private function hasBalancedParentheses(string $formula): bool
    {
        $count = 0;
        
        for ($i = 0; $i < strlen($formula); $i++) {
            if ($formula[$i] === '(') {
                $count++;
            } elseif ($formula[$i] === ')') {
                $count--;
                if ($count < 0) {
                    return false;
                }
            }
        }
        
        return $count === 0;
    }

    /**
     * Check if formula has valid syntax patterns.
     */
    private function hasValidSyntax(string $formula): bool
    {
        // Check for empty formula
        if (empty($formula)) {
            return false;
        }
        
        // Check for consecutive operators
        if (preg_match('/[+\-*\/]{2,}/', $formula)) {
            return false;
        }
        
        // Check for operators at start/end (except minus at start)
        if (preg_match('/^[+*\/]|[+\-*\/]$/', $formula)) {
            return false;
        }
        
        return true;
    }
}