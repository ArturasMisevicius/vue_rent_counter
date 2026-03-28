<?php

declare(strict_types=1);

namespace App\Http\Requests\Superadmin\Subscriptions;

use App\Http\Requests\Concerns\InteractsWithValidationPayload;

class UpdateOrganizationSubscriptionRequest extends StoreOrganizationSubscriptionRequest
{
    use InteractsWithValidationPayload;
}
