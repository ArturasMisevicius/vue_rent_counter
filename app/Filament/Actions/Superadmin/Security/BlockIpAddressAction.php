<?php

namespace App\Filament\Actions\Superadmin\Security;

use App\Models\BlockedIpAddress;
use Illuminate\Support\Facades\Validator;

class BlockIpAddressAction
{
    public function handle(array $attributes): BlockedIpAddress
    {
        /** @var array{ip_address: string, reason: string, blocked_by_user_id: int, blocked_until?: mixed} $validated */
        $validated = Validator::make($attributes, [
            'ip_address' => ['required', 'ip'],
            'reason' => ['required', 'string', 'max:255'],
            'blocked_by_user_id' => ['required', 'integer', 'exists:users,id'],
            'blocked_until' => ['nullable', 'date'],
        ])->validate();

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
