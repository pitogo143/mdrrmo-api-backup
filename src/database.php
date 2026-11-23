<?php
declare(strict_types=1);

// Sa Render, ibutang ang DATABASE_URL sa Environment settings.
// Ayaw ibutang ang tinuod nga password dinhi o i-upload sa GitHub.
$databaseUrl = getenv('DATABASE_URL');

if ($databaseUrl) {
    $parts = parse_url($databaseUrl);

    if ($parts === false) {
        throw new RuntimeException('Invalid DATABASE_URL.');
    }

    return [
        'host' => $parts['host'] ?? '',
        'port' => $parts['port'] ?? 5432,
        'dbname' => ltrim($parts['path'] ?? '', '/'),
        'user' => rawurldecode($parts['user'] ?? ''),
        'password' => rawurldecode($parts['pass'] ?? ''),
        'sslmode' => 'require',
    ];
}

// Local development settings
return [
    'host' => getenv('DB_HOST') ?: 'localhost',
    'port' => getenv('DB_PORT') ?: 5432,
    'dbname' => getenv('DB_NAME') ?: '',
    'user' => getenv('DB_USER') ?: '',
    'password' => getenv('DB_PASSWORD') ?: '',
    'sslmode' => getenv('DB_SSLMODE') ?: 'prefer',
];
