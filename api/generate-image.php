<?php
header('Content-Type: application/json');
set_time_limit(90);

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$apiKey        = $data['apiKey']        ?? '';
$reviewArticle = $data['reviewArticle'] ?? '';

if (!$apiKey || !$reviewArticle) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing apiKey or reviewArticle']);
    exit;
}

$articleSnippet = substr($reviewArticle, 0, 4000);
if (strlen($reviewArticle) > 4000) $articleSnippet .= "\n\n[... article continues ...]";

$imagePrompt = <<<PROMPT
Role: You are a Senior Technical Architect creating a minimal, high-fidelity system diagram for a technical newsletter. Your output will be used directly in publication. Quality and clarity over completeness.

---

DESIGN PHILOSOPHY (internalize before drawing):
- Whitespace is a feature. Every element needs room to breathe.
- No icons. No decorative elements. Rounded rectangles and arrows only.
- If it feels crowded, remove elements until it doesn't.
- Max 6 nodes per panel. Node labels only — no descriptions inside boxes.
- Metrics and issues live in a clean card below the flow diagram, never inside it.
- When in doubt, simplify.

---

OUTPUT SPEC:
Format: PNG at 2x resolution
Canvas: 1600 x 900px
Background: #0F1117
Divider: 1px vertical line, color #2A2A2A, at x=800
Padding: 60px all edges, 70px vertical spacing between nodes

Typography (strict):
- Panel titles: Bold, 22px, #FFFFFF
- Node labels: Medium, 13px, #FFFFFF
- Stats labels: Regular, 12px, #9CA3AF
- Stats values: Bold, 14px (colored per panel)
- Minimum font size: 12px. Never go smaller.

---

YOUR TASK:
Read the System Review Article provided below. Extract the following automatically:

FROM THE "Architecture Overview" SECTION:
→ Identify the pipeline nodes (max 6). Use short labels only (2-5 words max per node).
→ Identify any branching (parallel paths) and where they reconverge.
→ Identify the single biggest bottleneck node — this gets a heavier border (2px, stronger fill).

FROM THE "Current Performance" AND "Costs" SECTIONS:
→ Extract exactly 3-4 metrics for the BEFORE stats card (latency, reliability, cost).

FROM THE "Analysis" SECTION:
→ Extract the top 3 critical issues as short bullet points (max 10 words each).

FROM THE "WHAT I WOULD DO INSTEAD" SECTION:
→ Derive the AFTER pipeline (max 6 nodes). Use short labels.
→ Identify any new stages (gates, filters, routing) that didn't exist before.

FROM THE "Impact" SECTION:
→ Extract exactly 3-4 projected metrics for the AFTER stats card.
→ Extract the top 3 solutions as short bullet points (max 10 words each).

---

LEFT PANEL — "BEFORE: [System Name] Legacy Architecture"
Color theme: #EF4444 (red)

Node style:
- Rounded rectangle, radius 6px
- Border: 1.5px solid #EF4444 at 70% opacity
- Fill: #EF4444 at 6% opacity
- Size: 200px wide, 44px tall
- Center column: x=400

Bottleneck node: border 2px solid #EF4444, fill at 15% opacity

Arrows: 1px, #EF4444 at 50% opacity, straight lines, small arrowheads

Stats Card (below the flow):
- Rounded rect container, border 1px #2A2A2A, fill #161B22, padding 20px
- Two-column layout: metric name (gray) | value (red #EF4444)
- Below stats: "CRITICAL ISSUES" label in #EF4444, then 3 bullet points in #9CA3AF

---

RIGHT PANEL — "AFTER: [System Name] Optimized Architecture"
Color theme: #10B981 (mint green)

Node style:
- Rounded rectangle, radius 6px
- Border: 1.5px solid #10B981 at 70% opacity
- Fill: #10B981 at 6% opacity
- Size: 200px wide, 44px tall
- Center column: x=1200

New gate/filter nodes (confidence gates, routing layers): use #06B6D4 (cyan) instead of green to visually distinguish them.

Arrows: 1px, #10B981 at 50% opacity, straight lines, small arrowheads

Stats Card (below the flow):
- Rounded rect container, border 1px #2A2A2A, fill #161B22, padding 20px
- Two-column layout: metric name (gray) | value (green #10B981)
- Below stats: "SOLUTIONS" label in #10B981, then 3 bullet points in #9CA3AF

---

LAYOUT RULES:
- Both panels must have the same vertical rhythm. Align stats cards to the same y-position.
- Branch nodes (parallel paths) sit on the same horizontal row, evenly spaced.
- Never overlap elements. If the flow doesn't fit, remove the least important node.
- The divider line runs the full canvas height.

---
SYSTEM REVIEW ARTICLE:

{$articleSnippet}

Generate the before/after infographic image now.
PROMPT;

$model = 'gemini-3-pro-image-preview';
$url   = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);
$body  = json_encode([
    'contents' => [['parts' => [['text' => $imagePrompt]]]],
    'generationConfig' => ['responseModalities' => ['TEXT', 'IMAGE']],
]);

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
if ($resp === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to reach Gemini API']);
    exit;
}

$json = json_decode($resp, true);
if (isset($json['error'])) {
    http_response_code(500);
    echo json_encode(['error' => $json['error']['message'] ?? 'Gemini API error']);
    exit;
}

// Find inline image data in parts
$imageData = null;
$parts = $json['candidates'][0]['content']['parts'] ?? [];
foreach ($parts as $part) {
    if (isset($part['inlineData']['data'])) {
        $imageData = $part['inlineData']['data'];
        break;
    }
}

if (!$imageData) {
    http_response_code(500);
    echo json_encode(['error' => 'No image data in response']);
    exit;
}

echo json_encode(['image' => $imageData]);
