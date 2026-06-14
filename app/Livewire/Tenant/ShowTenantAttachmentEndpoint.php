<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Filament\Support\Tenants\TenantLeaseAgreement;
use App\Models\Attachment;
use App\Models\ExtraCharge;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ShowTenantAttachmentEndpoint extends Component
{
    public function show(Request $request, Attachment $attachment): BinaryFileResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $canAccess = $this->canAccessTenantAttachment($user, $attachment);

        abort_unless($canAccess, 403);

        $disk = Storage::disk((string) $attachment->disk);

        abort_unless($disk->exists((string) $attachment->path), 404);

        return response()->file($disk->path((string) $attachment->path), [
            'Content-Type' => (string) $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.($attachment->original_filename ?: $attachment->filename).'"',
        ]);
    }

    private function canAccessTenantAttachment(User $user, Attachment $attachment): bool
    {
        $attachable = $attachment->attachable;

        if ($attachment->document_type === TenantLeaseAgreement::DOCUMENT_TYPE) {
            if (! $attachable instanceof User || ! $attachable->isTenant()) {
                abort(404);
            }

            return $user->isSuperadmin()
                || ($user->isAdminLike() && $user->organization_id === $attachment->organization_id)
                || $attachable->id === $user->id;
        }

        if (! $attachable instanceof ExtraCharge) {
            abort(404);
        }

        if ($user->isSuperadmin() || ($user->isAdminLike() && $user->organization_id === $attachment->organization_id)) {
            return true;
        }

        return $user->isTenant()
            && $attachment->tenant_visible
            && $attachable->tenant_id === $user->id
            && $attachable->organization_id === $user->organization_id;
    }
}
