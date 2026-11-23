<?php
declare(strict_types=1);
require_once __DIR__ . '/../../src/http.php';
api_headers();
require_method(['GET']);
try {
    $pdo = require __DIR__ . '/../../src/db_connection.php';
    $row = $pdo->query("SELECT current_database() AS database, PostGIS_Full_Version() AS postgis")->fetch();
    json_response(['success' => true, 'service' => 'mdrrmo-api', 'database' => 'connected', 'postgis' => $row['postgis'] ?? 'enabled']);
} catch (Throwable $e) {
    error_log('Health check failed: ' . $e->getMessage());
    json_response(['success' => false, 'service' => 'mdrrmo-api', 'database' => 'unavailable'], 503);
}
