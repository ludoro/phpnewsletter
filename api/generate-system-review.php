<?php
header('Content-Type: application/json');
set_time_limit(90);

$raw  = file_get_contents('php://input');
$data = json_decode($raw, true);

$apiKey              = $data['apiKey']              ?? '';
$industryContext     = $data['industryContext']     ?? '';
$reviewArticle       = $data['reviewArticle']       ?? '';
$prePublishPrompt    = $data['prePublishPrompt']    ?? '';
$publishPrompt       = $data['publishPrompt']       ?? '';

if (!$apiKey) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing apiKey']);
    exit;
}

function gemini_call(string $apiKey, string $model, string $prompt): array {
    $url  = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . urlencode($apiKey);
    $body = json_encode(['contents' => [['parts' => [['text' => $prompt]]]]]);
    $ctx  = stream_context_create([
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
    if ($text === null) return ['error' => 'No text in Gemini response'];
    return ['text' => $text];
}

$fastModel    = 'gemini-3-flash-preview';
$detailModel  = 'gemini-3-pro-preview';

// --- Mode 1: generate marketing posts from existing review article ---
if ($reviewArticle) {
    if (!$prePublishPrompt || !$publishPrompt) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing prePublishPrompt or publishPrompt']);
        exit;
    }

    $summary = strlen($reviewArticle) > 3000
        ? substr($reviewArticle, 0, 3000) . "\n\n[... article continues ...]"
        : $reviewArticle;

    $prePrompt = str_contains($prePublishPrompt, '[The full system review article will be provided here]')
        ? str_replace('[The full system review article will be provided here]', $summary, $prePublishPrompt)
        : $prePublishPrompt . "\n\nSystem Review Article:\n" . $summary;

    $pubPrompt = str_contains($publishPrompt, '[The full system review article will be provided here]')
        ? str_replace('[The full system review article will be provided here]', $summary, $publishPrompt)
        : $publishPrompt . "\n\nSystem Review Article:\n" . $summary;

    $preResult = gemini_call($apiKey, $fastModel, $prePrompt);
    $pubResult = gemini_call($apiKey, $fastModel, $pubPrompt);

    if (isset($preResult['error'])) {
        http_response_code(500);
        echo json_encode(['error' => $preResult['error']]);
        exit;
    }
    if (isset($pubResult['error'])) {
        http_response_code(500);
        echo json_encode(['error' => $pubResult['error']]);
        exit;
    }

    echo json_encode([
        'prePublishPost' => $preResult['text'],
        'marketingPost'  => $pubResult['text'],
    ]);
    exit;
}

// --- Mode 2: generate the review article ---
if (!$industryContext) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing industryContext']);
    exit;
}

$template = <<<'TMPL'
The System

[Company Name] is a [stage/type] [industry] company that [recent milestone/context]. They've [growth metric or key achievement].

Their engineering team built a [system type] that [what it powers/does]. Here's their setup:

---

Architecture Overview

[Brief description of what happens when user/system triggers the flow]

[Component/Trigger]
    ↓
[Entry Point]
    ↓
[Main Service]
    ├→ [Dependency 1] - [latency/behavior]
    ├→ [Dependency 2] - [latency/behavior]
    └→ [Dependency N] - [latency/behavior]
    ↓
[Output/Response]

Traffic patterns:
[Scale metric 1]
[Scale metric 2]
Average: [X req/sec]
Peak: [Y req/sec]

