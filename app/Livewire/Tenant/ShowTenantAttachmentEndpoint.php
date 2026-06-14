<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Filament\Support\Tenants\TenantLeaseAgreement;
use App\Models\Attachment;
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
        abort_unless($attachment->document_type === TenantLeaseAgreement::DOCUMENT_TYPE, 404);
        abort_unless($attachment->attachable instanceof User && $attachment->attachable->isTenant(), 404);

        $tenant = $attachment->attachable;
        $canAccess = $user->isSuperadmin()
            || ($user->isAdminLike() && $user->organization_id === $attachment->organization_id)
            || $tenant->id === $user->id;

        abort_unless($canAccess, 403);

        $disk = Storage::disk((string) $attachment->disk);

        abort_unless($disk->exists((string) $attachment->path), 404);

        return response()->file($disk->path((string) $attachment->path), [
            'Content-Type' => (string) $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.($attachment->original_filename ?: $attachment->filename).'"',
        ]);
    }
}
