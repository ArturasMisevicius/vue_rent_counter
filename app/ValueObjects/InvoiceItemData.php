<?php

namespace App\ValueObjects;

use App\Models\Meter;
use App\Models\MeterReading;
use App\Models\Tariff;

/**
 * Value object for invoice item data.
 * 
 * Encapsulates all data needed to create an invoice item,
 * reducing parameter count in service methods.
 */
readonly class InvoiceItemData
{
    public function __construct(
        public Meter $meter,
        public float $consumption,
        public float $unitPrice,
        public float $total,
        public MeterReading $startReading,
        public MeterReading $endReading,
        public Tariff $tariff,
        public ?string $zone = null,
    ) {}

    /**
     * Get the description for this invoice item.
     */
    public function getDescription(): string
    {
        $description = match ($this->meter->type->value) {
            'electricity' => 'Electricity',
            'water_cold' => 'Cold Water',
            'water_hot' => 'Hot Water',
            'heating' => 'Heating',
            default => 'Utility',
        };

        if ($this->zone) {
            $description .= " ({$this->zone})";
        }

        return $description;
    }

    /**
     * Get the unit for this invoice item.
     */
    public function getUnit(): string
    {
        return match ($this->meter->type->value) {
            'electricity', 'heating' => 'kWh',
            'water_cold', 'water_hot' => 'm³',
            default => 'unit',
        };
    }

    /**
     * Get the meter reading snapshot data.
     */
    public function getSnapshot(): array
    {
        return [
            'meter_id' => $this->meter->id,
            'meter_serial' => $this->meter->serial_number,
            'start_reading_id' => $this->startReading->id,
            'start_value' => $this->startReading->value,
            'start_date' => $this->startReading->reading_date->toDateString(),
            'end_reading_id' => $this->endReading->id,
            'end_value' => $this->endReading->value,
            'end_date' => $this->endReading->reading_date->toDateString(),
            'zone' => $this->zone,
            'tariff_id' => $this->tariff->id,
            'tariff_name' => $this->tariff->name,
            'tariff_configuration' => $this->tariff->configuration,
        ];
    }

    /**
     * Convert to array for invoice item creation.
     */
    public function toArray(int $invoiceId): array
    {
        return [
            'invoice_id' => $invoiceId,
            'description' => $this->getDescription(),
            'quantity' => $this->consumption,
            'unit' => $this->getUnit(),
            'unit_price' => $this->unitPrice,
            'total' => $this->total,
            'meter_reading_snapshot' => $this->getSnapshot(),
        ];
    }
}
