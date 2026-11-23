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

try {
    $limit = filter_var($_GET['limit'] ?? 50, FILTER_VALIDATE_INT);
    $offset = filter_var($_GET['offset'] ?? 0, FILTER_VALIDATE_INT);
    $limit = max(1, min(100, $limit === false ? 50 : $limit));
    $offset = max(0, $offset === false ? 0 : $offset);
    $status = isset($_GET['status']) && is_string($_GET['status']) ? trim($_GET['status']) : '';

    $sql = 'SELECT id, incident_type, barangay, status, created_at, updated_at,
                   ROUND(ST_Y(location::geometry)::numeric, 2) AS latitude,
                   ROUND(ST_X(location::geometry)::numeric, 2) AS longitude
            FROM incidents';
    $params = [];
    if ($status !== '') {
        $allowedStatuses = ['pending', 'verified', 'responding', 'resolved', 'cancelled'];
        if (!in_array($status, $allowedStatuses, true)) {
            json_response(['success' => false, 'error' => 'Invalid incident status.'], 422);
        }
        $sql .= ' WHERE status = :status';
        $params[':status'] = $status;
    }
    $sql .= ' ORDER BY created_at DESC LIMIT :limit OFFSET :offset';

    $pdo = require __DIR__ . '/../../../src/db_connection.php';
    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value, PDO::PARAM_STR);
    }
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    json_response([
        'success' => true,
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'pagination' => ['limit' => $limit, 'offset' => $offset],
    ]);
} catch (Throwable $e) {
    error_log('Incident list failed: ' . $e->getMessage());
    json_response(['success' => false, 'error' => 'Could not load incident reports. Check database setup.'], 500);
}

