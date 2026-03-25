<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Attachment;
use App\Models\User;
use App\Models\UserKycProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class KycAttachmentController extends Controller
{
    public function __invoke(Request $request, Attachment $attachment): BinaryFileResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        abort_unless($attachment->attachable instanceof UserKycProfile, 404);

        $canAccess = $user->isSuperadmin()
            || ($user->isAdminLike() && $user->organization_id === $attachment->organization_id)
            || $attachment->attachable->user_id === $user->id;

        abort_unless($canAccess, 403);

        $disk = Storage::disk((string) $attachment->disk);

        abort_unless($disk->exists((string) $attachment->path), 404);

        return response()->file($disk->path((string) $attachment->path), [
            'Content-Type' => (string) $attachment->mime_type,
            'Content-Disposition' => 'inline; filename="'.($attachment->original_filename ?: $attachment->filename).'"',
        ]);
    }
}
