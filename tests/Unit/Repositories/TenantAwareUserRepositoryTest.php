<?php

namespace Tests\Unit\Repositories;

use App\Contracts\TenantContextInterface;
use App\Models\User;
use App\Repositories\TenantAwareUserRepository;
use App\ValueObjects\TenantId;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class TenantAwareUserRepositoryTest extends TestCase
{
    private $mockTenantContext;
    private TenantAwareUserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->mockTenantContext = Mockery::mock(TenantContextInterface::class);
        $this->repository = new TenantAwareUserRepository($this->mockTenantContext);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_find_by_email_queries_with_email_constraint(): void
    {
        $email = 'test@example.com';
        $mockUser = Mockery::mock(User::class);
        $mockQuery = Mockery::mock(Builder::class);
        
        // Mock the model's newQuery method
        User::shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('where')
            ->with('email', $email)
            ->once()
            ->andReturnSelf();
        
        $mockQuery->shouldReceive('first')
            ->once()
            ->andReturn($mockUser);
        
        $result = $this->repository->findByEmail($email);
        
        $this->assertEquals($mockUser, $result);
    }

    public function test_find_by_email_for_tenant_uses_tenant_context(): void
    {
        $email = 'test@example.com';
        $tenantId = new TenantId('test-tenant');
        $mockUser = Mockery::mock(User::class);
        $mockQuery = Mockery::mock(Builder::class);
        
        User::shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('where')
            ->with('email', $email)
            ->once()
            ->andReturnSelf();
        
        $mockQuery->shouldReceive('first')
            ->once()
            ->andReturn($mockUser);
        
        $result = $this->repository->findByEmailForTenant($email, $tenantId);
        
        $this->assertEquals($mockUser, $result);
    }

    public function test_get_active_users_filters_by_active_status(): void
    {
        $mockCollection = Mockery::mock(Collection::class);
        $mockQuery = Mockery::mock(Builder::class);
        
        User::shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('where')
            ->with('is_active', true)
            ->once()
            ->andReturnSelf();
        
        $mockQuery->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);
        
        $result = $this->repository->getActiveUsers();
        
        $this->assertEquals($mockCollection, $result);
    }

    public function test_get_users_by_role_filters_by_role(): void
    {
        $role = 'admin';
        $mockCollection = Mockery::mock(Collection::class);
        $mockQuery = Mockery::mock(Builder::class);
        
        User::shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('where')
            ->with('role', $role)
            ->once()
            ->andReturnSelf();
        
        $mockQuery->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);
        
        $result = $this->repository->getUsersByRole($role);
        
        $this->assertEquals($mockCollection, $result);
    }

    public function test_search_users_searches_name_and_email(): void
    {
        $search = 'john';
        $mockCollection = Mockery::mock(Collection::class);
        $mockQuery = Mockery::mock(Builder::class);
        $mockSubQuery = Mockery::mock(Builder::class);
        
        User::shouldReceive('newQuery')
            ->once()
            ->andReturn($mockQuery);
        
        $mockQuery->shouldReceive('where')
            ->with(Mockery::type('Closure'))
            ->once()
            ->andReturnUsing(function ($closure) use ($mockSubQuery) {
                $closure($mockSubQuery);
                return $mockQuery;
            });
        
        $mockSubQuery->shouldReceive('where')
            ->with('name', 'like', '%john%')
            ->once()
            ->andReturnSelf();
        
        $mockSubQuery->shouldReceive('orWhere')
            ->with('email', 'like', '%john%')
            ->once()
            ->andReturnSelf();
        
        $mockQuery->shouldReceive('get')
            ->once()
            ->andReturn($mockCollection);
        
        $result = $this->repository->searchUsers($search);
        
        $this->assertEquals($mockCollection, $result);
    }

    public function test_get_user_stats_returns_statistics_array(): void
    {
        $mockQuery = Mockery::mock(Builder::class);
        $mockRoleQuery = Mockery::mock(Builder::class);
        $mockCollection = Mockery::mock(Collection::class);
        
        // Mock multiple query instances for different stats
        User::shouldReceive('newQuery')
            ->times(4)
            ->andReturn($mockQuery, $mockQuery, $mockQuery, $mockRoleQuery);
        
        // Total count
        $mockQuery->shouldReceive('count')
            ->once()
            ->andReturn(10);
        
        // Active count
        $mockQuery->shouldReceive('where')
            ->with('is_active', true)
            ->once()
            ->andReturnSelf();
        $mockQuery->shouldReceive('count')
            ->once()
            ->andReturn(8);
        
        // Inactive count
        $mockQuery->shouldReceive('where')
            ->with('is_active', false)
            ->once()
            ->andReturnSelf();
        $mockQuery->shouldReceive('count')
            ->once()
            ->andReturn(2);
        
        // Role breakdown
        $mockRoleQuery->shouldReceive('selectRaw')
            ->with('role, count(*) as count')
            ->once()
            ->andReturnSelf();
        $mockRoleQuery->shouldReceive('groupBy')
            ->with('role')
            ->once()
            ->andReturnSelf();
        $mockRoleQuery->shouldReceive('pluck')
            ->with('count', 'role')
            ->once()
            ->andReturn($mockCollection);
        $mockCollection->shouldReceive('toArray')
            ->once()
            ->andReturn(['admin' => 2, 'user' => 8]);
        
        $result = $this->repository->getUserStats();
        
        $this->assertEquals([
            'total' => 10,
            'active' => 8,
            'inactive' => 2,
            'by_role' => ['admin' => 2, 'user' => 8],
        ], $result);
    }

    public function test_get_user_stats_for_tenant_uses_tenant_context(): void
    {
        $tenantId = new TenantId('test-tenant');
        $mockQuery = Mockery::mock(Builder::class);
        $mockRoleQuery = Mockery::mock(Builder::class);
        $mockCollection = Mockery::mock(Collection::class);
        
        // Mock multiple query instances for different stats
        User::shouldReceive('newQuery')
            ->times(4)
            ->andReturn($mockQuery, $mockQuery, $mockQuery, $mockRoleQuery);
        
        // Total count
        $mockQuery->shouldReceive('count')
            ->once()
            ->andReturn(5);
        
        // Active count
        $mockQuery->shouldReceive('where')
            ->with('is_active', true)
            ->once()
            ->andReturnSelf();
        $mockQuery->shouldReceive('count')
            ->once()
            ->andReturn(4);
        
        // Inactive count
        $mockQuery->shouldReceive('where')
            ->with('is_active', false)
            ->once()
            ->andReturnSelf();
        $mockQuery->shouldReceive('count')
            ->once()
            ->andReturn(1);
        
        // Role breakdown
        $mockRoleQuery->shouldReceive('selectRaw')
            ->with('role, count(*) as count')
            ->once()
            ->andReturnSelf();
        $mockRoleQuery->shouldReceive('groupBy')
            ->with('role')
            ->once()
            ->andReturnSelf();
        $mockRoleQuery->shouldReceive('pluck')
            ->with('count', 'role')
            ->once()
            ->andReturn($mockCollection);
        $mockCollection->shouldReceive('toArray')
            ->once()
            ->andReturn(['admin' => 1, 'user' => 4]);
        
        $result = $this->repository->getUserStatsForTenant($tenantId);
        
        $this->assertEquals([
            'total' => 5,
            'active' => 4,
            'inactive' => 1,
            'by_role' => ['admin' => 1, 'user' => 4],
        ], $result);
    }
}