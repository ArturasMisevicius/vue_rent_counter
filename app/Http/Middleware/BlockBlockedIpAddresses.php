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
        if (! Schema::hasTable('blocked_ip_addresses')) {
            return $next($request);
        }

        $blocked = BlockedIpAddress::query()
            ->where('ip_address', $request->ip())
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();

        if ($blocked) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
