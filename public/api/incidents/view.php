<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

function json_response(array $body, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    header('Allow: GET, OPTIONS');
    json_response(['success' => false, 'error' => 'Method not allowed.'], 405);
}

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) {
    json_response(['success' => false, 'error' => 'A valid incident id is required.'], 400);
}

try {
    $pdo = require __DIR__ . '/../../../src/db_connection.php';
    $sql = 'SELECT id, incident_type, barangay, status, created_at, updated_at,
                   ROUND(ST_Y(location::geometry)::numeric, 2) AS latitude,
                   ROUND(ST_X(location::geometry)::numeric, 2) AS longitude
            FROM incidents
            WHERE id = :id';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['id' => $id]);
    $incident = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$incident) {
        json_response(['success' => false, 'error' => 'Incident not found.'], 404);
    }

    json_response(['success' => true, 'data' => $incident]);
} catch (Throwable $e) {
    error_log('Incident view failed: ' . $e->getMessage());
    json_response(['success' => false, 'error' => 'Could not load incident report. Check database setup.'], 500);
}

