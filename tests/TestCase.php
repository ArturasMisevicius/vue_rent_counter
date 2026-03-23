<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $testRoutesCachePath = dirname(__DIR__).'/bootstrap/cache/routes-testing.php';
        $testConfigCachePath = dirname(__DIR__).'/bootstrap/cache/config-testing.php';

        if (file_exists($testConfigCachePath)) {
            @unlink($testConfigCachePath);
        }

        // Keep test env deterministic even if an individual test mutates process env vars.
        putenv('APP_ENV=testing');
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');

        $_ENV['APP_ENV'] = 'testing';
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';

        $_SERVER['APP_ENV'] = 'testing';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';

        putenv('APP_ROUTES_CACHE='.$testRoutesCachePath);
        $_ENV['APP_ROUTES_CACHE'] = $testRoutesCachePath;
        $_SERVER['APP_ROUTES_CACHE'] = $testRoutesCachePath;
        putenv('APP_CONFIG_CACHE='.$testConfigCachePath);
        $_ENV['APP_CONFIG_CACHE'] = $testConfigCachePath;
        $_SERVER['APP_CONFIG_CACHE'] = $testConfigCachePath;

        $app = parent::createApplication();

        // Keep the test suite isolated from local sqlite files even if process state drifts.
        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['db']->purge('sqlite');

        return $app;
    }
}
