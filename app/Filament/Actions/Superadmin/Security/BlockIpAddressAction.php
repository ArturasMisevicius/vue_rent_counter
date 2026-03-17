<?php

namespace App\Filament\Actions\Superadmin\Security;

use App\Http\Requests\Superadmin\Security\BlockIpAddressRequest;
use App\Models\BlockedIpAddress;

class BlockIpAddressAction
{
    public function handle(array $attributes): BlockedIpAddress
    {
        /** @var BlockIpAddressRequest $request */
        $request = new BlockIpAddressRequest;
        $validated = $request->validatePayload($attributes);

        return BlockedIpAddress::query()->updateOrCreate(
            ['ip_address' => $validated['ip_address']],
            [
                'reason' => $validated['reason'],
                'blocked_by_user_id' => $validated['blocked_by_user_id'],
                'blocked_until' => $validated['blocked_until'] ?? now()->addDay(),
            ],
        );
    }
}
