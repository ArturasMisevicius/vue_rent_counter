<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

/**
 * AssetOptimizationService handles frontend asset optimization
 * 
 * Provides methods for optimizing CSS, JavaScript, and other assets
 * to improve page load times and overall performance
 */
class AssetOptimizationService
{
    private const CACHE_PREFIX = 'asset_optimization';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get optimized Chart.js configuration for dashboard widgets
     */
    public function getOptimizedChartConfig(): array
    {
        return Cache::remember(
            self::CACHE_PREFIX . '.chart_config',
            self::CACHE_TTL,
            function () {
                return [
                    // Global Chart.js defaults for better performance
                    'defaults' => [
                        'responsive' => true,
                        'maintainAspectRatio' => false,
                        'animation' => [
                            'duration' => 750, // Faster animations
                        ],
                        'elements' => [
                            'point' => [
                                'radius' => 3,
                                'hoverRadius' => 5,
                            ],
                            'line' => [
                                'borderWidth' => 2,
                                'tension' => 0.4,
                            ],
                        ],
                        'plugins' => [
                            'legend' => [
                                'position' => 'top',
                            ],
                            'tooltip' => [
                                'mode' => 'index',
                                'intersect' => false,
                            ],
                        ],
                        // Performance optimizations
                        'parsing' => false,
                        'normalized' => true,
                    ],
                    
                    // Color palette for consistent theming
                    'colors' => [
                        'primary' => 'rgb(59, 130, 246)',
                        'success' => 'rgb(16, 185, 129)',
                        'warning' => 'rgb(245, 158, 11)',
                        'danger' => 'rgb(239, 68, 68)',
                        'info' => 'rgb(14, 165, 233)',
                        'purple' => 'rgb(139, 92, 246)',
                        'pink' => 'rgb(236, 72, 153)',
                        'emerald' => 'rgb(34, 197, 94)',
                        'orange' => 'rgb(251, 146, 60)',
                        'violet' => 'rgb(168, 85, 247)',
                    ],
                    
                    // Responsive breakpoints
                    'breakpoints' => [
                        'sm' => 640,
                        'md' => 768,
                        'lg' => 1024,
                        'xl' => 1280,
                    ],
                ];
            }
        );
    }

    /**
     * Get optimized Livewire configuration
     */
    public function getOptimizedLivewireConfig(): array
    {
        return [
            // Optimize Livewire polling
            'polling' => [
                'default_interval' => '30s',
                'background_interval' => '60s',
                'visible_interval' => '15s',
            ],
            
            // Lazy loading configuration
            'lazy_loading' => [
                'enabled' => true,
                'threshold' => '100px',
                'placeholder' => 'Loading...',
            ],
            
            // Debounce configuration for form inputs
            'debounce' => [
                'default' => '300ms',
                'search' => '500ms',
                'typing' => '150ms',
            ],
        ];
    }

    /**
     * Generate critical CSS for above-the-fold content
     */
    public function generateCriticalCSS(): string
    {
        return Cache::remember(
            self::CACHE_PREFIX . '.critical_css',
            self::CACHE_TTL,
            function () {
                // Critical CSS for dashboard layout
                return "
                    /* Critical CSS for dashboard */
                    .fi-main {
                        min-height: 100vh;
                    }
                    
                    .fi-sidebar {
                        transition: transform 0.2s ease-in-out;
                    }
                    
                    .fi-topbar {
                        backdrop-filter: blur(8px);
                    }
                    
                    /* Widget loading states */
                    .fi-wi-loading {
                        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
                        background-size: 200% 100%;
                        animation: loading 1.5s infinite;
                    }
                    
                    @keyframes loading {
                        0% { background-position: 200% 0; }
                        100% { background-position: -200% 0; }
                    }
                    
                    /* Chart containers */
                    .chart-container {
                        position: relative;
                        height: 300px;
                        width: 100%;
                    }
                    
                    /* Table optimizations */
                    .fi-ta-table {
                        table-layout: fixed;
                    }
                    
                    .fi-ta-cell {
                        overflow: hidden;
                        text-overflow: ellipsis;
                        white-space: nowrap;
                    }
                ";
            }
        );
    }

    /**
     * Get JavaScript optimization configuration
     */
    public function getJavaScriptOptimizations(): array
    {
        return [
            // Lazy load non-critical JavaScript
            'lazy_load' => [
                'charts' => true,
                'modals' => true,
                'tooltips' => true,
            ],
            
            // Preload critical resources
            'preload' => [
                'fonts' => [
                    '/fonts/inter-var.woff2',
                ],
                'scripts' => [
                    '/js/app.js',
                ],
            ],
            
            // Resource hints
            'dns_prefetch' => [
                '//fonts.googleapis.com',
                '//cdn.jsdelivr.net',
            ],
            
            // Service worker configuration
            'service_worker' => [
                'enabled' => true,
                'cache_strategy' => 'cache_first',
                'cache_duration' => 86400, // 24 hours
            ],
        ];
    }

    /**
     * Optimize images for dashboard
     */
    public function optimizeImages(): array
    {
        return [
            // Image optimization settings
            'formats' => [
                'webp' => true,
                'avif' => false, // Not widely supported yet
            ],
            
            // Responsive images
            'responsive' => [
                'breakpoints' => [320, 640, 768, 1024, 1280],
                'quality' => 85,
            ],
            
            // Lazy loading
            'lazy_loading' => [
                'enabled' => true,
                'threshold' => '50px',
                'placeholder' => 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzIwIiBoZWlnaHQ9IjE4MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PC9zdmc+',
            ],
        ];
    }

