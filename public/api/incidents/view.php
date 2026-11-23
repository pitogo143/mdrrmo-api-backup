<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/http.php';
api_headers();
require_method(['GET']);
$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if ($id === false || $id === null || $id < 1) json_response(['success' => false, 'error' => 'A valid incident id is required.'], 400);
try {
    $pdo = require __DIR__ . '/../../../src/db_connection.php';
    $stmt = $pdo->prepare('SELECT id, reporter_name, contact_number, incident_type, description, barangay, address, status, created_at, updated_at, ST_Y(location::geometry) AS latitude, ST_X(location::geometry) AS longitude FROM incidents WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $incident = $stmt->fetch();
    if (!$incident) json_response(['success' => false, 'error' => 'Incident not found.'], 404);
    json_response(['success' => true, 'data' => $incident]);
} catch (Throwable $e) {
    error_log('Incident view failed: ' . $e->getMessage());
    json_response(['success' => false, 'error' => 'Could not load incident report. Check database setup.'], 500);
}
