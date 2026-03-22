<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Content Generator</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <div class="header-left">
    <div class="header-icon">✦</div>
    <div>
      <div class="header-title">Content Generator</div>
      <div class="header-sub">Transform content into newsletter articles and marketing posts</div>
    </div>
  </div>
  <nav>
    <a href="index.php" class="active">Content Generator</a>
    <a href="system-review.php">System Review</a>
  </nav>
</header>

<main>

  <div class="card">
    <h2>Source Content</h2>
    <p class="desc">Provide a website URL, PDF link, or paste plain text to generate content</p>

    <div class="source-toggle">
      <button class="btn btn-primary" id="btn-url" onclick="setSource('url')">🔗 Website URL</button>
      <button class="btn btn-outline" id="btn-pdf" onclick="setSource('pdf')">📄 PDF Link</button>
      <button class="btn btn-outline" id="btn-text" onclick="setSource('text')">📝 Plain Text</button>
    </div>

    <div class="field">
      <label id="source-label">Website URL</label>
      <input type="text" id="source-input" placeholder="https://example.com/article" />
      <div id="source-textarea-wrap" style="display:none">
        <textarea id="source-textarea" rows="10" class="mono" placeholder="Paste or type your content here..."></textarea>
      </div>
    </div>

    <div class="field">
      <label>Gemini API Key</label>
      <input type="password" id="api-key" placeholder="Enter your Gemini API key" />
      <p class="hint">Get your API key from <a href="https://aistudio.google.com/apikey" target="_blank">Google AI Studio</a></p>
    </div>
  </div>

  <div class="center" style="margin-bottom:24px">
    <button class="btn btn-primary btn-lg" id="generate-btn" onclick="generate()">
      ✦ Generate Content
    </button>
  </div>

  <div id="error-area"></div>

  <div class="card">
    <h2>Customize Prompts</h2>
    <p class="desc">Edit the prompts to control how your content is generated</p>

    <div class="field">
      <div class="prompt-header">
        <div>
          <div class="prompt-title">Newsletter Article</div>
          <div class="prompt-sub">Generate a comprehensive newsletter article</div>
        </div>
      </div>
      <textarea id="newsletter-prompt" rows="8" class="mono"></textarea>
    </div>

    <div class="field">
      <div class="prompt-header">
        <div>
          <div class="prompt-title">Pre-Publish Marketing Post</div>
          <div class="prompt-sub">Announce the upcoming newsletter</div>
        </div>
      </div>
      <textarea id="pre-publish-prompt" rows="8" class="mono"></textarea>
    </div>

    <div class="field">
      <div class="prompt-header">
        <div>
          <div class="prompt-title">Publish Day Marketing Post</div>
          <div class="prompt-sub">Promote the published newsletter</div>
        </div>
      </div>
      <textarea id="publish-prompt" rows="8" class="mono"></textarea>
    </div>
  </div>

  <div id="results" style="display:none">
    <div class="card" id="title-card">
      <h2>Email Title &amp; Subtitle</h2>
      <p class="desc">Optimized subject line and preview text to boost open rates</p>
      <div class="title-preview">
        <div class="title-row">
          <span class="title-label">Subject</span>
          <span class="title-value" id="result-title"></span>
          <button class="btn btn-outline btn-sm" onclick="copyText('result-title')">Copy</button>
        </div>
        <div class="title-row">
          <span class="title-label">Preview</span>
          <span class="title-value" id="result-subtitle"></span>
          <button class="btn btn-outline btn-sm" onclick="copyText('result-subtitle')">Copy</button>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Generated Content</h2>
      <div class="tabs">
        <div class="tab-buttons">
          <button class="tab-btn active" onclick="switchTab('newsletter')">Newsletter</button>
          <button class="tab-btn" onclick="switchTab('pre-publish')">Pre-Publish Post</button>
          <button class="tab-btn" onclick="switchTab('publish')">Publish Post</button>
        </div>

        <div id="tab-newsletter" class="tab-content active">
          <div class="flex-end" style="margin-bottom:8px">
            <button class="btn btn-outline btn-sm" onclick="copyText('result-newsletter')">Copy</button>
          </div>
          <div class="result-box" id="result-newsletter"></div>
        </div>
        <div id="tab-pre-publish" class="tab-content">
          <div class="flex-end" style="margin-bottom:8px">
            <button class="btn btn-outline btn-sm" onclick="copyText('result-pre-publish')">Copy</button>
          </div>
          <div class="result-box" id="result-pre-publish"></div>
        </div>
        <div id="tab-publish" class="tab-content">
          <div class="flex-end" style="margin-bottom:8px">
            <button class="btn btn-outline btn-sm" onclick="copyText('result-publish')">Copy</button>
          </div>
          <div class="result-box" id="result-publish"></div>
        </div>
      </div>
    </div>
  </div>

