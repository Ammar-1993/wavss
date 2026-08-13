<?php

// Optional AI triage function
// Returns JSON string with rating and explanation, or null on failure/disabled
function triageResult($type, $url, $attackStr)
{
    $apiKey = getenv('AI_API_KEY');
    if (empty($apiKey)) {
        return null;
    }

    $prompt = "You are an expert security analyst assisting in vulnerability triage.
A vulnerability scanner found a potential issue.
Type: $type
URL: $url
Attack String: $attackStr

Please evaluate this finding.
1. Rate confidence (High, Medium, Low) that this is a true positive vs a false positive.
2. Provide a one-sentence plain-language explanation of the finding and its remediation.

Output strictly as JSON:
{
  \"confidence\": \"...\",
  \"explanation\": \"...\"
}";

    $endpoint = getenv('AI_API_ENDPOINT') ?: 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;
    
    $payload = json_encode([
        "contents" => [
            ["parts" => [["text" => $prompt]]]
        ],
        "generationConfig" => [
            "response_mime_type" => "application/json"
        ]
    ]);

    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Fail fast to not block scan
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        return null;
    }

    $data = json_decode($response, true);
    $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (empty($text)) {
        return null;
    }
    
    $jsonObj = json_decode($text, true);
    if (json_last_error() === JSON_ERROR_NONE && isset($jsonObj['confidence'])) {
        return json_encode($jsonObj);
    }
    
    return null;
}
