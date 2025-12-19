<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;

/**
 * CSP Violation Request Validation
 * 
 * Validates and sanitizes CSP violation reports from browsers
 * with enhanced security measures and rate limiting.
 */
final class CspViolationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // CSP reports are public but rate limited
        return $this->checkRateLimit();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'csp-report' => ['required', 'array'],
            'csp-report.violated-directive' => ['required', 'string', 'max:100'],
            'csp-report.blocked-uri' => ['nullable', 'string', 'max:2048'],
            'csp-report.document-uri' => ['required', 'string', 'max:2048'],
            'csp-report.referrer' => ['nullable', 'string', 'max:2048'],
            'csp-report.source-file' => ['nullable', 'string', 'max:2048'],
            'csp-report.line-number' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'csp-report.column-number' => ['nullable', 'integer', 'min:0', 'max:999999'],
            'csp-report.original-policy' => ['nullable', 'string', 'max:4096'],
            'csp-report.effective-directive' => ['nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'csp-report.required' => 'CSP report data is required',
            'csp-report.violated-directive.required' => 'Violated directive is required',
            'csp-report.document-uri.required' => 'Document URI is required',
            '*.max' => 'The :attribute field is too long',
            '*.integer' => 'The :attribute must be a valid integer',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Validate content type and size
        if (!$this->isJson()) {
            abort(400, 'Invalid content type');
        }

        if ($this->header('Content-Length', 0) > 10240) { // Max 10KB
            abort(413, 'Request too large');
        }

        // Sanitize input data
        $data = $this->json()->all();
        
        if (isset($data['csp-report']) && is_array($data['csp-report'])) {
            $data['csp-report'] = $this->sanitizeCspReport($data['csp-report']);
            $this->replace($data);
        }
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        // Log invalid CSP reports for security monitoring
        logger()->warning('Invalid CSP violation report', [
            'errors' => $validator->errors()->toArray(),
            'ip_hash' => hash('sha256', $this->ip() . config('app.key')),
            'user_agent_hash' => hash('sha256', $this->userAgent() . config('app.key')),
            'timestamp' => now()->toISOString(),
        ]);

        parent::failedValidation($validator);
    }

    /**
     * Check rate limiting for CSP reports.
     */
    private function checkRateLimit(): bool
    {
        $key = 'csp_reports:' . $this->ip();
        
        if (RateLimiter::tooManyAttempts($key, 50)) { // 50 per minute per IP
            logger()->warning('CSP report rate limit exceeded', [
                'ip_hash' => hash('sha256', $this->ip() . config('app.key')),
                'timestamp' => now()->toISOString(),
            ]);
            
            throw new ThrottleRequestsException('Too many CSP reports');
        }

        RateLimiter::hit($key, 60); // 1 minute window
        return true;
    }

    /**
     * Sanitize CSP report data.
     */
    private function sanitizeCspReport(array $report): array
    {
        $sanitized = [];
        
        // Define allowed fields and their sanitization rules
        $fieldRules = [
            'violated-directive' => 'directive',
            'blocked-uri' => 'uri',
            'document-uri' => 'uri',
            'referrer' => 'uri',
            'source-file' => 'uri',
            'line-number' => 'integer',
            'column-number' => 'integer',
            'original-policy' => 'policy',
            'effective-directive' => 'directive',
        ];

        foreach ($fieldRules as $field => $rule) {
            if (isset($report[$field])) {
                $sanitized[$field] = $this->sanitizeField($report[$field], $rule);
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize individual field based on type.
     */
    private function sanitizeField($value, string $type)
    {
        if ($value === null) {
            return null;
        }

        $value = (string) $value;

        // Remove control characters
        $value = preg_replace('/[\x00-\x1F\x7F]/', '', $value);

        switch ($type) {
            case 'uri':
                return $this->sanitizeUri($value);
            case 'directive':
                return $this->sanitizeDirective($value);
            case 'policy':
                return $this->sanitizePolicy($value);
            case 'integer':
                return $this->sanitizeInteger($value);
            default:
                return substr($value, 0, 255);
        }
    }

    /**
     * Sanitize URI values.
     */
    private function sanitizeUri(string $uri): ?string
    {
        // Remove potential XSS vectors
        $uri = preg_replace('/javascript:/i', '', $uri);
        $uri = preg_replace('/data:text\/html/i', '', $uri);
        $uri = preg_replace('/vbscript:/i', '', $uri);
        
        // Basic URL validation
        if (filter_var($uri, FILTER_VALIDATE_URL) === false && 
            !preg_match('/^(https?:\/\/|\/)/i', $uri)) {
            return null;
        }

        return substr($uri, 0, 2048);
    }

    /**
     * Sanitize directive values.
     */
    private function sanitizeDirective(string $directive): string
    {
        $validDirectives = [
            'default-src', 'script-src', 'style-src', 'img-src', 'font-src',
            'connect-src', 'frame-src', 'object-src', 'media-src', 'child-src',
            'frame-ancestors', 'base-uri', 'form-action', 'plugin-types',
            'sandbox', 'report-uri', 'report-to'
        ];

        $directive = strtolower(trim($directive));
        
        return in_array($directive, $validDirectives) ? $directive : 'unknown';
    }

    /**
     * Sanitize policy values.
     */
    private function sanitizePolicy(string $policy): string
    {
        // Remove potential injection vectors
        $policy = preg_replace('/[<>"\']/', '', $policy);
        
        return substr($policy, 0, 4096);
    }

    /**
     * Sanitize integer values.
     */
    private function sanitizeInteger($value): ?int
    {
        $int = filter_var($value, FILTER_VALIDATE_INT);
        
        return ($int !== false && $int >= 0 && $int <= 999999) ? $int : null;
    }
}