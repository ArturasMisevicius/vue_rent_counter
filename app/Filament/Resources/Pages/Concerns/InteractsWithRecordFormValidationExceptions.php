<?php

namespace App\Filament\Resources\Pages\Concerns;

use Illuminate\Validation\ValidationException;

trait InteractsWithRecordFormValidationExceptions
{
    protected function addRecordFormValidationErrors(ValidationException $exception): void
    {
        $this->resetErrorBag();

        foreach ($exception->errors() as $key => $messages) {
            foreach ($messages as $message) {
                $this->addError("data.{$key}", $message);
            }
        }
    }
}
