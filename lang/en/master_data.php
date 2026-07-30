<?php

return [

    'layout' => [
        'title' => 'Master data',
        'description' => 'Reference records the application runs on.',
    ],

    'user' => [
        'title' => 'User',
        'description' => 'Application users, their role, and their access status.',

        'create' => [
            'title' => 'Add user',
            'description' => 'A new user starts active and signs in with the configured default password.',
        ],

        'edit' => [
            'title' => 'Edit user',
            'description' => 'Change the name, email, role, or access status of an existing user.',
        ],

        'label' => [
            'id' => 'ID',
            'name' => 'Name',
            'email' => 'Email',
            'role' => 'Role',
            'status' => 'Status',
            'created_at' => 'Created',
            'actions' => 'Actions',
        ],

        'placeholder' => [
            'name' => 'Full name',
            'email' => 'name@example.com',
            'role' => 'Select a role',
            'status' => 'Select a status',
            'search' => 'Search by name or email…',
        ],

        'button' => [
            'create' => 'Add user',
            'save' => 'Save',
            'cancel' => 'Cancel',
            'edit' => 'Edit',
            'export' => 'Export XLSX',
        ],

        'message' => [
            'created' => 'The user has been created.',
            'updated' => 'The user has been updated.',
        ],

        'empty' => [
            'title' => 'No users found',
            'description' => 'No user matches the current search.',
        ],

        'filter' => [
            'status' => 'Status',
            'role' => 'Role',
        ],

        'role_missing' => 'No role',
    ],

];
