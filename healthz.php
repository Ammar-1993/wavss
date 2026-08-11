<?php
// Simple lightweight health check endpoint for Docker and infrastructure monitoring
require_once __DIR__ . '/scanner/functions/databaseFunctions.php';

try {
    if (connectToDb($db)) {
        http_response_code(200);
        echo "OK";
    } else {
        http_response_code(503);
        echo "DB unavailable";
    }
} catch (Exception $e) {
    http_response_code(503);
    echo "DB unavailable";
}
