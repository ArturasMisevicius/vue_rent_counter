<?php

declare(strict_types=1);

namespace App\Actions\Enhanced;

use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Services\ServiceValidationEngine;
use Illuminate\Support\Facades\Log;

/**
 * Validate Meter Reading Action
 * 
 * Single responsibility: Validate a single meter reading.
 * Applies all validation rules and updates reading status.
 * 
 * @package App\Actions\Enhanced
 */
final class ValidateMeterReadingAction
{
    public function __construct(
        private readonly ServiceValidationEngine $validationEngine
    ) {}

    /**
     * Execute the meter reading validation action.
     *
     * @param MeterReading $reading The reading to validate
     * @param bool $autoUpdate Whether to automatically update the reading status
     * @return array Validation results
     */
    public function execute(MeterReading $reading, bool $autoUpdate = true): array
    {
        // Perform validation
        $validationResult = $this->validationEngine->validateMeterReading($reading);

        // Update reading status if auto-update is enabled
        if ($autoUpdate) {
            $this->updateReadingStatus($reading, $validationResult);
        }

        // Log validation result
        Log::info('Meter reading validation completed', [
            'reading_id' => $reading->id,
            'meter_id' => $reading->meter_id,
            'is_valid' => $validationResult['is_valid'],
            'error_count' => count($validationResult['errors'] ?? []),
            'warning_count' => count($validationResult['warnings'] ?? []),
            'validated_by' => auth()->id(),
        ]);

        return $validationResult;
    }

    /**
     * Update reading validation status based on results.
     */
    private function updateReadingStatus(MeterReading $reading, array $validationResult): void
    {
        $newStatus = $this->determineValidationStatus($validationResult);
        
        if ($reading->validation_status !== $newStatus) {
            $reading->update([
                'validation_status' => $newStatus,
                'validated_by' => auth()->id(),
                'validated_at' => now(),
                'validation_notes' => $this->generateValidationNotes($validationResult),
            ]);
        }
    }

    /**
     * Determine validation status from results.
     */
    private function determineValidationStatus(array $validationResult): ValidationStatus
    {
        if (!$validationResult['is_valid']) {
            return ValidationStatus::REJECTED;
        }

        $warningCount = count($validationResult['warnings'] ?? []);
        
        if ($warningCount > 0) {
            return ValidationStatus::REQUIRES_REVIEW;
        }

        return ValidationStatus::VALIDATED;
    }

    /**
     * Generate validation notes from results.
     */
    private function generateValidationNotes(array $validationResult): ?string
    {
        $notes = [];

        if (!empty($validationResult['errors'])) {
            $notes[] = 'Errors: ' . implode('; ', $validationResult['errors']);
        }

        if (!empty($validationResult['warnings'])) {
            $notes[] = 'Warnings: ' . implode('; ', $validationResult['warnings']);
        }

        return empty($notes) ? null : implode(' | ', $notes);
    }
}