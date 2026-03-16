<?php

namespace App\Exceptions;

use Exception;

class SubscriptionLimitExceededException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $message
     * @return void
     */
    public function __construct(string $message = 'You have reached the maximum limit for your subscription plan. Please upgrade your subscription.')
    {
        parent::__construct($message);
    }
}
