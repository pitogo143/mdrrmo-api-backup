<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
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

function read_json_body(): array
{
    $data = json_decode(file_get_contents('php://input'), true);
    if (!is_array($data)) {
        json_response(['success' => false, 'error' => 'A valid JSON object is required.'], 400);
    }
    return $data;
}

function required_string(array $data, string $key, int $maxLength): string
{
    $value = $data[$key] ?? null;
    if (!is_string($value) || trim($value) === '') {
        json_response(['success' => false, 'error' => ucfirst(str_replace('_', ' ', $key)) . ' is required.'], 422);
    }

    $value = trim($value);
    $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    if ($length > $maxLength) {
        json_response(['success' => false, 'error' => ucfirst(str_replace('_', ' ', $key)) . ' is too long.'], 422);
    }
    return $value;
}

function optional_string(array $data, string $key, int $maxLength): ?string
{
    $value = $data[$key] ?? null;
    if ($value === null || $value === '') {
        return null;
    }
    if (!is_string($value)) {
        json_response(['success' => false, 'error' => ucfirst(str_replace('_', ' ', $key)) . ' must be text.'], 422);
    }

    $value = trim($value);
    $length = function_exists('mb_strlen') ? mb_strlen($value) : strlen($value);
    if ($length > $maxLength) {
        json_response(['success' => false, 'error' => ucfirst(str_replace('_', ' ', $key)) . ' is too long.'], 422);
    }
    return $value === '' ? null : $value;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST, OPTIONS');
    json_response(['success' => false, 'error' => 'Method not allowed.'], 405);
}

try {
    $data = read_json_body();
    $reporter = required_string($data, 'reporter_name', 150);
    $contact = required_string($data, 'contact_number', 40);
    $type = required_string($data, 'incident_type', 100);
    $description = required_string($data, 'description', 10000);
    $barangay = optional_string($data, 'barangay', 120);
    $address = optional_string($data, 'address', 2000);
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;

    if (!is_numeric($latitude) || !is_numeric($longitude)
        || !is_finite((float)$latitude) || !is_finite((float)$longitude)
        || (float)$latitude < -90 || (float)$latitude > 90
        || (float)$longitude < -180 || (float)$longitude > 180) {
        json_response(['success' => false, 'error' => 'Valid latitude and longitude are required.'], 422);
    }

    $pdo = require __DIR__ . '/../../../src/db_connection.php';
    $sql = 'INSERT INTO incidents
        (reporter_name, contact_number, incident_type, description, barangay, address, location)
        VALUES (:reporter, :contact, :type, :description, :barangay, :address,
                ST_SetSRID(ST_MakePoint(:longitude, :latitude), 4326)::geography)
        RETURNING id, reporter_name, contact_number, incident_type, description,
                  barangay, address, status, created_at,
                  ST_Y(location::geometry) AS latitude, ST_X(location::geometry) AS longitude';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'reporter' => $reporter,
        'contact' => $contact,
        'type' => $type,
        'description' => $description,
        'barangay' => $barangay,
        'address' => $address,
        'longitude' => (float)$longitude,
        'latitude' => (float)$latitude,
    ]);

    json_response(['success' => true, 'data' => $stmt->fetch(PDO::FETCH_ASSOC)], 201);
} catch (Throwable $e) {
    error_log('Incident creation failed: ' . $e->getMessage());
    json_response(['success' => false, 'error' => 'Could not create the incident report. Check database setup.'], 500);
}
