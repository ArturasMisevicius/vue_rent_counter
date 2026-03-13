<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Contracts\RepositoryInterface;
use App\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

/**
 * Simple Repository Pattern Tests
 * 
 * Basic tests to verify the repository pattern implementation
 * without requiring complex database setup.
 */
class SimpleRepositoryTest extends TestCase
{
    /** @test */
    public function repository_interface_defines_correct_methods(): void
    {
        $reflection = new \ReflectionClass(RepositoryInterface::class);
        $methods = $reflection->getMethods();
        $methodNames = array_map(fn($method) => $method->getName(), $methods);

        $expectedMethods = [
            'find',
            'findOrFail',
            'findBy',
            'findWhere',
            'all',
            'paginate',
            'create',
            'update',
            'delete',
            'with',
            'orderBy',
            'chunk',
            'count',
            'exists',
            'firstOrCreate',
            'updateOrCreate',
            'where',
            'whereIn',
            'whereNotIn',
            'whereBetween',
            'whereNull',
            'whereNotNull',
            'limit',
            'offset',
            'fresh',
            'get',
            'first',
            'getModel',
            'setModel',
        ];

        foreach ($expectedMethods as $method) {
            $this->assertContains($method, $methodNames, "Repository interface should have {$method} method");
        }
    }

    /** @test */
    public function base_repository_implements_repository_interface(): void
    {
        $this->assertTrue(
            is_subclass_of(BaseRepository::class, RepositoryInterface::class),
            'BaseRepository should implement RepositoryInterface'
        );
    }

    /** @test */
    public function repository_can_be_instantiated_with_model(): void
    {
        $model = new class extends Model {
            protected $table = 'test_models';
            protected $fillable = ['name'];
        };

        $repository = new class($model) extends BaseRepository {
            // Anonymous class for testing
        };

        $this->assertInstanceOf(RepositoryInterface::class, $repository);
        $this->assertSame($model, $repository->getModel());
    }

    /** @test */
    public function repository_can_set_new_model(): void
    {
        $model1 = new class extends Model {
            protected $table = 'test_models_1';
        };

        $model2 = new class extends Model {
            protected $table = 'test_models_2';
        };

        $repository = new class($model1) extends BaseRepository {
            // Anonymous class for testing
        };

        $this->assertSame($model1, $repository->getModel());

        $newRepository = $repository->setModel($model2);
        $this->assertSame($model2, $newRepository->getModel());
    }

    /** @test */
    public function repository_service_provider_is_registered(): void
    {
        $providers = app()->getLoadedProviders();
        
        $this->assertArrayHasKey(
            'App\\Providers\\RepositoryServiceProvider',
            $providers,
            'RepositoryServiceProvider should be registered'
        );
    }

    /** @test */
    public function repository_exception_can_be_created(): void
    {
        $exception = new \App\Exceptions\RepositoryException('Test message');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('Test message', $exception->getMessage());
    }

    /** @test */
    public function repository_exception_has_static_factory_methods(): void
    {
        $modelNotFoundException = \App\Exceptions\RepositoryException::modelNotFound('User', 123);
        $this->assertStringContainsString('User', $modelNotFoundException->getMessage());
        $this->assertStringContainsString('123', $modelNotFoundException->getMessage());

        $invalidCriteriaException = \App\Exceptions\RepositoryException::invalidCriteria(['invalid' => 'criteria']);
        $this->assertStringContainsString('criteria', $invalidCriteriaException->getMessage());

        $bulkOperationException = \App\Exceptions\RepositoryException::bulkOperationFailed('create', 5);
        $this->assertStringContainsString('create', $bulkOperationException->getMessage());
        $this->assertStringContainsString('5', $bulkOperationException->getMessage());
    }
}