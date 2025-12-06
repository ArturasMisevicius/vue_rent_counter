<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Meter Reading DTO
 * 
 * Data transfer object for meter reading creation/update.
 * Provides type safety and validation for meter reading data.
 * 
 * @package App\DTOs
 */
final readonly class MeterReadingDTO
{
    public function __construct(
        public int $meterId,
        public float $value,
        public Carbon $readingDate,
        public ?string $zone = null,
        public ?int $enteredByUserId = null,
        public ?string $notes = null
    ) {}

    /**
     * Create DTO from HTTP request.
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        return new self(
            meterId: (int) $request->input('meter_id'),
            value: (float) $request->input('value'),
            readingDate: Carbon::parse($request->input('reading_date')),
            zone: $request->input('zone'),
            enteredByUserId: $request->has('entered_by_user_id') 
                ? (int) $request->input('entered_by_user_id') 
                : auth()->id(),
            notes: $request->input('notes')
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
            meterId: (int) $data['meter_id'],
            value: (float) $data['value'],
            readingDate: $data['reading_date'] instanceof Carbon 
                ? $data['reading_date'] 
                : Carbon::parse($data['reading_date']),
            zone: $data['zone'] ?? null,
            enteredByUserId: $data['entered_by_user_id'] ?? auth()->id(),
            notes: $data['notes'] ?? null
        );
    }

    /**
     * Convert to array for model creation.
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'meter_id' => $this->meterId,
            'value' => $this->value,
            'reading_date' => $this->readingDate->toDateString(),
            'zone' => $this->zone,
            'entered_by_user_id' => $this->enteredByUserId,
            'notes' => $this->notes,
        ];
    }
}
