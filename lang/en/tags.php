<?php

return [
    'resource' => [
        'label' => 'Tag',
        'plural_label' => 'Tags',
        'navigation_label' => 'Tags',
    ],
    'fields' => [
        'name' => 'Title',
        'slug' => 'Link',
        'color' => 'Color',
        'description' => 'Description',
        'type' => 'Type',
        'order' => 'Queue',
    ],
    'types' => [
        'general' => 'Common',
        'property' => 'Property',
        'tenant' => 'Tenant',
        'invoice' => 'Invoice',
    ],
    'actions' => [
        'create' => 'Create a tag',
        'edit' => 'Edit tag',
        'delete' => 'Delete tag',
        'view' => 'View tag',
    ],
    'messages' => [
        'created' => 'Tag created successfully.',
        'updated' => 'Tag updated successfully.',
        'deleted' => 'Tag deleted successfully.',
    ],
    'labels' => [
        'description' => 'Description',
        'name' => 'Title',
        'color' => 'Color',
        'type' => 'Type',
    ],
];
