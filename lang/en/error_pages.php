<?php

declare(strict_types=1);

return [
    'common' => [
        'dashboard' => 'Go to Dashboard',
        'home' => 'Go to Home',
        'login' => 'Go to Login',
        'back' => 'Go Back',
        'back_fix' => 'Go Back and Fix Errors',
        'refresh' => 'Refresh Page',
        'return_home' => 'Return to Home',
        'actions_title' => 'What you can do:',
        'try_refresh' => 'Try refreshing the page',
        'try_again' => 'Go back and try again',
        'contact_support' => 'Contact support if the problem persists',
    ],
    '401' => [
        'title' => '401 - Unauthorized',
        'headline' => 'Unauthorized',
        'description' => 'You need to be logged in to access this page.',
    ],
    '403' => [
        'title' => '403 - Forbidden',
        'headline' => 'Access Forbidden',
        'description' => 'You do not have permission to access this resource.',
    ],
    '404' => [
        'title' => '404 - Not Found',
        'headline' => 'Page Not Found',
        'description' => "The page you're looking for doesn't exist or has been moved.",
    ],
    '422' => [
        'title' => '422 - Validation Error',
        'headline' => 'Validation Error',
        'description' => 'The data you submitted contains errors. Please review and try again.',
        'errors_title' => 'Validation Errors:',
    ],
    '500' => [
        'title' => '500 - Server Error',
        'headline' => 'Server Error',
        'description' => "Something went wrong on our end. We're working to fix it.",
    ],
];
