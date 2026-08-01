<?php

return [

    'title' => 'Documentation',
    'description' => 'Internal guides for using the application.',

    'button' => [
        'manage' => 'Manage',
        'read' => 'Open documentation',
        'create' => 'New documentation',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'delete' => 'Delete',
    ],

    'status' => [
        'draft' => 'Draft',
        'published' => 'Published',
    ],

    'empty' => [
        'reader' => 'No documentation has been published yet.',
        'manage' => 'No documentation yet.',
    ],

    'message' => [
        'created' => 'The documentation has been created.',
        'updated' => 'The documentation has been updated.',
        'deleted' => 'The documentation has been deleted.',
        'category_created' => 'The category has been created.',
        'category_updated' => 'The category has been updated.',
        'category_deleted' => 'The category has been deleted.',
    ],

    'reader' => [
        'list' => 'Documentation list',
        'list_description' => 'Pick the documentation to read.',
    ],

    'manage' => [
        'title' => 'Manage documentation',
        'description' => 'Arrange categories, order, status, and documentation content.',

        'category' => [
            'title' => 'Categories',
            'label' => 'Category name',
            'placeholder' => 'New category',
            'add' => 'Add category',
            'move_up' => 'Move category up',
            'move_down' => 'Move category down',
            'rename' => 'Rename category',
            'rename_description' => 'The new name appears everywhere the category is listed.',
            'delete' => 'Delete category',
            'delete_title' => 'Delete the category :name?',
            'delete_description' => 'A category can only be deleted once it no longer contains documentation.',
        ],

        'document' => [
            'title' => 'Documentation',
            'move_up' => 'Move documentation up',
            'move_down' => 'Move documentation down',
            'edit' => 'Edit documentation',
            'delete' => 'Delete documentation',
            'delete_title' => 'Delete this documentation permanently?',
            'delete_description' => 'This action cannot be undone.',
        ],

        'column' => [
            'title' => 'Title',
            'category' => 'Category',
            'status' => 'Status',
            'actions' => 'Actions',
        ],
    ],

    'form' => [
        'create' => 'New documentation',
        'edit' => 'Edit documentation',
        'description' => 'Compose internal documentation with a structured editor.',

        'label' => [
            'title' => 'Title',
            'slug' => 'Slug',
            'category' => 'Category',
            'status' => 'Status',
        ],

        'placeholder' => [
            'category' => 'Select a category',
            'slug' => 'Generated from the title',
        ],

        'message' => [
            'discard' => 'Discard unsaved changes?',
        ],
    ],

    'search' => [
        'title' => 'Global search',
        'description' => 'Search application navigation and internal documentation.',
        'placeholder' => 'Search navigation or documentation…',
        'empty' => 'No results.',

        'group' => [
            'navigation' => 'Navigation',
            'documentation' => 'Documentation',
        ],
    ],

    'validation' => [
        'invalid_content' => 'The documentation content structure is invalid.',
        'invalid_heading' => 'Documentation only supports heading levels 1 through 3.',
        'invalid_link' => 'Links must use HTTP, HTTPS, or an internal path.',
        'category_in_use' => 'The category still contains documentation. Move or delete those documents first.',
    ],
];
