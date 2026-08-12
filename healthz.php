<?php
// Simple lightweight health check endpoint for Docker and infrastructure monitoring
require_once __DIR__ . '/scanner/functions/databaseFunctions.php';

try {
    if (connectToDb($db)) {
        // Run a simple query to ensure seed.sql has completed
        $res = $db->query("SELECT 1 FROM users LIMIT 1");
        if ($res !== false) {
            http_response_code(200);
            echo "OK";
        } else {
            http_response_code(503);
            echo "DB seeding in progress";
        }
    } else {
        http_response_code(503);
        echo "DB unavailable";
    }
} catch (Exception $e) {
    http_response_code(503);
    echo "DB unavailable";
}
