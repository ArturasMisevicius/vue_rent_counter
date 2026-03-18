<?php

declare(strict_types=1);

namespace App\Http\Requests\Security;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

final class CspViolationRequest extends FormRequest
{
    use InteractsWithValidationPayload;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'csp-report' => ['sometimes', 'array'],
            'csp-report.document-uri' => ['nullable', 'string'],
            'csp-report.violated-directive' => ['nullable', 'string'],
            'csp-report.effective-directive' => ['nullable', 'string'],
            'csp-report.blocked-uri' => ['nullable', 'string'],
            'csp-report.original-policy' => ['nullable', 'string'],
            'csp-report.referrer' => ['nullable', 'string'],
            'csp-report.disposition' => ['nullable', 'string'],
            'csp-report.sample' => ['nullable', 'string'],
            'body' => ['sometimes', 'array'],
            'body.documentURL' => ['nullable', 'string'],
            'body.effectiveDirective' => ['nullable', 'string'],
            'body.blockedURL' => ['nullable', 'string'],
            'body.originalPolicy' => ['nullable', 'string'],
            'body.referrer' => ['nullable', 'string'],
            'body.disposition' => ['nullable', 'string'],
            'body.sample' => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [];
    }

    protected function prepareForValidation(): void
    {
        $this->trimStrings([
            'csp-report.document-uri',
            'csp-report.violated-directive',
            'csp-report.effective-directive',
            'csp-report.blocked-uri',
            'csp-report.original-policy',
            'csp-report.referrer',
            'csp-report.disposition',
            'csp-report.sample',
            'body.documentURL',
            'body.effectiveDirective',
            'body.blockedURL',
            'body.originalPolicy',
            'body.referrer',
            'body.disposition',
            'body.sample',
        ]);

        $this->emptyStringsToNull([
            'csp-report.document-uri',
            'csp-report.violated-directive',
            'csp-report.effective-directive',
            'csp-report.blocked-uri',
            'csp-report.original-policy',
            'csp-report.referrer',
            'csp-report.disposition',
            'csp-report.sample',
            'body.documentURL',
            'body.effectiveDirective',
            'body.blockedURL',
            'body.originalPolicy',
            'body.referrer',
            'body.disposition',
            'body.sample',
        ]);
    }

    /**
     * @return array{
     *     url: string|null,
     *     violated_directive: string|null,
     *     effective_directive: string|null,
     *     blocked_uri: string|null,
     *     original_policy: string|null,
     *     referrer: string|null,
     *     disposition: string|null,
     *     sample: string|null
     * }
     */
    public function reportData(): array
    {
        /** @var array<string, mixed> $validated */
        $validated = $this->validated();

        /** @var array<string, mixed> $payload */
        $payload = $validated['csp-report'] ?? $validated['body'] ?? [];

        return [
            'url' => $this->stringValue($payload['document-uri'] ?? $payload['documentURL'] ?? null),
            'violated_directive' => $this->stringValue($payload['violated-directive'] ?? null),
            'effective_directive' => $this->stringValue($payload['effective-directive'] ?? $payload['effectiveDirective'] ?? null),
            'blocked_uri' => $this->stringValue($payload['blocked-uri'] ?? $payload['blockedURL'] ?? null),
            'original_policy' => $this->stringValue($payload['original-policy'] ?? $payload['originalPolicy'] ?? null),
            'referrer' => $this->stringValue($payload['referrer'] ?? null),
            'disposition' => $this->stringValue($payload['disposition'] ?? null),
            'sample' => $this->stringValue($payload['sample'] ?? null),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function validationData(): array
    {
        $data = parent::validationData();

        if ($data !== [] || $this->getContent() === '') {
            return $data;
        }

        $decoded = json_decode($this->getContent(), true);

        if (! is_array($decoded)) {
            return $data;
        }

        if (array_is_list($decoded)) {
            $decoded = is_array($decoded[0] ?? null) ? $decoded[0] : [];
        }

        return is_array($decoded) ? $decoded : $data;
    }

    private function stringValue(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $string = trim((string) $value);

        return $string === '' ? null : $string;
    }
}