    /**
     * Get performance monitoring configuration
     */
    public function getPerformanceMonitoring(): array
    {
        return [
            // Core Web Vitals thresholds
            'core_web_vitals' => [
                'lcp' => 2.5, // Largest Contentful Paint (seconds)
                'fid' => 100, // First Input Delay (milliseconds)
                'cls' => 0.1, // Cumulative Layout Shift
            ],
            
            // Performance budget
            'budget' => [
                'javascript' => 200, // KB
                'css' => 100, // KB
                'images' => 500, // KB
                'fonts' => 100, // KB
            ],
            
            // Monitoring endpoints
            'endpoints' => [
                'vitals' => '/api/performance/vitals',
                'errors' => '/api/performance/errors',
            ],
        ];
    }

    /**
     * Generate performance optimization script
     */
    public function generateOptimizationScript(): string
    {
        return Cache::remember(
            self::CACHE_PREFIX . '.optimization_script',
            self::CACHE_TTL,
            function () {
                return "
                    // Performance optimization script
                    (function() {
                        'use strict';
                        
                        // Preload critical resources
                        const preloadResources = [
                            { href: '/css/app.css', as: 'style' },
                            { href: '/js/app.js', as: 'script' }
                        ];
                        
                        preloadResources.forEach(resource => {
                            const link = document.createElement('link');
                            link.rel = 'preload';
                            link.href = resource.href;
                            link.as = resource.as;
                            document.head.appendChild(link);
                        });
                        
                        // Lazy load images
                        if ('IntersectionObserver' in window) {
                            const imageObserver = new IntersectionObserver((entries, observer) => {
                                entries.forEach(entry => {
                                    if (entry.isIntersecting) {
                                        const img = entry.target;
                                        img.src = img.dataset.src;
                                        img.classList.remove('lazy');
                                        observer.unobserve(img);
                                    }
                                });
                            });
                            
                            document.querySelectorAll('img[data-src]').forEach(img => {
                                imageObserver.observe(img);
                            });
                        }
                        
                        // Optimize Chart.js rendering
                        if (window.Chart) {
                            Chart.defaults.animation.duration = 750;
                            Chart.defaults.responsive = true;
                            Chart.defaults.maintainAspectRatio = false;
                        }
                        
                        // Performance monitoring
                        if ('PerformanceObserver' in window) {
                            // Monitor Largest Contentful Paint
                            new PerformanceObserver((entryList) => {
                                const entries = entryList.getEntries();
                                const lastEntry = entries[entries.length - 1];
                                console.log('LCP:', lastEntry.startTime);
                            }).observe({ entryTypes: ['largest-contentful-paint'] });
                            
                            // Monitor First Input Delay
                            new PerformanceObserver((entryList) => {
                                const entries = entryList.getEntries();
                                entries.forEach(entry => {
                                    console.log('FID:', entry.processingStart - entry.startTime);
                                });
                            }).observe({ entryTypes: ['first-input'] });
                        }
                    })();
                ";
            }
        );
    }

    /**
     * Clear optimization cache
     */
    public function clearCache(): void
    {
        $keys = [
            self::CACHE_PREFIX . '.chart_config',
            self::CACHE_PREFIX . '.critical_css',
            self::CACHE_PREFIX . '.optimization_script',
        ];
        
        foreach ($keys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Get asset optimization statistics
     */
    public function getOptimizationStats(): array
    {
        return [
            'cache_status' => [
                'chart_config' => Cache::has(self::CACHE_PREFIX . '.chart_config'),
                'critical_css' => Cache::has(self::CACHE_PREFIX . '.critical_css'),
                'optimization_script' => Cache::has(self::CACHE_PREFIX . '.optimization_script'),
            ],
            'asset_sizes' => $this->getAssetSizes(),
            'performance_score' => $this->calculatePerformanceScore(),
        ];
    }

    /**
     * Get asset file sizes
     */
    private function getAssetSizes(): array
    {
        $publicPath = public_path();
        
        return [
            'css' => $this->getDirectorySize($publicPath . '/css'),
            'js' => $this->getDirectorySize($publicPath . '/js'),
            'images' => $this->getDirectorySize($publicPath . '/images'),
            'fonts' => $this->getDirectorySize($publicPath . '/fonts'),
        ];
    }

    /**
     * Get directory size in KB
     */
    private function getDirectorySize(string $path): int
    {
        if (!File::exists($path)) {
            return 0;
        }
        
        $size = 0;
        $files = File::allFiles($path);
        
        foreach ($files as $file) {
            $size += $file->getSize();
        }
        
        return round($size / 1024); // Convert to KB
    }

    /**
     * Calculate performance score (simplified)
     */
    private function calculatePerformanceScore(): int
    {
        // This is a simplified calculation
        // In production, you'd use real performance metrics
        $score = 100;
        
        $assetSizes = $this->getAssetSizes();
        $budget = $this->getPerformanceMonitoring()['budget'];
        
        // Deduct points for exceeding budget
        if ($assetSizes['js'] > $budget['javascript']) {
            $score -= 10;
        }
        
        if ($assetSizes['css'] > $budget['css']) {
            $score -= 10;
        }
        
        if ($assetSizes['images'] > $budget['images']) {
            $score -= 15;
        }
        
        return max(0, $score);
    }
}