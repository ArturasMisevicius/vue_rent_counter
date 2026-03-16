<?php

namespace App\Exceptions;

use Exception;

class SubscriptionExpiredException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $message
     * @return void
     */
    public function __construct(string $message = 'Your subscription has expired. Please renew to continue managing your properties.')
    {
        parent::__construct($message);
    }
}
