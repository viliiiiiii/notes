<?php
return [
    'db' => [
        'host' => '127.0.0.1',
        'database' => 'notes_app',
        'user' => 'notes_user',
        'password' => 'notes_pass',
        'charset' => 'utf8mb4',
    ],
    'minio' => [
        'endpoint' => 'http://127.0.0.1:9000',
        'key' => 'minioadmin',
        'secret' => 'minioadmin',
        'bucket' => 'notes-attachments',
        'region' => 'us-east-1',
    ],
    'app' => [
        'session_name' => 'notes_app_session',
        'timezone' => 'UTC',
    ],
];
