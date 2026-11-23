<?php
declare(strict_types=1);

$config = require __DIR__ . '/database.php';

$dsn = sprintf(
    'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
    $config['host'],
    $config['port'],
    $config['dbname'],
    $config['sslmode']
);

try {
    return new PDO(
        $dsn,
        $config['user'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    error_log($e->getMessage());
    throw new RuntimeException('Database connection failed. Check the database settings.');
}
