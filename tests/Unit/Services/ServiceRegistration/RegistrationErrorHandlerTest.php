<?php

declare(strict_types=1);

namespace Tests\Unit\Services\ServiceRegistration;

use App\Services\ServiceRegistration\RegistrationErrorHandler;
use App\ValueObjects\ServiceRegistration\RegistrationResult;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

final class RegistrationErrorHandlerTest extends TestCase
{
    private RegistrationErrorHandler $handler;
    private Application $app;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->app = Mockery::mock(Application::class);
        $this->handler = new RegistrationErrorHandler($this->app);
    }

    public function test_handles_successful_registration(): void
    {
        $operation = fn() => ['registered' => 5, 'skipped' => 0, 'errors' => []];
        
        $result = $this->handler->handleRegistration($operation, 'test_context');
        
        $this->assertInstanceOf(RegistrationResult::class, $result);
        $this->assertEquals(5, $result->registered);
        $this->assertEquals(0, $result->skipped);
        $this->assertEquals([], $result->errors);
        $this->assertGreaterThan(0, $result->durationMs);
    }

    public function test_handles_authorization_exception(): void
    {
        Log::shouldReceive('debug')
            ->once()
            ->with('Registration skipped due to authorization', Mockery::type('array'));

        $operation = fn() => throw new AuthorizationException('Unauthorized');
        
        $result = $this->handler->handleRegistration($operation, 'test_context');
        
        $this->assertEquals(0, $result->registered);
        $this->assertEquals(0, $result->skipped);
        $this->assertEquals([], $result->errors);
    }

    public function test_handles_general_exception(): void
    {
        Log::shouldReceive('error')
            ->once()
            ->with('Registration operation failed', Mockery::type('array'));

        $operation = fn() => throw new \RuntimeException('Test error');
        
        $result = $this->handler->handleRegistration($operation, 'test_context');
        
        $this->assertEquals(0, $result->registered);
        $this->assertEquals(0, $result->skipped);
        $this->assertEquals(['system' => 'registration_failed'], $result->errors);
    }

    public function test_logs_results_in_development(): void
    {
        $this->app->shouldReceive('environment')
            ->with('local', 'testing')
            ->andReturn(true);

        Log::shouldReceive('info')
            ->once()
            ->with('Registration completed', Mockery::type('array'));

        $result = new RegistrationResult(5, 0, [], 100.0);
        
        $this->handler->logResults($result, 'test_context');
    }

    public function test_logs_production_warnings_for_errors(): void
    {
        $this->app->shouldReceive('environment')
            ->with('local', 'testing')
            ->andReturn(false);
        
        $this->app->shouldReceive('environment')
            ->with('production')
            ->andReturn(true);

        Log::shouldReceive('warning')
            ->once()
            ->with('Registration issues detected', Mockery::type('array'));

        $result = new RegistrationResult(3, 2, ['error1' => 'message'], 100.0);
        
        $this->handler->logResults($result, 'test_context');
    }

    public function test_handles_critical_failure_in_development(): void
    {
        $this->app->shouldReceive('environment')
            ->with('local', 'testing')
            ->twice()
            ->andReturn(true);

        Log::shouldReceive('critical')
            ->once()
            ->with('Critical failure in service registration', Mockery::type('array'));

        $exception = new \RuntimeException('Critical error');
        
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Critical error');
        
        $this->handler->handleCriticalFailure($exception, 'test_context');
    }

    public function test_handles_critical_failure_in_production(): void
    {
        $this->app->shouldReceive('environment')
            ->with('local', 'testing')
            ->andReturn(false);

        Log::shouldReceive('critical')
            ->once()
            ->with('Critical failure in service registration', Mockery::type('array'));

        $exception = new \RuntimeException('Critical error');
        
        // Should not throw in production
        $this->handler->handleCriticalFailure($exception, 'test_context');
        
        $this->assertTrue(true); // Test passes if no exception is thrown
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}