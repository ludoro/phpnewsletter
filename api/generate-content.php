<?php
header('Content-Type: application/json');
set_time_limit(90);

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

$sourceType    = $data['sourceType']      ?? '';
$sourceInput   = $data['sourceInput']     ?? '';
$apiKey        = $data['apiKey']          ?? '';
$newsletterPrompt  = $data['newsletterPrompt']  ?? '';
$prePublishPrompt  = $data['prePublishPrompt']  ?? '';
$publishPrompt     = $data['publishPrompt']     ?? '';

if (!$sourceInput || !$apiKey || !$newsletterPrompt || !$prePublishPrompt || !$publishPrompt) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

function gemini_call(string $apiKey, string $model, array $contents): array {
    $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);
    $body = json_encode(['contents' => $contents]);
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n",
            'content' => $body,
            'timeout' => 60,
            'ignore_errors' => true,
        ]
    ]);
    $resp = file_get_contents($url, false, $ctx);
    if ($resp === false) return ['error' => 'Failed to reach Gemini API'];
    $json = json_decode($resp, true);
    if (isset($json['error'])) return ['error' => $json['error']['message'] ?? 'Gemini API error'];
    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;
    if ($text === null) return ['error' => 'No text in response'];
    return ['text' => $text];
}

function fetch_url(string $url): array {
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'GET',
            'header'  => implode("\r\n", [
                'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
                'Accept: text/html,application/xhtml+xml,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.9',
            ]),
            'timeout' => 30,
            'follow_location' => 1,
            'ignore_errors' => true,
        ]
    ]);
    $html = @file_get_contents($url, false, $ctx);
    if ($html === false || $html === '') return ['error' => 'Failed to fetch URL or empty response'];
    return ['html' => $html];
}

function extract_main_content(string $html): string {
    // Strip scripts and styles
    $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/is', '', $html);
    $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/is', '', $html);

    // Try main content areas
    if (preg_match('/<main[^>]*>(.*?)<\/main>/is', $html, $m)) $html = $m[1];
    elseif (preg_match('/<article[^>]*>(.*?)<\/article>/is', $html, $m)) $html = $m[1];

    // Strip tags, clean whitespace
    $text = strip_tags($html);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    return substr($text, 0, 30000);
}

$model = 'gemini-3-pro-preview';
$isPdf = ($sourceType === 'pdf');
$isText = ($sourceType === 'text');

// --- PDF path ---
if ($isPdf) {
    $newsletterResult = gemini_call($apiKey, $model, [[
        'parts' => [
            ['text' => $newsletterPrompt],
            ['fileData' => ['fileUri' => $sourceInput, 'mimeType' => 'application/pdf']],
        ]
    ]]);
    if (isset($newsletterResult['error'])) {
        http_response_code(500);
        echo json_encode(['error' => $newsletterResult['error']]);
        exit;
    }
    $newsletter = $newsletterResult['text'];
    $summary = substr($newsletter, 0, 2000);

    $preResult  = gemini_call($apiKey, $model, [[
        'parts' => [['text' => $prePublishPrompt . "\n\nNewsletter Content Summary:\n" . $summary]]
    ]]);
    $pubResult  = gemini_call($apiKey, $model, [[
        'parts' => [['text' => $publishPrompt . "\n\nNewsletter Content:\n" . $summary]]
    ]]);

    if (isset($preResult['error']) || isset($pubResult['error'])) {
        http_response_code(500);
        echo json_encode(['error' => $preResult['error'] ?? $pubResult['error']]);
        exit;
    }
    echo json_encode([
        'newsletter'      => $newsletter,
        'prePublishPost'  => $preResult['text'],
        'publishPost'     => $pubResult['text'],
    ]);
    exit;
}

// --- Text path ---
if ($isText) {
    $sourceContent = trim($sourceInput);
    if (!$sourceContent) {
        http_response_code(400);
        echo json_encode(['error' => 'Plain text content cannot be empty']);
        exit;
    }
    $sourceContent = substr($sourceContent, 0, 50000);
} else {
    // URL path
    $fetched = fetch_url($sourceInput);
    if (isset($fetched['error'])) {
        http_response_code(400);
        echo json_encode(['error' => $fetched['error']]);
        exit;
    }
    $sourceContent = extract_main_content($fetched['html']);
    if (!$sourceContent) {
        http_response_code(400);
        echo json_encode(['error' => 'Could not extract content from URL. The page may require JavaScript.']);
        exit;
    }
}

// Generate newsletter
$newsletterResult = gemini_call($apiKey, $model, [[
    'parts' => [['text' => $newsletterPrompt . "\n\nSource Content:\n" . $sourceContent]]
]]);
if (isset($newsletterResult['error'])) {
    http_response_code(500);
    echo json_encode(['error' => $newsletterResult['error']]);
    exit;
}
$newsletter = $newsletterResult['text'];
$summary = substr($newsletter, 0, 2000);

$preResult = gemini_call($apiKey, $model, [[
    'parts' => [['text' => $prePublishPrompt . "\n\nNewsletter Content Summary:\n" . $summary]]
]]);
$pubResult = gemini_call($apiKey, $model, [[
    'parts' => [['text' => $publishPrompt . "\n\nNewsletter Content:\n" . $summary]]
]]);

if (isset($preResult['error']) || isset($pubResult['error'])) {
    http_response_code(500);
    echo json_encode(['error' => $preResult['error'] ?? $pubResult['error']]);
    exit;
}

echo json_encode([
    'newsletter'      => $newsletter,
    'prePublishPost'  => $preResult['text'],
    'publishPost'     => $pubResult['text'],
]);
