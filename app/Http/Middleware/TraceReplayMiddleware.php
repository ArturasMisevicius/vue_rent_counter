<?php

namespace App\Http\Middleware;

use App\Support\TraceReplay\TraceReplayStorageHealth;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use TraceReplay\Http\Middleware\TraceMiddleware as BaseTraceMiddleware;

final class TraceReplayMiddleware extends BaseTraceMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->shouldAttemptTracing($request)) {
            return $next($request);
        }

        return parent::handle($request, $next);
    }

    public function terminate(Request $request, Response $response): void
    {
        if (! $this->shouldAttemptTracing($request)) {
            return;
        }

        parent::terminate($request, $response);
    }

    protected function shouldSkipInstrumentation(Request $request): bool
    {
        return parent::shouldSkipInstrumentation($request)
            || $this->matchesConfiguredRoute($request)
            || $this->matchesConfiguredPath($request);
    }

    private function shouldAttemptTracing(Request $request): bool
    {
        return (bool) config('trace-replay.enabled')
            && ! $this->shouldSkipInstrumentation($request)
            && app(TraceReplayStorageHealth::class)->isReady();
    }

    private function matchesConfiguredRoute(Request $request): bool
    {
        $routeName = $request->route()?->getName();

        if (! is_string($routeName) || $routeName === '') {
            return false;
        }

        return collect(config('trace-replay.skip_routes', []))
            ->contains(fn (string $pattern): bool => Str::is($pattern, $routeName));
    }

    private function matchesConfiguredPath(Request $request): bool
    {
        $path = ltrim($request->path(), '/');

        return collect(config('trace-replay.skip_paths', []))
            ->contains(fn (string $pattern): bool => Str::is(trim($pattern, '/'), $path));
    }
}
