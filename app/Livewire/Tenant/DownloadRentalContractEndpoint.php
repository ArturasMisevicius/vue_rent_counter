<?php

declare(strict_types=1);

namespace App\Livewire\Tenant;

use App\Filament\Support\RentalContracts\RentalContractFile;
use App\Models\Attachment;
use App\Models\RentalContract;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class DownloadRentalContractEndpoint extends Component
{
    public function download(Request $request, RentalContract $rentalContract, Attachment $attachment): BinaryFileResponse
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);
        Gate::forUser($user)->authorize('download', $rentalContract);

        abort_unless($attachment->organization_id === $rentalContract->organization_id, 404);
        abort_unless($attachment->attachable_type === RentalContract::class, 404);
        abort_unless((int) $attachment->attachable_id === (int) $rentalContract->id, 404);
        abort_unless($attachment->document_type === RentalContractFile::DOCUMENT_TYPE, 404);

        $disk = Storage::disk((string) $attachment->disk);

        abort_unless($disk->exists((string) $attachment->path), 404);

        return response()->download(
            $disk->path((string) $attachment->path),
            $attachment->original_filename ?: $attachment->filename,
            ['Content-Type' => (string) $attachment->mime_type],
        );
    }
}
