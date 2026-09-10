<?php

return [
    'owner_email' => env('RBAC_OWNER_EMAIL'),

    'roles' => ['Owner', 'Admin', 'Editor', 'Viewer'],

    'permissions' => [
        'entries.view', 'entries.create', 'entries.update', 'entries.delete',
        'categories.view', 'categories.create', 'categories.update', 'categories.delete',
        'tags.view', 'tags.create', 'tags.update', 'tags.delete',
        'documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.download',
        'users.view', 'users.update',
        'roles.view', 'roles.manage',
        'permissions.view',
        'messages.view', 'messages.send', 'messages.manage',
        'activity.view',
    ],

    'role_permissions' => [
        'Owner' => '*',
        'Admin' => [
            'entries.view', 'entries.create', 'entries.update', 'entries.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'tags.view', 'tags.create', 'tags.update', 'tags.delete',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.download',
            'users.view', 'users.update', 'roles.view', 'roles.manage', 'permissions.view',
            'messages.view', 'messages.send', 'messages.manage', 'activity.view',
        ],
        'Editor' => [
            'entries.view', 'entries.create', 'entries.update', 'entries.delete',
            'categories.view', 'categories.create', 'categories.update', 'categories.delete',
            'tags.view', 'tags.create', 'tags.update', 'tags.delete',
            'documents.view', 'documents.create', 'documents.update', 'documents.delete', 'documents.download',
            'messages.view', 'messages.send',
        ],
        'Viewer' => [
            'entries.view', 'categories.view', 'tags.view', 'documents.view', 'documents.download',
            'messages.view', 'messages.send',
        ],
    ],
];
