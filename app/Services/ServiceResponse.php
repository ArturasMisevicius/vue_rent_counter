<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service Response DTO
 * 
 * Standardized response object for all service layer operations.
 * Provides consistent structure for success/error responses.
 *
 * @package App\Services
 */
final readonly class ServiceResponse
{
    public function __construct(
        public bool $success,
        public mixed $data = null,
        public string $message = '',
        public int $code = 0,
        public array $metadata = [],
    ) {}

    /**
     * Check if the response indicates success.
     *
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Check if the response indicates failure.
     *
     * @return bool
     */
    public function isFailure(): bool
    {
        return !$this->success;
    }

    /**
     * Get the response data or throw if failed.
     *
     * @return mixed
     * @throws \RuntimeException If response is a failure
     */
    public function getDataOrFail(): mixed
    {
        if ($this->isFailure()) {
            throw new \RuntimeException($this->message, $this->code);
        }

        return $this->data;
    }

    /**
     * Convert to array representation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'data' => $this->data,
            'message' => $this->message,
            'code' => $this->code,
            'metadata' => $this->metadata,
        ];
    }
}