</main>

<script>
const NEWSLETTER_PROMPT = `Role: You are a Senior Staff Machine Learning Engineer writing for a technical audience.

Objective: Write a high-signal newsletter article based on the Topic/Notes provided below. IMPORTANT: You are commenting on and analyzing the work of others (papers, blog posts, technical articles). This is NOT about work you or your team did. You are an engineer providing analysis and commentary on external work.

Audience Profile: ML Engineers and Software engineers. They understand the jargon (e.g., embeddings, latency, inference), so do not explain basic terms.

Voice & Tone:
- Write like a human engineer analyzing and commenting on others' work. No AI-speak, no overly formal language, no marketing fluff.
- Be direct and conversational. Write from the perspective of an engineer examining papers, blog posts, or technical articles.
- NEVER use "we did this" or "we built this" - you are always commenting on external work. Use phrases like "they found", "the paper shows", "the authors describe", "this approach", etc.
- Every sentence should add value. Cut all filler words, buzzwords, and unnecessary adjectives.
- Focus on how things work and why it matters. Use clear, logical explanations of the architecture or mechanics from an analytical perspective.

Formatting Requirements:
- DO NOT use markdown formatting. No asterisks for bold (**text**), no asterisks for bullet points (*), no markdown headers (#).
- Write plain text that can be copied directly into Substack or LinkedIn.
- Use line breaks for paragraphs and sections.
- For lists, use simple line breaks or dashes without markdown syntax.

Article Structure:
- Title: Descriptive and professional.
- TL;DR: Summarizing the main technical takeaways.
- Introduction: Set the context. What is the specific problem or landscape this topic addresses?
- Section 1: Explain the core concept. Break down the logic, architecture, or methodology. Use analogies if they clarify complex systems, but keep them technical.
- Section 2: Discuss the "So what?" How does this affect production environments, performance, or workflow? (e.g., Is it faster? Cheaper? More accurate? Harder to deploy? Whatever you deem important).`;

const PRE_PUBLISH_PROMPT = `Role: You are a Technical Lead. You are succinct and value-driven.

Objective: Write a social media post teasing an upcoming newsletter article based on the Topic listed. IMPORTANT: The newsletter is about analyzing and commenting on others' work (papers, blog posts, technical articles), not about work you did.

Constraints:
- Do not summarize the whole article.
- Focus on the "Why": Highlight a common pain point, a misconception, or a specific challenge that the article addresses.
- Write like a human, not an AI. Be conversational and direct. No corporate speak or overly formal language.
- Remember: You are commenting on external work, not describing your own work. Use language that reflects analyzing others' research or technical content.
- Length: Under 280 characters if possible, or short-form text (LinkedIn style).

Formatting Requirements:
- DO NOT use markdown formatting. No asterisks for bold (**text**), no asterisks for bullet points (*).
- Write plain text that can be copied directly into Substack or LinkedIn.

Structure:
- The Hook: A direct statement about the problem or the topic.
- The Promise: "Something like: In the next newsletter, I break down exactly how [Concept] handles this..."
- Call to Action: "Subscribe to not miss it, link to the article below".`;

