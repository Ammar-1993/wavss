<?php

require_once(__DIR__ . '/databaseFunctions.php');

function createJsonReport($testId) {
    global $db;
    if (!connectToDb($db)) {
        return json_encode(['error' => 'Database connection failed']);
    }

    // Fetch test details
    $stmt = $db->prepare("SELECT url, start_timestamp, finish_timestamp FROM tests WHERE id = ?");
    $stmt->bind_param("i", $testId);
    $stmt->execute();
    $testRes = $stmt->get_result();
    
    if ($testRes->num_rows === 0) {
        $stmt->close();
        return json_encode(['error' => 'Test not found']);
    }
    
    $test = $testRes->fetch_object();
    $stmt->close();

    $report = [
        'test_id' => $testId,
        'url' => $test->url,
        'start_timestamp' => $test->start_timestamp,
        'finish_timestamp' => $test->finish_timestamp,
        'duration' => $test->finish_timestamp - $test->start_timestamp,
        'findings' => []
    ];

    // Fetch findings
    $stmt = $db->prepare("SELECT type, method, url, attack_str, ai_note FROM test_results WHERE test_id = ?");
    $stmt->bind_param("i", $testId);
    $stmt->execute();
    $vulnRes = $stmt->get_result();

    while ($row = $vulnRes->fetch_object()) {
        $aiNoteData = json_decode($row->ai_note, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            $row->ai_note = $aiNoteData;
        }
        $report['findings'][] = $row;
    }
    $stmt->close();

    return json_encode($report, JSON_PRETTY_PRINT);
}
