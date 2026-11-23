<?php
declare(strict_types=1);
require_once __DIR__ . '/../../../src/http.php';
api_headers();
require_method(['GET']);
try {
    $pdo = require __DIR__ . '/../../../src/db_connection.php';
    $limit = filter_var($_GET['limit'] ?? 50, FILTER_VALIDATE_INT);
    $offset = filter_var($_GET['offset'] ?? 0, FILTER_VALIDATE_INT);
    $limit = max(1, min(100, $limit === false ? 50 : $limit));
    $offset = max(0, $offset === false ? 0 : $offset);
    $status = trim((string)($_GET['status'] ?? ''));
    $sql = 'SELECT id, reporter_name, contact_number, incident_type, description,
                   barangay, address, status, created_at, updated_at,
                   ST_Y(location::geometry) AS latitude, ST_X(location::geometry) AS longitude
            FROM incidents';
    $params = [];
    if ($status !== '') {
        $sql .= ' WHERE status = :status';
        $params['status'] = $status;
    }
    $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) $stmt->bindValue(':' . $key, $value, PDO::PARAM_STR);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    json_response(['success' => true, 'data' => $stmt->fetchAll(), 'pagination' => ['limit' => $limit, 'offset' => $offset]]);
} catch (Throwable $e) {
    error_log('Incident list failed: ' . $e->getMessage());
    json_response(['success' => false, 'error' => 'Could not load incident reports. Check database setup.'], 500);
}