const PUBLISH_PROMPT = `Role: You are an expert sharing knowledge with peers.


Objective: Create a launch post for the newsletter article based on the Content below. IMPORTANT: The newsletter analyzes and comments on others' work (papers, blog posts, technical articles). You are sharing insights from analyzing external work, not describing your own work.


Strategy: The goal is to provide value in the post itself (Zero-Click content) which convinces them the full article is high-quality.

Guidelines:
- Extract the Insight: Find the most interesting fact, architectural decision, or result from the content you analyzed.
- No Clickbait: Do not use "You won't believe what happened." Be specific.
- Write like a human engineer sharing insights from analyzing others' work. Be conversational, direct, and authentic. Avoid AI-sounding phrases.
- NEVER use "we did this" or "we built this" - always frame it as analysis of external work. Use "they found", "the paper shows", "this approach", etc.
- Tone: Helpful, technical, authoritative but approachable.

Formatting Requirements:
- DO NOT use markdown formatting. No asterisks for bold (**text**), no asterisks for bullet points (*).
- Write plain text that can be copied directly into Substack or LinkedIn.
- Use line breaks for separation, not markdown bullets.

Structure:
- Headline: A strong technical statement or question regarding the topic.
- The "Meat": 2-3 quick, high-impact takeaways from the article. (e.g., "Found that X approach reduced latency by 20%")
- The Cliffhanger: Mention one deeper nuance that is only covered in the full text.
- Call to Action: "Read the full deep dive in the newsletter, link below".`;

let sourceType = 'url';

// Init
document.getElementById('newsletter-prompt').value = NEWSLETTER_PROMPT;
document.getElementById('pre-publish-prompt').value = PRE_PUBLISH_PROMPT;
document.getElementById('publish-prompt').value = PUBLISH_PROMPT;

// Restore API key
const savedKey = localStorage.getItem('gemini-api-key');
if (savedKey) document.getElementById('api-key').value = savedKey;
document.getElementById('api-key').addEventListener('input', function() {
  localStorage.setItem('gemini-api-key', this.value);
});

function setSource(type) {
  sourceType = type;
  ['url','pdf','text'].forEach(t => {
    document.getElementById('btn-' + t).className = 'btn ' + (t === type ? 'btn-primary' : 'btn-outline');
  });
  const isText = type === 'text';
  document.getElementById('source-input').style.display = isText ? 'none' : 'block';
  document.getElementById('source-textarea-wrap').style.display = isText ? 'block' : 'none';
  document.getElementById('source-label').textContent = type === 'url' ? 'Website URL' : type === 'pdf' ? 'PDF Link' : 'Plain Text Content';
  if (!isText) {
    document.getElementById('source-input').placeholder = type === 'url' ? 'https://example.com/article' : 'https://example.com/document.pdf';
  }
}

function getSourceInput() {
  if (sourceType === 'text') return document.getElementById('source-textarea').value.trim();
  return document.getElementById('source-input').value.trim();
}

async function generate() {
  const sourceInput = getSourceInput();
  const apiKey = document.getElementById('api-key').value.trim();
  if (!sourceInput || !apiKey) return;

  const btn = document.getElementById('generate-btn');
  btn.disabled = true;
  btn.textContent = '⟳ Generating...';
  document.getElementById('error-area').innerHTML = '';
  document.getElementById('results').style.display = 'none';

  try {
    const resp = await fetch('api/generate-content.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        sourceType,
        sourceInput,
        apiKey,
        newsletterPrompt: document.getElementById('newsletter-prompt').value,
        prePublishPrompt: document.getElementById('pre-publish-prompt').value,
        publishPrompt: document.getElementById('publish-prompt').value,
      })
    });

    const data = await resp.json();
    if (!resp.ok) throw new Error(data.error || 'Failed to generate content');

    document.getElementById('result-title').textContent = data.title || '';
    document.getElementById('result-subtitle').textContent = data.subtitle || '';
    document.getElementById('result-newsletter').textContent = data.newsletter;
    document.getElementById('result-pre-publish').textContent = data.prePublishPost;
    document.getElementById('result-publish').textContent = data.publishPost;
    document.getElementById('results').style.display = 'block';
    document.getElementById('results').scrollIntoView({behavior: 'smooth'});
  } catch(e) {
    document.getElementById('error-area').innerHTML = '<div class="error-box">' + escHtml(e.message) + '</div>';
  } finally {
    btn.disabled = false;
    btn.textContent = '✦ Generate Content';
  }
}

function switchTab(name) {
  document.querySelectorAll('.tab-btn').forEach((b,i) => {
    const names = ['newsletter','pre-publish','publish'];
    b.className = 'tab-btn' + (names[i] === name ? ' active' : '');
  });
  document.querySelectorAll('.tab-content').forEach(el => el.className = 'tab-content');
  document.getElementById('tab-' + name).className = 'tab-content active';
}

function copyText(id) {
  navigator.clipboard.writeText(document.getElementById(id).textContent);
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
