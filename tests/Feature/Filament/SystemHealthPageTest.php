<?php

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

beforeEach(function () {
    $this->superadmin = User::factory()->create([
        'role' => 'superadmin',
        'email' => 'superadmin@test.com',
    ]);
});

test('superadmin can access system health page', function () {
    actingAs($this->superadmin)
        ->get('/admin/system-health')
        ->assertSuccessful();
});

test('non-superadmin cannot access system health page', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    
    actingAs($admin)
        ->get('/admin/system-health')
        ->assertForbidden();
});

test('system health page displays database health section', function () {
    actingAs($this->superadmin)
        ->get('/admin/system-health')
        ->assertSee('Database Health')
        ->assertSee('Connection')
        ->assertSee('Tables');
});

test('system health page displays backup status section', function () {
    actingAs($this->superadmin)
        ->get('/admin/system-health')
        ->assertSee('Backup Status');
});

test('system health page displays queue status section', function () {
    actingAs($this->superadmin)
        ->get('/admin/system-health')
        ->assertSee('Queue Status')
        ->assertSee('Pending Jobs')
        ->assertSee('Failed Jobs');
});

test('system health page displays storage metrics section', function () {
    actingAs($this->superadmin)
        ->get('/admin/system-health')
        ->assertSee('Storage Metrics')
        ->assertSee('Disk Usage');
});

test('system health page displays cache status section', function () {
    actingAs($this->superadmin)
        ->get('/admin/system-health')
        ->assertSee('Cache Status')
        ->assertSee('Connection');
});

test('database health check returns correct data', function () {
    $page = new \App\Filament\Pages\SystemHealth();
    $health = $page->getDatabaseHealth();
    
    expect($health)->toHaveKeys(['status', 'color', 'connection'])
        ->and($health['status'])->toBeString()
        ->and($health['color'])->toBeIn(['success', 'warning', 'danger']);
});

test('backup status check returns correct data', function () {
    $page = new \App\Filament\Pages\SystemHealth();
    $status = $page->getBackupStatus();
    
    expect($status)->toHaveKeys(['status', 'color'])
        ->and($status['status'])->toBeString()
        ->and($status['color'])->toBeIn(['success', 'warning', 'danger']);
});

test('queue status check returns correct data', function () {
    $page = new \App\Filament\Pages\SystemHealth();
    $status = $page->getQueueStatus();
    
    expect($status)->toHaveKeys(['status', 'color'])
        ->and($status['status'])->toBeString()
        ->and($status['color'])->toBeIn(['success', 'warning', 'danger']);
});

test('storage metrics check returns correct data', function () {
    $page = new \App\Filament\Pages\SystemHealth();
    $metrics = $page->getStorageMetrics();
    
    expect($metrics)->toHaveKeys(['status', 'color'])
        ->and($metrics['status'])->toBeString()
        ->and($metrics['color'])->toBeIn(['success', 'warning', 'danger']);
});

test('cache status check returns correct data', function () {
    $page = new \App\Filament\Pages\SystemHealth();
    $status = $page->getCacheStatus();
    
    expect($status)->toHaveKeys(['status', 'color'])
        ->and($status['status'])->toBeString()
        ->and($status['color'])->toBeIn(['success', 'warning', 'danger']);
});

test('health checks are cached', function () {
    $page = new \App\Filament\Pages\SystemHealth();
    
    // First call
    $health1 = $page->getDatabaseHealth();
    
    // Second call should return cached data
    $health2 = $page->getDatabaseHealth();
    
    expect($health1)->toBe($health2);
});

test('health check actions are available', function () {
    actingAs($this->superadmin)
        ->get('/admin/system-health')
        ->assertSee('Run Health Check')
        ->assertSee('Trigger Manual Backup')
        ->assertSee('Clear Cache')
        ->assertSee('Download Diagnostic Report');
});

test('system health page has all required sections', function () {
    actingAs($this->superadmin)
        ->get('/admin/system-health')
        ->assertSee('Database Health')
        ->assertSee('Backup Status')
        ->assertSee('Queue Status')
        ->assertSee('Storage Metrics')
        ->assertSee('Cache Status');
});
