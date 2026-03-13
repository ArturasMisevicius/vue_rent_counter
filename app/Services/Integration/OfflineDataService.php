<?php

declare(strict_types=1);

namespace App\Services\Integration;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OfflineDataService
{
    private const OFFLINE_MODE_KEY = 'integration_offline_mode';
    private const OFFLINE_DATA_PREFIX = 'offline_data:';
    private const PENDING_SYNC_PREFIX = 'pending_sync:';
    
    /**
     * Store successful result for offline use
     */
    public function storeSuccessfulResult(string $serviceName, mixed $data): void
    {
        $key = self::OFFLINE_DATA_PREFIX . $serviceName;
        
        Cache::put($key, [
            'data' => $data,
            'timestamp' => now(),
            'service' => $serviceName,
        ], now()->addDays(7));
        
        $this->registerCacheKey($key);
        
        Log::debug('Stored offline data', [
            'service' => $serviceName,
            'size' => is_string($data) ? strlen($data) : 'complex',
        ]);
    }
    
    /**
     * Get last successful result for offline use
     */
    public function getLastSuccessfulResult(string $serviceName): mixed
    {
        $key = self::OFFLINE_DATA_PREFIX . $serviceName;
        $cached = Cache::get($key);
        
        if ($cached && isset($cached['data'])) {
            Log::info('Retrieved offline data', [
                'service' => $serviceName,
                'age_hours' => now()->diffInHours($cached['timestamp']),
            ]);
            
            return $cached['data'];
        }
        
        return null;
    }
    
    /**
     * Store data that needs to be synchronized later
     */
    public function storePendingSync(string $serviceName, array $data, string $operation = 'update'): void
    {
        $key = self::PENDING_SYNC_PREFIX . $serviceName . ':' . uniqid();
        
        Cache::put($key, [
            'service' => $serviceName,
            'operation' => $operation,
            'data' => $data,
            'timestamp' => now(),
            'attempts' => 0,
        ], now()->addDays(30));
        
        $this->registerCacheKey($key);
        
        Log::info('Stored pending sync data', [
            'service' => $serviceName,
            'operation' => $operation,
            'key' => $key,
        ]);
    }
    
    /**
     * Get all pending synchronization data
     */
    public function getPendingSyncData(string $serviceName = null): array
    {
        $pattern = $serviceName 
            ? self::PENDING_SYNC_PREFIX . $serviceName . ':'
            : self::PENDING_SYNC_PREFIX;
            
        $keys = $this->getCacheKeys($pattern);
        $pendingData = [];
        
        foreach ($keys as $key) {
            $data = Cache::get($key);
            if ($data) {
                $pendingData[$key] = $data;
            }
        }
        
        return $pendingData;
    }
    
