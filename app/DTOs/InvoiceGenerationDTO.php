<?php

declare(strict_types=1);

namespace App\DTOs;

use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * Invoice Generation DTO
 * 
 * Data transfer object for invoice generation.
 * Encapsulates all data needed to generate an invoice.
 * 
 * @package App\DTOs
 */
final readonly class InvoiceGenerationDTO
{
    public function __construct(
        public int $tenantId,
        public int $tenantRenterId,
        public Carbon $periodStart,
        public Carbon $periodEnd,
        public Carbon $dueDate,
        public ?array $meterReadings = null
    ) {}

    /**
     * Create DTO from HTTP request.
     *
     * @param Request $request
     * @return self
     */
    public static function fromRequest(Request $request): self
    {
        $periodStart = Carbon::parse($request->input('period_start'));
        $periodEnd = Carbon::parse($request->input('period_end'));
        
        // Calculate due date (default: 14 days after period end)
        $dueDays = config('billing.invoice.default_due_days', 14);
        $dueDate = $request->has('due_date')
            ? Carbon::parse($request->input('due_date'))
            : $periodEnd->copy()->addDays($dueDays);

        return new self(
            tenantId: (int) $request->input('tenant_id'),
            tenantRenterId: (int) $request->input('tenant_renter_id'),
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            dueDate: $dueDate,
            meterReadings: $request->input('meter_readings')
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
        $periodStart = $data['period_start'] instanceof Carbon
            ? $data['period_start']
            : Carbon::parse($data['period_start']);

        $periodEnd = $data['period_end'] instanceof Carbon
            ? $data['period_end']
            : Carbon::parse($data['period_end']);

        $dueDate = isset($data['due_date'])
            ? ($data['due_date'] instanceof Carbon ? $data['due_date'] : Carbon::parse($data['due_date']))
            : $periodEnd->copy()->addDays(config('billing.invoice.default_due_days', 14));

        return new self(
            tenantId: (int) $data['tenant_id'],
            tenantRenterId: (int) $data['tenant_renter_id'],
            periodStart: $periodStart,
            periodEnd: $periodEnd,
            dueDate: $dueDate,
            meterReadings: $data['meter_readings'] ?? null
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
            'tenant_id' => $this->tenantId,
            'tenant_renter_id' => $this->tenantRenterId,
            'period_start' => $this->periodStart->toDateString(),
            'period_end' => $this->periodEnd->toDateString(),
            'due_date' => $this->dueDate->toDateString(),
            'meter_readings' => $this->meterReadings,
        ];
    }
}
