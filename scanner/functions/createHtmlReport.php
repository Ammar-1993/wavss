<?php

require_once(__DIR__ . '/databaseFunctions.php');

function createHtmlReport($testId) {
    global $db;
    if (!connectToDb($db)) return "Database connection failed";

    // Fetch test details
    $stmt = $db->prepare("SELECT url, start_timestamp, finish_timestamp FROM tests WHERE id = ?");
    $stmt->bind_param("i", $testId);
    $stmt->execute();
    $testRes = $stmt->get_result();
    
    if ($testRes->num_rows === 0) {
        $stmt->close();
        return "Test not found";
    }
    
    $test = $testRes->fetch_object();
    $stmt->close();

    $duration = $test->finish_timestamp - $test->start_timestamp;
    $mins = intval($duration / 60);
    $seconds = $duration % 60;
    
    $html = "<!DOCTYPE html><html><head><meta charset='utf-8'><title>WAVSS Scan Report - Test $testId</title>";
    $html .= "<style>body { font-family: Arial, sans-serif; line-height: 1.6; margin: 20px; } ";
    $html .= "h1, h2, h3, h4 { color: #333; } .vuln-card { border: 1px solid #ccc; padding: 15px; margin-bottom: 20px; background: #f9f9f9; }";
    $html .= ".instance { background: #fff; border-left: 4px solid #d9534f; padding: 10px; margin-top: 10px; }";
    $html .= ".ai-note { background: #e9f7ef; border-left: 4px solid #28a745; padding: 10px; margin-top: 10px; }";
    $html .= "</style></head><body>";
    
    $html .= "<h1>WAVSS Detailed HTML Report</h1>";
    $html .= "<h2>Summary</h2>";
    $html .= "<ul>";
    $html .= "<li><strong>Test ID:</strong> $testId</li>";
    $html .= "<li><strong>Target Site:</strong> " . htmlspecialchars($test->url) . "</li>";
    $html .= "<li><strong>Start Time:</strong> " . date('l jS F Y h:i:s A', $test->start_timestamp) . "</li>";
    $html .= "<li><strong>Finish Time:</strong> " . date('l jS F Y h:i:s A', $test->finish_timestamp) . "</li>";
    $html .= "<li><strong>Duration:</strong> $mins minutes and $seconds seconds</li>";
    $html .= "</ul>";

    $html .= "<h2>Vulnerabilities Found</h2>";

    $stmt = $db->prepare("SELECT type, method, url, attack_str, ai_note FROM test_results WHERE test_id = ?");
    $stmt->bind_param("i", $testId);
    $stmt->execute();
    $vulnRes = $stmt->get_result();

    $findings = [];
    while ($row = $vulnRes->fetch_object()) {
        $findings[$row->type][] = $row;
    }
    $stmt->close();

    if (empty($findings)) {
        $html .= "<p>No vulnerabilities found.</p>";
    } else {
        foreach ($findings as $type => $instances) {
            $stmt = $db->prepare("SELECT name, description, solution, priority FROM vulnerabilities WHERE id = ?");
            $stmt->bind_param("s", $type);
            $stmt->execute();
            $metaRes = $stmt->get_result();
            if ($metaRes->num_rows > 0) {
                $meta = $metaRes->fetch_object();
                $html .= "<div class='vuln-card'>";
                $html .= "<h3>" . htmlspecialchars($meta->name) . "</h3>";
                $html .= "<p><strong>Priority:</strong> " . htmlspecialchars($meta->priority) . "</p>";
                $html .= "<p><strong>Description:</strong> " . stripslashes($meta->description) . "</p>";
                $html .= "<p><strong>Recommendations:</strong> " . stripslashes($meta->solution) . "</p>";
                $html .= "<h4>Instances Found:</h4>";

                foreach ($instances as $inst) {
                    $html .= "<div class='instance'>";
                    $html .= "<strong>URL:</strong> " . htmlspecialchars($inst->url) . "<br>";
                    $html .= "<strong>Method:</strong> " . strtoupper(htmlspecialchars($inst->method)) . "<br>";
                    $html .= "<strong>Attack/Reference:</strong> " . htmlspecialchars($inst->attack_str) . "<br>";
                    
                    if ($inst->ai_note) {
                        $html .= "<div class='ai-note'><strong>🤖 AI-Assisted Note:</strong><br>";
                        $noteData = json_decode($inst->ai_note, true);
                        if ($noteData && isset($noteData['confidence']) && isset($noteData['explanation'])) {
                            $html .= "<i>Confidence (True Positive): " . htmlspecialchars($noteData['confidence']) . "</i><br>";
                            $html .= "<i>" . htmlspecialchars($noteData['explanation']) . "</i>";
                        } else {
                            $html .= "<i>" . htmlspecialchars($inst->ai_note) . "</i>";
                        }
                        $html .= "</div>";
                    }
                    $html .= "</div>";
                }
                $html .= "</div>";
            }
            $stmt->close();
        }
    }

    $html .= "</body></html>";
    return $html;
}
?>