    /**
     * Synchronize pending data when services come back online
     */
    public function synchronizePendingData(): array
    {
        $pendingData = $this->getPendingSyncData();
        $results = [
            'synchronized' => 0,
            'failed' => 0,
            'errors' => [],
        ];
        
        foreach ($pendingData as $key => $data) {
            try {
                $this->processPendingSync($data);
                Cache::forget($key);
                $results['synchronized']++;
                
                Log::info('Successfully synchronized pending data', [
                    'service' => $data['service'],
                    'operation' => $data['operation'],
                ]);
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'service' => $data['service'],
                    'error' => $e->getMessage(),
                ];
                
                // Increment attempt count
                $data['attempts'] = ($data['attempts'] ?? 0) + 1;
                
                if ($data['attempts'] < 3) {
                    Cache::put($key, $data, now()->addDays(30));
                } else {
                    // Give up after 3 attempts
                    Cache::forget($key);
                    Log::error('Gave up synchronizing pending data after 3 attempts', [
                        'service' => $data['service'],
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Process individual pending sync item
     */
    private function processPendingSync(array $data): void
    {
        $serviceName = $data['service'];
        $operation = $data['operation'];
        $payload = $data['data'];
        
        // This would typically call the actual integration service
        // For now, we'll just simulate the sync
        switch ($operation) {
            case 'meter_reading':
                $this->syncMeterReading($payload);
                break;
            case 'billing_data':
                $this->syncBillingData($payload);
                break;
            case 'configuration':
                $this->syncConfiguration($payload);
                break;
            default:
                throw new \InvalidArgumentException("Unknown sync operation: {$operation}");
        }
    }
    
    /**
     * Sync meter reading data
     */
    private function syncMeterReading(array $data): void
    {
        // Simulate meter reading sync
        DB::table('meter_readings')->updateOrInsert(
            ['meter_id' => $data['meter_id'], 'reading_date' => $data['reading_date']],
            $data
        );
    }
    
    /**
     * Sync billing data
     */
    private function syncBillingData(array $data): void
    {
        // Simulate billing data sync
        DB::table('billing_records')->updateOrInsert(
            ['property_id' => $data['property_id'], 'billing_period' => $data['billing_period']],
            $data
        );
    }
    
    /**
     * Sync configuration data
     */
    private function syncConfiguration(array $data): void
    {
        // Simulate configuration sync
        DB::table('configurations')->updateOrInsert(
            ['service_id' => $data['service_id']],
            $data
        );
    }
    
    /**
     * Enable offline mode
     */
    public function enableOfflineMode(): void
    {
        Cache::put(self::OFFLINE_MODE_KEY, true, now()->addDays(1));
    }
    
    /**
     * Disable offline mode
     */
    public function disableOfflineMode(): void
    {
        Cache::forget(self::OFFLINE_MODE_KEY);
    }
    
    /**
     * Check if offline mode is enabled
     */
    public function isOfflineModeEnabled(): bool
    {
        return Cache::get(self::OFFLINE_MODE_KEY, false);
    }
    
    /**
     * Get offline data statistics
     */
    public function getOfflineStats(): array
    {
        $offlineKeys = $this->getCacheKeys(self::OFFLINE_DATA_PREFIX);
        $pendingKeys = $this->getCacheKeys(self::PENDING_SYNC_PREFIX);
        
        return [
            'offline_mode' => $this->isOfflineModeEnabled(),
            'cached_services' => count($offlineKeys),
            'pending_sync_items' => count($pendingKeys),
            'oldest_cache' => $this->getOldestCacheAge(),
        ];
    }
    
    /**
     * Get age of oldest cached data
     */
    private function getOldestCacheAge(): ?int
    {
        $offlineKeys = $this->getCacheKeys(self::OFFLINE_DATA_PREFIX);
        $oldestAge = null;
        
        foreach ($offlineKeys as $key) {
            $data = Cache::get($key);
            if ($data && isset($data['timestamp'])) {
                $age = now()->diffInHours($data['timestamp']);
                if ($oldestAge === null || $age > $oldestAge) {
                    $oldestAge = $age;
                }
            }
        }
        
        return $oldestAge;
    }
    
    /**
     * Get cache keys matching a pattern (cache-driver agnostic)
     */
    private function getCacheKeys(string $prefix): array
    {
        // For testing with ArrayStore, we'll use a different approach
        if (app()->environment('testing')) {
            return $this->getTestCacheKeys($prefix);
        }
        
        // For Redis in production
        try {
            return Cache::getRedis()->keys($prefix . '*');
        } catch (\Exception $e) {
            // Fallback for non-Redis cache drivers
            return $this->getTestCacheKeys($prefix);
        }
    }
    
    /**
     * Get cache keys for testing environment
     */
    private function getTestCacheKeys(string $prefix): array
    {
        // In testing, we'll maintain a registry of keys
        $registryKey = 'cache_keys_registry';
        $registry = Cache::get($registryKey, []);
        
        return array_filter($registry, function($key) use ($prefix) {
            return str_starts_with($key, $prefix);
        });
    }
    
    /**
     * Register a cache key (for testing)
     */
    private function registerCacheKey(string $key): void
    {
        if (app()->environment('testing')) {
            $registryKey = 'cache_keys_registry';
            $registry = Cache::get($registryKey, []);
            $registry[] = $key;
            Cache::put($registryKey, array_unique($registry), now()->addDays(1));
        }
    }
}