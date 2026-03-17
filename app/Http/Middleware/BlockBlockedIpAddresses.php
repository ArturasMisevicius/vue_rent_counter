<?php

namespace App\Http\Middleware;

use App\Models\BlockedIpAddress;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class BlockBlockedIpAddresses
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            (app()->runningInConsole() && ! app()->runningUnitTests()) ||
            ! Schema::hasTable((new BlockedIpAddress)->getTable())
        ) {
            return $next($request);
        }

        $isBlocked = BlockedIpAddress::query()
            ->where('ip_address', $request->ip())
            ->where(function ($query): void {
                $query->whereNull('blocked_until')
                    ->orWhere('blocked_until', '>', now());
            })
            ->exists();

        abort_if($isBlocked, 403);

        return $next($request);
    }
}
