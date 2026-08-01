<?php

return [

    'page' => [
        'title' => 'Chat',
        'description' => 'Direct messages with other people in the application',
    ],

    'label' => [
        'conversations' => 'Conversations',
        'messages' => 'Messages',
        'search' => 'Search people',
        'composer' => 'Message',
        'unavailable' => 'Unavailable',
        'read' => 'Read',
        'sent' => 'Sent',
        'delivered' => 'Delivered',
        'you' => 'Sent',
        'reconnecting' => 'Reconnecting',
        'image' => 'Image',
        'image_preview' => 'Full-size chat image preview',
        'uploading' => 'Uploading image',
        'today' => 'Today',
        'yesterday' => 'Yesterday',
    ],

    'placeholder' => [
        'search' => 'Search by name',
        'composer' => 'Write a message',
    ],

    'button' => [
        'send' => 'Send',
        'new' => 'New message',
        'load_older' => 'Load older messages',
        'jump_to_latest' => 'Jump to latest',
        'minimize' => 'Minimize chat',
        'expand' => 'Expand chat',
        'close' => 'Close chat',
        'retry' => 'Try again',
        'add_image' => 'Add image',
        'remove_image' => 'Remove image',
        'preview_image' => 'Preview image',
        'react' => 'React to message',
    ],

    'empty' => [
        'conversations' => 'No conversations yet.',
        'conversations_description' => 'Start one by searching for a name.',
        'messages' => 'No messages yet.',
        'messages_description' => 'The conversation starts with the first message.',
        'search' => 'No matching people.',
        'search_hint' => 'Type at least two characters.',
        'unselected' => 'Select a conversation.',
        'unselected_description' => 'Pick a conversation on the left, or start a new one.',
    ],

    'message' => [
        'disabled' => 'Chat is currently turned off.',
        'recipient_unavailable' => 'That person cannot receive messages.',
        'peer_unavailable' => 'This person can no longer receive messages. The history stays available.',
        'rate_limited' => 'Too many messages were sent. Wait a moment and try again.',
        'send_failed' => 'The message was not sent.',
        'reconnected' => 'Connection restored.',
        'image_upload_disabled' => 'Image uploads are currently disabled.',
        'image_removed_disabled' => 'The selected image was removed because image uploads were disabled.',
    ],

    'notification' => [
        'message' => 'Sent a direct message.',
    ],

    'audit' => [
        'title' => 'Chat audit',
        'description' => 'Read-only record of every direct message',
        'label' => [
            'participants' => 'Participants',
            'messages' => 'Messages',
            'last_activity' => 'Last activity',
            'access_log' => 'Access log',
            'search' => 'Search participants',
            'viewer' => 'Opened by',
            'viewed_at' => 'Opened at',
            'ip_address' => 'IP address',
            'user_agent' => 'User agent',
        ],
        'placeholder' => [
            'search' => 'Search by participant name',
        ],
        'button' => [
            'open' => 'Open',
            'back' => 'Back to audit',
        ],
        'empty' => [
            'conversations' => 'No conversations recorded.',
            'search' => 'No conversation matches that participant.',
            'messages' => 'No messages in this conversation.',
            'logs' => 'No previous access recorded.',
        ],
        'notice' => 'Opening a conversation is recorded permanently and stays invisible to its participants.',
    ],

];
