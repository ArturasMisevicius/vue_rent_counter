<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Enums\InputMethod;
use App\Enums\ValidationStatus;
use App\Models\MeterReading;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;
use App\Services\MeterReadingService;

/**
 * Validates data quality leveraging existing meter reading audit trail.
 */
final class DataQualityValidator extends AbstractValidator
{
    public function __construct(
        \Illuminate\Contracts\Cache\Repository $cache,
        \Illuminate\Contracts\Config\Repository $config,
        \Psr\Log\LoggerInterface $logger,
        private readonly MeterReadingService $meterReadingService,
    ) {
        parent::__construct($cache, $config, $logger);
    }

    public function getName(): string
    {
        return 'data_quality';
    }

    public function validate(ValidationContext $context): ValidationResult
    {
        try {
            $errors = [];
            $warnings = [];
            $recommendations = [];

            // 1. Duplicate reading detection
            $duplicateCheck = $this->checkForDuplicateReadings($context);
            if (!empty($duplicateCheck['duplicates'])) {
                $errors[] = 'Duplicate reading detected for the same date and meter';
            }

            // 2. Reading sequence validation
            if ($context->hasPreviousReading()) {
                $sequenceValidation = $this->validateReadingSequence($context);
                $errors = array_merge($errors, $sequenceValidation['errors']);
                $warnings = array_merge($warnings, $sequenceValidation['warnings']);
            }

            // 3. Audit trail validation
            if ($context->reading->exists) {
                $auditValidation = $this->validateAuditTrail($context);
                $warnings = array_merge($warnings, $auditValidation['warnings']);
            }

            // 4. Photo validation for OCR readings
            if ($context->reading->input_method === InputMethod::PHOTO_OCR) {
                $photoValidation = $this->validatePhotoReading($context);
                $errors = array_merge($errors, $photoValidation['errors']);
                $recommendations = array_merge($recommendations, $photoValidation['recommendations']);
            }

            $metadata = [
                'rules_applied' => ['duplicate_detection', 'sequence_validation', 'audit_trail', 'photo_validation'],
                'duplicate_check' => $duplicateCheck,
            ];

            if (empty($errors)) {
                return ValidationResult::valid($warnings, $recommendations, $metadata);
            }

            return ValidationResult::invalid($errors, $warnings, $recommendations, $metadata);

        } catch (\Exception $e) {
            return $this->handleException($e, $context);
        }
    }

    private function checkForDuplicateReadings(ValidationContext $context): array
    {
        $duplicates = MeterReading::where('meter_id', $context->reading->meter_id)
            ->where('reading_date', $context->reading->reading_date)
            ->when($context->reading->exists, fn($q) => $q->where('id', '!=', $context->reading->id))
            ->get();

        return ['duplicates' => $duplicates->toArray()];
    }

    private function validateReadingSequence(ValidationContext $context): array
    {
        $errors = [];
        $warnings = [];

        $previousReading = $context->previousReading;
        
        if ($previousReading && $context->reading->value < $previousReading->value) {
            // Check if this might be a meter rollover
            $maxMeterValue = $this->getMaxMeterValue($context->reading->meter);
            $possibleRollover = ($previousReading->value > ($maxMeterValue * 0.9)) && 
                               ($context->reading->value < ($maxMeterValue * 0.1));
            
            if (!$possibleRollover) {
                $errors[] = "Reading value is less than previous reading (possible meter rollback)";
            } else {
                $warnings[] = "Possible meter rollover detected";
            }
        }

        return ['errors' => $errors, 'warnings' => $warnings];
    }

    private function validateAuditTrail(ValidationContext $context): array
    {
        $warnings = [];

        $auditCount = $context->reading->auditTrail()->count();
        
        if ($auditCount === 0 && $context->reading->wasChanged()) {
            $warnings[] = "Reading has been modified but no audit trail exists";
        }

        return ['warnings' => $warnings];
    }

    private function validatePhotoReading(ValidationContext $context): array
    {
        $errors = [];
        $recommendations = [];

        if (empty($context->reading->photo_path)) {
            $errors[] = "Photo path is required for OCR readings";
        } elseif (!file_exists(storage_path('app/public/' . $context->reading->photo_path))) {
            $errors[] = "Photo file not found at specified path";
        }

        if ($context->reading->validation_status === ValidationStatus::PENDING) {
            $recommendations[] = "OCR reading requires manual validation for accuracy";
        }

        return ['errors' => $errors, 'recommendations' => $recommendations];
    }

    private function getMaxMeterValue($meter): float
    {
        // Default meter maximum value - could be configured per meter type
        return 999999.99;
    }
}