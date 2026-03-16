<?php

namespace App\Exceptions;

use Exception;

class CannotDeleteWithDependenciesException extends Exception
{
    /**
     * Create a new exception instance.
     *
     * @param string $message Custom error message or default message will be used
     * @return void
     */
    public function __construct(string $message = 'Cannot delete resource because it has associated dependencies. Please deactivate instead.')
    {
        parent::__construct($message);
    }
}
