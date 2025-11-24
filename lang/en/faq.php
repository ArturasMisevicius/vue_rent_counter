<?php

declare(strict_types=1);

return [
    'labels' => [
        'resource' => 'FAQ',
        'question' => 'Question',
        'category' => 'Category',
        'answer' => 'Answer',
        'display_order' => 'Display order',
        'published' => 'Published',
        'status' => 'Status',
        'order' => 'Order',
        'last_updated' => 'Last Updated',
        'details' => 'FAQ Entry',
    ],

    'sections' => [
        'faq_entry' => 'FAQ Entry',
    ],

    'placeholders' => [
        'question' => 'What is the billing cycle?',
        'category' => 'Billing, Access, Meters...',
    ],

    'helper_text' => [
        'entry' => 'Create or edit FAQ entries displayed on the public landing page',
        'category' => 'Optional category for grouping related questions',
        'answer' => 'Use concise, complete answers. This content is shown publicly on the landing page.',
        'order' => 'Lower numbers appear first.',
        'published' => 'Only published FAQs appear on the landing page',
        'visible' => 'Visible on landing page',
        'hidden' => 'Hidden from public',
    ],

    'filters' => [
        'status' => 'Status',
        'category' => 'Category',
        'options' => [
            'published' => 'Published',
            'draft' => 'Draft',
        ],
    ],

    'empty' => [
        'heading' => 'No FAQ entries yet',
        'description' => 'Create your first FAQ entry to help users understand the platform.',
    ],

    'actions' => [
        'add_first' => 'Add First FAQ',
    ],

    'modals' => [
        'delete' => [
            'heading' => 'Delete FAQ Entries',
            'description' => 'Are you sure you want to delete these FAQ entries? This action cannot be undone.',
        ],
    ],
];
