<?php

return [
    'labels' => [
        'resource' => 'FAQs',
        'question' => 'Question',
        'answer' => 'Answer',
        'category' => 'Category',
        'display_order' => 'Display Order',
        'published' => 'Published',
        'order' => 'Order',
        'last_updated' => 'Last Updated',
    ],

    'placeholders' => [
        'question' => 'Enter your question here...',
        'category' => 'e.g., Billing, Technical, General',
    ],

    'helper_text' => [
        'entry' => 'Create and manage frequently asked questions for your users.',
        'category' => 'Optional category to organize FAQs.',
        'answer' => 'Provide a detailed answer. HTML formatting is supported.',
        'order' => 'Lower numbers appear first. Default is 0.',
        'published' => 'Only published FAQs are visible to users.',
        'visible' => 'This FAQ is visible to users',
        'hidden' => 'This FAQ is hidden from users',
    ],

    'hints' => [
        'html_sanitized' => 'HTML content is automatically sanitized for security.',
    ],

    'filters' => [
        'status' => 'Publication Status',
        'category' => 'Category',
        'options' => [
            'published' => 'Published',
            'draft' => 'Draft',
        ],
    ],

    'modals' => [
        'delete' => [
            'heading' => 'Delete FAQ',
            'description' => 'Are you sure you want to delete this FAQ? This action cannot be undone.',
        ],
    ],

    'empty' => [
        'heading' => 'No FAQs yet',
        'description' => 'Get started by creating your first FAQ entry.',
    ],

    'actions' => [
        'add_first' => 'Create First FAQ',
    ],

    'sections' => [
        'faq_entry' => 'FAQ Entry',
    ],

    'validation' => [
        'question_format' => 'Question can only contain letters, numbers, and basic punctuation.',
        'category_format' => 'Category can only contain letters, numbers, spaces, hyphens, and underscores.',
        'answer_too_short' => 'Answer must be at least :min characters.',
        'answer_too_long' => 'Answer cannot exceed :max characters.',
    ],

    'errors' => [
        'bulk_limit_exceeded' => 'Cannot delete more than :max items at once.',
        'unauthorized' => 'You do not have permission to manage FAQs.',
    ],
];
