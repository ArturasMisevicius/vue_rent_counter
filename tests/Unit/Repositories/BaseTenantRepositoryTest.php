<?php

namespace Tests\Unit\Repositories;

use App\Contracts\TenantContextInterface;
use App\Repositories\BaseTenantRepository;
use App\ValueObjects\TenantId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class BaseTenantRepositoryTest extends TestCase
{
    private $mockModel;
    private $mockTenantContext;
    private $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockModel = Mockery::mock(Model::class);
        $this->mockTenantContext = Mockery::mock(TenantContextInterface::class);
        
        $this->repository = new class($this->mockModel, $this->mockTenantContext) extends BaseTenantRepository {
            // Concrete implementation for testing
        };
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_can_set_and_get_tenant_context(): void
    {
        $tenantId = new TenantId('test-tenant');
        
        $this->repository->setTenantContext($tenantId);
        
        $this->assertEquals($tenantId, $this->repository->getTenantContext());
    }

    public function test_falls_back_to_global_tenant_context(): void
    {
        $tenantId = new TenantId('global-tenant');
        
        $this->mockTenantContext->shouldReceive('getCurrentTenant')
            ->once()
            ->andReturn($tenantId);
        
        $this->assertEquals($tenantId, $this->repository->getTenantContext());
    }

    public function test_can_clear_tenant_context(): void
    {
        $tenantId = new TenantId('test-tenant');
        
        $this->repository->setTenantContext($tenantId);
        $this->assertEquals($tenantId, $this->repository->getTenantContext());
        
        $this->repository->clearTenantContext();
        
        $this->mockTenantContext->shouldReceive('getCurrentTenant')
            ->once()
            ->andReturn(null);
        
        $this->assertNull($this->repository->getTenantContext());
    }

    public function test_find_by_id_uses_tenant_scoped_query(): void
    {
        $tenantId = new TenantId('test-tenant');
        $mockQuery = Mockery::mock(Builder::class);
        $mockModel = Mockery::mock(Model::class);
        
        $this->repository->setTenantContext($tenantId);
        
        $this->mockModel->shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('find')
            ->with(1)
            ->once()
            ->andReturn($mockModel);
        
        $result = $this->repository->findById(1);
        
        $this->assertEquals($mockModel, $result);
    }

    public function test_get_all_returns_collection(): void
    {
        $mockQuery = Mockery::mock(Builder::class);
        $mockCollection = Mockery::mock(Collection::class);
        
        $this->mockModel->shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);
        
        $result = $this->repository->getAll();
        
        $this->assertEquals($mockCollection, $result);
    }

    public function test_get_paginated_returns_paginator(): void
    {
        $mockQuery = Mockery::mock(Builder::class);
        $mockPaginator = Mockery::mock(LengthAwarePaginator::class);
        
        $this->mockModel->shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('paginate')
            ->with(15)
            ->once()
            ->andReturn($mockPaginator);
        
        $result = $this->repository->getPaginated();
        
        $this->assertEquals($mockPaginator, $result);
    }

    public function test_create_adds_tenant_id_when_available(): void
    {
        $tenantId = new TenantId('test-tenant');
        $data = ['name' => 'Test'];
        $expectedData = ['name' => 'Test', 'tenant_id' => 'test-tenant'];
        $mockModel = Mockery::mock(Model::class);
        
        $this->repository->setTenantContext($tenantId);
        
        $this->mockModel->shouldReceive('getTenantIdColumn')
            ->once()
            ->andReturn('tenant_id');
        
        $this->mockModel->shouldReceive('create')
            ->with($expectedData)
            ->once()
            ->andReturn($mockModel);
        
        $result = $this->repository->create($data);
        
        $this->assertEquals($mockModel, $result);
    }

    public function test_create_for_tenant_adds_specific_tenant_id(): void
    {
        $tenantId = new TenantId('specific-tenant');
        $data = ['name' => 'Test'];
        $expectedData = ['name' => 'Test', 'tenant_id' => 'specific-tenant'];
        $mockModel = Mockery::mock(Model::class);
        
        $this->mockModel->shouldReceive('getTenantIdColumn')
            ->once()
            ->andReturn('tenant_id');
        
        $this->mockModel->shouldReceive('create')
            ->with($expectedData)
            ->once()
            ->andReturn($mockModel);
        
        $result = $this->repository->createForTenant($data, $tenantId);
        
        $this->assertEquals($mockModel, $result);
    }

    public function test_update_finds_and_updates_model(): void
    {
        $mockQuery = Mockery::mock(Builder::class);
        $mockModel = Mockery::mock(Model::class);
        $data = ['name' => 'Updated'];
        
        $this->mockModel->shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('find')
            ->with(1)
            ->once()
            ->andReturn($mockModel);
        
        $mockModel->shouldReceive('update')
            ->with($data)
            ->once();
        
        $mockModel->shouldReceive('fresh')
            ->once()
            ->andReturn($mockModel);
        
        $result = $this->repository->update(1, $data);
        
        $this->assertEquals($mockModel, $result);
    }

    public function test_update_returns_null_when_model_not_found(): void
    {
        $mockQuery = Mockery::mock(Builder::class);
        $data = ['name' => 'Updated'];
        
        $this->mockModel->shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('find')
            ->with(1)
            ->once()
            ->andReturn(null);
        
        $result = $this->repository->update(1, $data);
        
        $this->assertNull($result);
    }

    public function test_delete_finds_and_deletes_model(): void
    {
        $mockQuery = Mockery::mock(Builder::class);
        $mockModel = Mockery::mock(Model::class);
        
        $this->mockModel->shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('find')
            ->with(1)
            ->once()
            ->andReturn($mockModel);
        
        $mockModel->shouldReceive('delete')
            ->once()
            ->andReturn(true);
        
        $result = $this->repository->delete(1);
        
        $this->assertTrue($result);
    }

    public function test_delete_returns_false_when_model_not_found(): void
    {
        $mockQuery = Mockery::mock(Builder::class);
        
        $this->mockModel->shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('find')
            ->with(1)
            ->once()
            ->andReturn(null);
        
        $result = $this->repository->delete(1);
        
        $this->assertFalse($result);
    }

    public function test_count_returns_query_count(): void
    {
        $mockQuery = Mockery::mock(Builder::class);
        
        $this->mockModel->shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('count')
            ->once()
            ->andReturn(5);
        
        $result = $this->repository->count();
        
        $this->assertEquals(5, $result);
    }

    public function test_without_tenant_scope_executes_callback(): void
    {
        $mockQuery = Mockery::mock(Builder::class);
        $callbackResult = 'callback executed';
        
        $this->mockModel->shouldReceive('withoutGlobalScopes')
            ->once()
            ->andReturn($mockQuery);
        
        $result = $this->repository->withoutTenantScope(function ($query) use ($callbackResult) {
            return $callbackResult;
        });
        
        $this->assertEquals($callbackResult, $result);
    }
}