The ML Pipeline:
[Description of model type, what it's trained on, key characteristics]

Current performance:
[Latency metric 1]
[Reliability metric]
[Business impact metric]

Costs:
[Cost category 1]: [$ amount]
Total: [$ total]

Recent incidents:
[Incident 1 with impact]
[Incident 2 with recovery details]

---

The Analysis

Now let me show you what's actually happening here.

Critical Issue #1: [Problem Title]
[Point to specific detail from architecture section]
[Calculate the impact with numbers]

Critical Issue #2: [Problem Title]
[Reference specific timeline or metric]

Critical Issue #3: [Problem Title]
[Walk through a typical flow]

Critical Issue #4: [Problem Title]
[Quote something suspicious from their setup]

Critical Issue #5: [Problem Title]
They have [surprising number] just for [seemingly simple task].

---

WHAT I'D DO INSTEAD

1. [Solution 1 Title]
Impact:
[Improvement 1 with metric]
[Improvement 2 with metric]

2. [Solution 2 Title]
Current: [Old flow]
New: [New flow]
Impact: [Metric before] → [Metric after]

3. [Solution 3 Title]
Replace: [Old approach] ([$ cost])
With: [New approach]
Total: [$ new total]

---

The Trade-offs

Every decision has a downside. Here's what you need to know before acting on any of this:

Solution 1: [Solution 1 Title]
[List only the cons that genuinely matter — could be one, could be several]
[When this is the wrong call: only include if there is a real scenario worth flagging]

Solution 2: [Solution 2 Title]
[List only the cons that genuinely matter]
[When this is the wrong call: only include if relevant]

Solution 3: [Solution 3 Title]
[List only the cons that genuinely matter]
[When this is the wrong call: only include if relevant]

---

The Impact

Before redesign:
[Capability constraint]
[$ cost]

After redesign:
[New capability]
[$ new cost] ([% savings])

Time to implement: [timeline], [team size]

---

The Lesson

The system already told them what's wrong:
[Incident 1] → [interpretation]
[Weird metric] → [interpretation]

They just need to listen to what the system is saying.

---

[Engagement question related to the trade-offs discussed]

---

APPENDIX: Cost Estimation Methodology

How I estimated the savings for each decision:

Solution 1: [Solution 1 Title]
Baseline: [Current cost/unit] × [current volume/month] = [$ current monthly cost]
After change: [New cost/unit] × [same or adjusted volume] = [$ new monthly cost]
Estimated saving: [$ difference/month] ([% reduction])
Key assumption: [The main assumption driving this estimate, e.g. cache hit rate, traffic pattern, model compression ratio]
Confidence: [High/Medium/Low] — [one sentence explaining uncertainty, e.g. "actual hit rate depends on query diversity"]

Solution 2: [Solution 2 Title]
Baseline: [Current cost breakdown, e.g. infra + ops labor]
After change: [New cost breakdown]
Estimated saving: [$ difference/month]
Key assumption: [Main assumption]
Confidence: [High/Medium/Low] — [one sentence]

Solution 3: [Solution 3 Title]
Baseline: [Current cost]
After change: [New cost]
Estimated saving: [$ difference/month]
Key assumption: [Main assumption]
Confidence: [High/Medium/Low] — [one sentence]

TMPL;

$reviewPrompt = <<<PROMPT
Role: You are a Senior Staff Machine Learning Engineer who specializes in system architecture reviews and critical analysis of production ML systems.

Objective: Write a detailed technical system review following the EXACT structure of the template provided below. This is a "fake review" or thought experiment - you should create realistic, plausible technical scenarios based on the industry/system context provided. IMPORTANT: You are analyzing and commenting on others' work. This is NOT about work you or your team did. NEVER use "we did this" or "we built this" - always frame it as analysis of external work.

Industry/System Context:
{$industryContext}

TEMPLATE TO FOLLOW:
{$template}

Instructions:
1. Replace ALL [bracketed placeholders] with realistic, specific technical details
2. Use real numbers, metrics, and costs that make sense for the industry/scale
3. Identify 4-5 critical architectural issues with specific technical explanations
4. Provide concrete solutions with before/after metrics
5. Make the company and scenario fictional but the technical problems REAL
6. Write like a human engineer, not an AI. Be direct, conversational, and authentic.
7. DO NOT use markdown formatting. No asterisks, no markdown headers (#).
8. Write plain text. Use line breaks for sections.
9. Keep the tone direct, technical, and analytical but conversational
10. Include specific latencies, costs, scale numbers throughout
11. For the APPENDIX: Cost Estimation Methodology — show the actual arithmetic: unit cost × volume = total. Numbers must be internally consistent with the costs stated in the main article. State the key assumption and your confidence level honestly.
12. For the Trade-offs section in the main text — be genuinely critical. List only the downsides that actually matter for each solution — not every solution needs the same number of cons. Include a "When this is the wrong call" scenario only when there is a genuinely meaningful one. Do not soften or hedge, but do not pad with weak cons just to fill a list.
13. All issues in The Analysis section must be labeled "Critical Issue #N" — never use "Design Smell" or "Hidden Issue".
14. CRITICAL REQUIREMENT — The core architectural issue must be planted and discoverable in the Architecture Overview section. A careful reader going through that section should be able to spot something that doesn't add up — a latency number that's inconsistent with the stated throughput, a dependency chain that introduces a hidden bottleneck, a cost figure that implies an unsustainable per-request overhead, or a flow step that silently makes the whole system brittle. The detail should be subtle enough that a skimming reader misses it, but clear enough that an engineer who traces through the numbers or the call path can identify it. When The Analysis section references this issue, it must explicitly point back to the specific line or metric in the Architecture Overview that revealed it.

Generate the complete system review article now:
PROMPT;

$result = gemini_call($apiKey, $fastModel, $reviewPrompt);

if (isset($result['error'])) {
    http_response_code(500);
    echo json_encode(['error' => $result['error']]);
    exit;
}

echo json_encode(['reviewArticle' => $result['text']]);
