<?php
declare(strict_types=1);

require_once __DIR__ . '/../../../src/http.php';
api_headers();
require_method(['POST']);

try {
    $data = read_json_body();
    $reporter = required_string($data, 'reporter_name', 150);
    $contact = required_string($data, 'contact_number', 40);
    $type = required_string($data, 'incident_type', 100);
    $description = required_string($data, 'description', 10000);
    $latitude = $data['latitude'] ?? null;
    $longitude = $data['longitude'] ?? null;

    if (!is_numeric($latitude) || !is_numeric($longitude)
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
        'barangay' => isset($data['barangay']) ? trim((string)$data['barangay']) : null,
        'address' => isset($data['address']) ? trim((string)$data['address']) : null,
        'longitude' => (float)$longitude,
        'latitude' => (float)$latitude,
    ]);
    json_response(['success' => true, 'message' => 'Incident report created.', 'data' => $stmt->fetch()], 201);
} catch (Throwable $e) {
    error_log('Incident create failed: ' . $e->getMessage());
    json_response(['success' => false, 'error' => 'Could not save incident report. Check database setup.'], 500);
}
