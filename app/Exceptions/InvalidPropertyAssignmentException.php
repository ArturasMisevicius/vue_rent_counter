<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exception thrown when attempting to assign a tenant to a property
 * from a different organization (tenant_id mismatch).
 *
 * This exception enforces multi-tenancy boundaries and prevents
 * cross-tenant data access violations.
 *
 * Requirements: 5.3, 6.1, 7.1
 */
final class InvalidPropertyAssignmentException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param  string  $message  The exception message
     * @param  int  $code  The exception code (default: 422)
     * @param  \Throwable|null  $previous  Previous exception for chaining
     */
    public function __construct(
        string $message = 'Cannot assign tenant to property from different organization.',
        int $code = Response::HTTP_UNPROCESSABLE_ENTITY,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Render the exception as an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function render(Request $request): JsonResponse|Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $this->getMessage(),
                'error' => 'invalid_property_assignment',
            ], $this->getCode());
        }

        return response()->view('errors.422', [
            'message' => $this->getMessage(),
            'exception' => $this,
        ], $this->getCode());
    }

    /**
     * Report the exception.
     *
     * Log security-relevant property assignment violations for audit purposes.
     *
     * @return bool
     */
    public function report(): bool
    {
        // Log to security channel for audit trail
        Log::channel('security')->warning('Invalid property assignment attempt', [
            'message' => $this->getMessage(),
            'trace' => $this->getTraceAsString(),
        ]);

        return true;
    }
}
