<?php

declare(strict_types=1);

namespace App\DTOs\Enhanced;

use App\Enums\InputMethod;
use App\Enums\ValidationStatus;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Meter Reading Validation DTO
 * 
 * Data transfer object for meter reading validation operations.
 * Encapsulates validation parameters and options.
 * 
 * @package App\DTOs\Enhanced
 */
final readonly class MeterReadingValidationDTO
{
    public function __construct(
        public int $readingId,
        public bool $autoUpdate = true,
        public bool $skipAuthorization = false,
        public ?array $validationOptions = null,
        public ?string $validationContext = null
    ) {
        $this->validate();
    }

    /**
     * Create DTO from HTTP request.
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            readingId: (int) $request->input('reading_id'),
            autoUpdate: $request->boolean('auto_update', true),
            skipAuthorization: $request->boolean('skip_authorization', false),
            validationOptions: $request->input('validation_options'),
            validationContext: $request->input('validation_context')
        );
    }

    /**
     * Create DTO from array.
     *
     * @param array $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        return new self(
            readingId: (int) $data['reading_id'],
            autoUpdate: $data['auto_update'] ?? true,
            skipAuthorization: $data['skip_authorization'] ?? false,
            validationOptions: $data['validation_options'] ?? null,
            validationContext: $data['validation_context'] ?? null
        );
    }

    /**
     * Convert to array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'reading_id' => $this->readingId,
            'auto_update' => $this->autoUpdate,
            'skip_authorization' => $this->skipAuthorization,
            'validation_options' => $this->validationOptions,
            'validation_context' => $this->validationContext,
        ];
    }

    /**
     * Validate DTO data.
     *
     * @throws \InvalidArgumentException If validation fails
     */
    private function validate(): void
    {
        if ($this->readingId <= 0) {
            throw new \InvalidArgumentException('Reading ID must be a positive integer');
        }

        if ($this->validationOptions && !is_array($this->validationOptions)) {
            throw new \InvalidArgumentException('Validation options must be an array');
        }

        if ($this->validationContext && strlen($this->validationContext) > 500) {
            throw new \InvalidArgumentException('Validation context is too long (max 500 characters)');
        }
    }
}