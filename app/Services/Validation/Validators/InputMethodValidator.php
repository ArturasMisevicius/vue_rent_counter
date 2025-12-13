<?php

declare(strict_types=1);

namespace App\Services\Validation\Validators;

use App\Enums\InputMethod;
use App\Enums\ValidationStatus;
use App\Services\Validation\ValidationContext;
use App\Services\Validation\ValidationResult;

/**
 * Validates input method specific requirements.
 */
final class InputMethodValidator extends AbstractValidator
{
    public function getName(): string
    {
        return 'input_method';
    }

    public function validate(ValidationContext $context): ValidationResult
    {
        try {
            $errors = [];
            $warnings = [];
            $recommendations = [];

            $inputMethod = $context->reading->input_method;

            // Photo OCR specific validation
            if ($inputMethod === InputMethod::PHOTO_OCR) {
                $ocrValidation = $this->validatePhotoOCR($context);
                $errors = array_merge($errors, $ocrValidation['errors']);
                $recommendations = array_merge($recommendations, $ocrValidation['recommendations']);
            }

            // Estimated reading validation
            if ($inputMethod === InputMethod::ESTIMATED) {
                $estimatedValidation = $this->validateEstimatedReading($context);
                $warnings = array_merge($warnings, $estimatedValidation['warnings']);
                $recommendations = array_merge($recommendations, $estimatedValidation['recommendations']);
            }

            // API integration validation
            if ($inputMethod === InputMethod::API_INTEGRATION) {
                $apiValidation = $this->validateAPIIntegration($context);
                $warnings = array_merge($warnings, $apiValidation['warnings']);
            }

            // CSV import validation
            if ($inputMethod === InputMethod::CSV_IMPORT) {
                $csvValidation = $this->validateCSVImport($context);
                $recommendations = array_merge($recommendations, $csvValidation['recommendations']);
            }

            $metadata = [
                'rules_applied' => ['input_method_validation'],
                'input_method' => $inputMethod->value,
            ];

            if (empty($errors)) {
                return ValidationResult::valid($warnings, $recommendations, $metadata);
            }

            return ValidationResult::invalid($errors, $warnings, $recommendations, $metadata);

        } catch (\Exception $e) {
            return $this->handleException($e, $context);
        }
    }

    private function validatePhotoOCR(ValidationContext $context): array
    {
        $errors = [];
        $recommendations = [];

        if (empty($context->reading->photo_path)) {
            $errors[] = 'Photo path required for OCR readings';
        }
        
        if ($context->reading->validation_status === ValidationStatus::PENDING) {
            $recommendations[] = 'OCR reading requires manual validation';
        }

        return ['errors' => $errors, 'recommendations' => $recommendations];
    }

    private function validateEstimatedReading(ValidationContext $context): array
    {
        $warnings = [];
        $recommendations = [];

        if ($context->reading->validation_status !== ValidationStatus::REQUIRES_REVIEW) {
            $warnings[] = 'Estimated readings should be marked for review';
        }
        
        $recommendations[] = 'Replace estimated reading with actual reading when available';

        return ['warnings' => $warnings, 'recommendations' => $recommendations];
    }

    private function validateAPIIntegration(ValidationContext $context): array
    {
        $warnings = [];

        if (empty($context->reading->entered_by)) {
            $warnings[] = 'API integration reading missing source identification';
        }

        return ['warnings' => $warnings];
    }

    private function validateCSVImport(ValidationContext $context): array
    {
        $recommendations = [];

        if ($context->reading->validation_status === ValidationStatus::PENDING) {
            $recommendations[] = 'Batch imported reading should be validated';
        }

        return ['recommendations' => $recommendations];
    }
}