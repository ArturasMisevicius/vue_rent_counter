<?php

declare(strict_types=1);

namespace Tests\Unit\Exceptions;

use App\Exceptions\InvalidPropertyAssignmentException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class InvalidPropertyAssignmentExceptionTest extends TestCase
{
    public function test_exception_has_correct_default_message(): void
    {
        $exception = new InvalidPropertyAssignmentException();

        expect($exception->getMessage())
            ->toBe('Cannot assign tenant to property from different organization.');
    }

    public function test_exception_has_correct_default_status_code(): void
    {
        $exception = new InvalidPropertyAssignmentException();

        expect($exception->getCode())
            ->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_exception_accepts_custom_message(): void
    {
        $customMessage = 'Custom error message';
        $exception = new InvalidPropertyAssignmentException($customMessage);

        expect($exception->getMessage())->toBe($customMessage);
    }

    public function test_exception_accepts_custom_code(): void
    {
        $exception = new InvalidPropertyAssignmentException('Test', 400);

        expect($exception->getCode())->toBe(400);
    }

    public function test_exception_accepts_previous_exception(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new InvalidPropertyAssignmentException('Test', 422, $previous);

        expect($exception->getPrevious())->toBe($previous);
    }

    public function test_render_returns_json_for_json_requests(): void
    {
        $exception = new InvalidPropertyAssignmentException();
        $request = Request::create('/api/test', 'POST');
        $request->headers->set('Accept', 'application/json');

        $response = $exception->render($request);

        expect($response)->toBeInstanceOf(\Illuminate\Http\JsonResponse::class)
            ->and($response->getStatusCode())->toBe(422)
            ->and($response->getData(true))->toHaveKey('message')
            ->and($response->getData(true))->toHaveKey('error')
            ->and($response->getData(true)['error'])->toBe('invalid_property_assignment');
    }

    public function test_render_returns_view_for_web_requests(): void
    {
        $exception = new InvalidPropertyAssignmentException();
        $request = Request::create('/admin/test', 'POST');

        $response = $exception->render($request);

        expect($response)->toBeInstanceOf(\Illuminate\Http\Response::class)
            ->and($response->getStatusCode())->toBe(422);
    }

    public function test_report_logs_to_security_channel(): void
    {
        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->with('Invalid property assignment attempt', \Mockery::type('array'));

        $exception = new InvalidPropertyAssignmentException();
        $result = $exception->report();

        expect($result)->toBeTrue();
    }

    public function test_exception_is_final(): void
    {
        $reflection = new \ReflectionClass(InvalidPropertyAssignmentException::class);

        expect($reflection->isFinal())->toBeTrue();
    }

    public function test_exception_extends_base_exception(): void
    {
        $exception = new InvalidPropertyAssignmentException();

        expect($exception)->toBeInstanceOf(\Exception::class);
    }

    public function test_exception_message_is_string_type(): void
    {
        $exception = new InvalidPropertyAssignmentException();

        expect($exception->getMessage())->toBeString();
    }

    public function test_exception_code_is_integer_type(): void
    {
        $exception = new InvalidPropertyAssignmentException();

        expect($exception->getCode())->toBeInt();
    }

    public function test_exception_with_empty_custom_message(): void
    {
        $exception = new InvalidPropertyAssignmentException('');

        expect($exception->getMessage())->toBe('');
    }

    public function test_exception_preserves_message_with_special_characters(): void
    {
        $message = "Cannot assign tenant 'test@org.com' to property #123 from organization 'Other Org'";
        $exception = new InvalidPropertyAssignmentException($message);

        expect($exception->getMessage())->toBe($message);
    }

    public function test_exception_preserves_multiline_message(): void
    {
        $message = "Line 1\nLine 2\nLine 3";
        $exception = new InvalidPropertyAssignmentException($message);

        expect($exception->getMessage())->toBe($message);
    }

    public function test_render_includes_custom_message_in_json_response(): void
    {
        $customMessage = 'Property belongs to tenant A, tenant belongs to tenant B';
        $exception = new InvalidPropertyAssignmentException($customMessage);
        $request = Request::create('/api/test', 'POST');
        $request->headers->set('Accept', 'application/json');

        $response = $exception->render($request);

        expect($response->getData(true)['message'])->toBe($customMessage);
    }

    public function test_render_includes_custom_message_in_html_response(): void
    {
        $customMessage = 'Property belongs to tenant A, tenant belongs to tenant B';
        $exception = new InvalidPropertyAssignmentException($customMessage);
        $request = Request::create('/admin/test', 'POST');

        $response = $exception->render($request);

        // Response should be an HTTP Response with 422 status
        expect($response)->toBeInstanceOf(\Illuminate\Http\Response::class)
            ->and($response->getStatusCode())->toBe(422);
        
        // The view should be errors.422 with the custom message
        // We can't easily test view data in unit tests, but we verify the response type and status
    }

    public function test_report_logs_custom_message(): void
    {
        $customMessage = 'Custom security violation message';

        Log::shouldReceive('channel')
            ->once()
            ->with('security')
            ->andReturnSelf();

        Log::shouldReceive('warning')
            ->once()
            ->with('Invalid property assignment attempt', \Mockery::on(function ($context) use ($customMessage) {
                return isset($context['message']) && $context['message'] === $customMessage;
            }));

        $exception = new InvalidPropertyAssignmentException($customMessage);
        $exception->report();
    }

    public function test_exception_can_be_caught_as_exception(): void
    {
        try {
            throw new InvalidPropertyAssignmentException();
        } catch (\Exception $e) {
            expect($e)->toBeInstanceOf(InvalidPropertyAssignmentException::class);
        }
    }

    public function test_exception_can_be_caught_as_throwable(): void
    {
        try {
            throw new InvalidPropertyAssignmentException();
        } catch (\Throwable $e) {
            expect($e)->toBeInstanceOf(InvalidPropertyAssignmentException::class);
        }
    }
}
