<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $testRoutesCachePath = dirname(__DIR__).'/bootstrap/cache/routes-testing.php';

        putenv('APP_ROUTES_CACHE='.$testRoutesCachePath);
        $_ENV['APP_ROUTES_CACHE'] = $testRoutesCachePath;
        $_SERVER['APP_ROUTES_CACHE'] = $testRoutesCachePath;

        return parent::createApplication();
    }
}
