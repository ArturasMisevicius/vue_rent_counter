<?php

return [
    /*
    |--------------------------------------------------------------------------
    | FAQ Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security settings for FAQ management including rate limiting,
    | validation rules, and content security policies.
    |
    */

    'rate_limiting' => [
        /*
        | Rate limit for FAQ creation (per user per minute)
        */
        'create' => [
            'max_attempts' => 5,
            'decay_minutes' => 1,
        ],

        /*
        | Rate limit for FAQ updates (per user per minute)
        */
        'update' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
        ],

        /*
        | Rate limit for FAQ deletion (per user per minute)
        */
        'delete' => [
            'max_attempts' => 10,
            'decay_minutes' => 1,
        ],

        /*
        | Rate limit for bulk operations (per user per hour)
        */
        'bulk' => [
            'max_attempts' => 20,
            'decay_minutes' => 60,
        ],
    ],

    'validation' => [
        /*
        | Maximum length for question field
        */
        'question_max_length' => 255,

        /*
        | Minimum length for question field
        */
        'question_min_length' => 10,

        /*
        | Maximum length for answer field
        */
        'answer_max_length' => 10000,

        /*
        | Minimum length for answer field
        */
        'answer_min_length' => 10,

        /*
        | Maximum length for category field
        */
        'category_max_length' => 120,

        /*
        | Maximum display order value
        */
        'display_order_max' => 9999,

        /*
        | Allowed HTML tags in answer field
        */
        'allowed_html_tags' => '<p><br><strong><em><u><ul><ol><li><a>',
    ],

    'cache' => [
        /*
        | Cache TTL for category options (in minutes)
        */
        'category_ttl' => 15,

        /*
        | Cache key prefix for FAQ-related caches
        */
        'key_prefix' => 'faq:',

        /*
        | Maximum number of categories to cache
        */
        'max_categories' => 100,
    ],

    'security' => [
        /*
        | Enable HTML sanitization on answer field
        */
        'sanitize_html' => true,

        /*
        | Enable audit trail logging
        */
        'audit_trail' => true,

        /*
        | Require confirmation for bulk delete
        */
        'confirm_bulk_delete' => true,

        /*
        | Maximum items per bulk operation
        */
        'bulk_operation_limit' => 50,
    ],
];
