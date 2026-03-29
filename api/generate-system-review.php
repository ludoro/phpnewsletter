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

// --- Mode 1: generate marketing post from existing review article ---
if ($reviewArticle) {
    if (!$publishPrompt) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing publishPrompt']);
        exit;
    }

    $summary = strlen($reviewArticle) > 3000
        ? substr($reviewArticle, 0, 3000) . "\n\n[... article continues ...]"
        : $reviewArticle;

    $pubPrompt = str_contains($publishPrompt, '[The full system review article will be provided here]')
        ? str_replace('[The full system review article will be provided here]', $summary, $publishPrompt)
        : $publishPrompt . "\n\nSystem Review Article:\n" . $summary;

    $pubResult = gemini_call($apiKey, $fastModel, $pubPrompt);

    if (isset($pubResult['error'])) {
        http_response_code(500);
        echo json_encode(['error' => $pubResult['error']]);
        exit;
    }

    echo json_encode(['marketingPost' => $pubResult['text']]);
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

I write about ML systems in production — the tradeoffs, the architecture decisions, the stuff that doesn't make it into papers. If you want to go deeper, the paid tier covers the technical details I can't fit in free posts.

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
Trade-offs:
[List only the cons that genuinely matter — could be one, could be several]
[When this is the wrong call: only include if there is a real scenario worth flagging]

2. [Solution 2 Title]
Current: [Old flow]
New: [New flow]
Impact: [Metric before] → [Metric after]
Trade-offs:
[List only the cons that genuinely matter]
[When this is the wrong call: only include if relevant]

3. [Solution 3 Title]
Replace: [Old approach] ([$ cost])
With: [New approach]
Total: [$ new total]
Trade-offs:
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
7. Do NOT use any markdown anywhere in the article — no #, no ##, no asterisks, no bold, no bullet dashes, no code fences. Section titles and subsection titles must be plain text on their own line.
8. Write plain text for all body content. Use line breaks for spacing between paragraphs.
9. Keep the tone direct, technical, and analytical but conversational
10. Include specific latencies, costs, scale numbers throughout
11. For the APPENDIX: Cost Estimation Methodology — show the actual arithmetic: unit cost × volume = total. Numbers must be internally consistent with the costs stated in the main article. State the key assumption and your confidence level honestly.
12. Trade-offs belong inside each solution in the "WHAT I'D DO INSTEAD" section, not in a separate section. Each solution ends with its own Trade-offs block. Be genuinely critical — list only the downsides that actually matter. Include a "When this is the wrong call" scenario only when there is a genuinely meaningful one. Do not soften or hedge, but do not pad with weak cons just to fill a list.
13. All issues in The Analysis section must be labeled "Critical Issue #N" — never use "Design Smell" or "Hidden Issue".
14. CRITICAL REQUIREMENT — Every single critical issue must be fully visible and traceable in the Architecture Overview section BEFORE The Analysis section names it. This is not about hiding clues — it is about laying out the facts plainly so that a technically engaged reader, working through the numbers and the architecture description, has everything they need to arrive at the diagnosis themselves. When The Analysis section names an issue, the reader's reaction must be "Yes, I saw that — that number didn't add up" or "Right, that dependency chain was obviously going to cause this", NOT "Oh, I never would have guessed that". The Architecture Overview must contain: the exact latency/cost/throughput figures that expose each bottleneck, the exact dependency or design decision that creates each failure mode, and enough detail that a reader tracing through the system flow can connect the dots independently. The Analysis section then confirms and articulates what the architecture already made obvious — it is the "Aha, exactly that" moment, not a reveal.
15. Never introduce a critical issue in The Analysis section that relies on information not present in the Architecture Overview. Every issue must be fully grounded in facts already stated.

Generate the complete system review article now:
PROMPT;

$result = gemini_call($apiKey, $fastModel, $reviewPrompt);

if (isset($result['error'])) {
    http_response_code(500);
    echo json_encode(['error' => $result['error']]);
    exit;
}

$article = $result['text'];

$titlePrompt = <<<PROMPT
You are a growth-obsessed technical newsletter editor for ML@Scale, a newsletter read by senior engineers and ML practitioners. Your titles routinely get 40%+ open rates because they are impossible to ignore.

Based on the system review article below, generate a newsletter title and subtitle that make engineers stop scrolling.

Title rules:
- Lead with a shocking number, cost, or metric pulled directly from the article (e.g. "$240K/month", "47ms", "3x slower", "14 engineers")
- Name the specific failure — not "an issue" but the actual thing that broke
- Create tension or contradiction ("looked fine", "nobody noticed", "passed all tests")
- Max 12 words. No markdown. No colons.
- Examples of the energy to aim for:
  "A $180K/Month Fraud Model That Was Silently Wrong for 6 Months"
  "How a 3-Line Config Change Took Down Real-Time Inference for 40M Users"
  "The Embedding Pipeline That Looked Fast Until It Hit 10K QPS"

Subtitle rules:
- One sentence. Expand on what makes the story technically juicy.
- Include a second concrete number or detail from the article if possible.
- End with what the reader will learn or walk away with — make it feel worth 5 minutes of their time.
- No markdown.

Generate TWO different title/subtitle variations — each should take a distinct angle (e.g. one leads with the cost failure, another leads with the architectural mistake or the timeline).

Output ONLY valid JSON in this exact format with no other text: {"title1": "...", "subtitle1": "...", "title2": "...", "subtitle2": "..."}

System Review Article:
{$article}
PROMPT;

$titleResult = gemini_call($apiKey, $fastModel, $titlePrompt);

$title1    = '';
$subtitle1 = '';
$title2    = '';
$subtitle2 = '';
if (!isset($titleResult['error'])) {
    $raw = trim($titleResult['text']);
    // strip markdown code fences if present
    $raw = preg_replace('/^```(?:json)?\s*/i', '', $raw);
    $raw = preg_replace('/\s*```$/', '', $raw);
    $parsed = json_decode($raw, true);
    if (isset($parsed['title1']))    $title1    = $parsed['title1'];
    if (isset($parsed['subtitle1'])) $subtitle1 = $parsed['subtitle1'];
    if (isset($parsed['title2']))    $title2    = $parsed['title2'];
    if (isset($parsed['subtitle2'])) $subtitle2 = $parsed['subtitle2'];
}

echo json_encode(['reviewArticle' => $article, 'title1' => $title1, 'subtitle1' => $subtitle1, 'title2' => $title2, 'subtitle2' => $subtitle2]);
