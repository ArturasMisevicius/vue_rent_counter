<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin\RentalContracts;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;

class UpdateRentalContractRequest extends StoreRentalContractRequest
{
    use InteractsWithValidationPayload;
}
