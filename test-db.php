<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

try {
    $pdo = require __DIR__ . '/src/db_connection.php';
    $row = $pdo->query("SELECT PostGIS_Full_Version() AS postgis, to_regclass('public.incidents') AS incidents_table")->fetch();
    echo "PostgreSQL connection: OK
";
    echo "PostGIS: " . ($row['postgis'] ?? 'available') . "
";
    if (empty($row['incidents_table'])) {
        fwrite(STDERR, "Incidents table is missing. Run src/schema.sql against this database.
");
        exit(1);
    }
    echo "Incidents table: OK
";
} catch (Throwable $e) {
    fwrite(STDERR, "Database check failed. Verify DB settings and that PostGIS is enabled.
");
    exit(1);
}
