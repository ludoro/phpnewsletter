<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>System Review Generator</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <div class="header-left">
    <div class="header-icon">⚠</div>
    <div>
      <div class="header-title">MLSys System Review Generator</div>
      <div class="header-sub">Generate technical system reviews for ML infrastructure</div>
    </div>
  </div>
  <nav>
    <a href="index.php">Content Generator</a>
    <a href="system-review.php" class="active">System Review</a>
  </nav>
</header>

<main>

  <div class="card">
    <h2>System Context</h2>
    <p class="desc">Describe the industry, ML system, or key MLOps problem you want to analyze</p>

    <div class="field">
      <label>Industry / ML System / Problem Description</label>
      <textarea id="industry-context" rows="8" placeholder="E.g., A fintech company using real-time fraud detection with 10k req/sec, or a recommendation system for an e-commerce platform with 1M products..."></textarea>
      <p class="hint">Provide details about the company, scale, technology stack, current challenges, or any specific MLOps problems</p>
    </div>

    <div class="field">
      <label>Gemini API Key</label>
      <input type="password" id="api-key" placeholder="Enter your Gemini API key" />
      <p class="hint">Get your API key from <a href="https://aistudio.google.com/apikey" target="_blank">Google AI Studio</a></p>
    </div>

    <div class="center" style="padding-top:8px">
      <button class="btn btn-primary btn-lg" id="gen-newsletter-btn" onclick="generateNewsletter()">
        ✦ Generate Newsletter
      </button>
    </div>
  </div>

  <div class="card">
    <h2>Customize Prompts</h2>
    <p class="desc">Edit the prompts to control how your marketing posts are generated</p>

    <div class="field">
      <div class="prompt-header">
        <div>
          <div class="prompt-title">Pre-Publish Marketing Post</div>
          <div class="prompt-sub">Announce the upcoming system review</div>
        </div>
      </div>
      <textarea id="pre-publish-prompt" rows="8" class="mono"></textarea>
    </div>

    <div class="field">
      <div class="prompt-header">
        <div>
          <div class="prompt-title">Publish Day Marketing Post</div>
          <div class="prompt-sub">Promote the published system review</div>
        </div>
      </div>
      <textarea id="publish-prompt" rows="8" class="mono"></textarea>
    </div>
  </div>

  <div class="card" style="border-color:#3a2a5a; background:#1a1428">
    <h2>About System Reviews</h2>
    <p style="font-size:13px; color:#aaa; margin-top:8px; line-height:1.6">
      This generator creates detailed MLSys "fake reviews" following a structured template that analyzes
      system architecture, identifies critical issues, and proposes solutions.
    </p>
    <p style="font-size:13px; font-weight:600; margin-top:12px; color:#ccc;">The review includes:</p>
    <ul style="font-size:13px; color:#aaa; margin:6px 0 0 20px; line-height:1.8">
      <li>Architecture overview with traffic patterns and costs</li>
      <li>Critical issues analysis with specific metrics</li>
      <li>Proposed solutions with before/after comparisons</li>
      <li>Impact assessment and implementation timeline</li>
      <li>Pre-publish and publish day marketing posts</li>
    </ul>
  </div>

  <div id="error-area"></div>

  <!-- Newsletter Article (shown after generation) -->
  <div id="newsletter-section" style="display:none">
    <div class="card">
      <h2>Newsletter Article</h2>
      <p class="desc">Edit your newsletter article, then generate marketing posts</p>

      <div class="field">
        <label>Newsletter Content</label>
        <textarea id="review-article" rows="20" class="mono"></textarea>
      </div>

      <div class="flex-between">
        <div style="display:flex;gap:8px">
          <button class="btn btn-outline btn-sm" onclick="copyText('review-article-text')">Copy</button>
          <button class="btn btn-outline btn-sm" id="gen-image-btn" onclick="generateImage()">
            🖼 Generate Infographic
          </button>
        </div>
        <button class="btn btn-primary btn-lg" id="gen-posts-btn" onclick="generatePosts()">
          ✦ Generate Marketing Posts
        </button>
      </div>
    </div>
  </div>

  <!-- Generated Image -->
  <div id="image-section" style="display:none">
    <div class="card">
      <h2>Before/After Infographic</h2>
      <p class="desc">Generated infographic showing the system design review before and after</p>
      <div style="background:#111; border-radius:8px; padding:16px; display:flex; justify-content:center; margin-bottom:12px">
        <img id="infographic-img" class="infographic" alt="System Design Review Before/After" />
      </div>
      <div class="flex-end">
        <button class="btn btn-outline btn-sm" onclick="downloadImage()">Download</button>
      </div>
    </div>
  </div>

  <!-- Marketing Posts -->
  <div id="posts-section" style="display:none">
    <div class="card">
      <h2>Generated Marketing Posts</h2>
      <p class="desc">Your pre-publish and publish day marketing posts</p>
      <div class="tabs">
        <div class="tab-buttons">
          <button class="tab-btn active" onclick="switchTab('pre')">Pre-Publish Post</button>
          <button class="tab-btn" onclick="switchTab('pub')">Publish Post</button>
        </div>
        <div id="tab-pre" class="tab-content active">
          <div class="flex-end" style="margin-bottom:8px">
            <button class="btn btn-outline btn-sm" onclick="copyResult('result-pre')">Copy</button>
          </div>
          <div class="result-box" id="result-pre"></div>
        </div>
        <div id="tab-pub" class="tab-content">
          <div class="flex-end" style="margin-bottom:8px">
            <button class="btn btn-outline btn-sm" onclick="copyResult('result-pub')">Copy</button>
          </div>
          <div class="result-box" id="result-pub"></div>
        </div>
      </div>
    </div>
  </div>

</main>

<script>
const PRE_PUBLISH_PROMPT = `Role: You are a Technical Lead creating a LinkedIn post to tease an upcoming system review article. IMPORTANT: The system review analyzes and comments on others' work (papers, blog posts, technical articles about systems). You are an engineer providing analysis of external work, not describing your own work.

Based on the system review article below, create a short LinkedIn teaser post following this structure:

LinkedIn Post:

I'm reviewing a [system type] serving [scale metric] ([fictional case study, real architecture problems]).

The setup:
→ [Problem indicator 1]
→ [Problem indicator 2]

The problems I found:
→ [Root cause 1]: [specific numbers]
→ [Root cause 2]: [specific numbers]

The fix:
→ [Solution 1] to [benefit]
→ [Solution 2] ([before] → [after])

Full architecture breakdown coming in this week's ML@Scale:
[Link to newsletter]

---

[Engagement question]

---

P.S. Subscribe to not miss it.

System Review Article:
[The full system review article will be provided here]

Instructions:
1. Extract the key system type, scale, and problems from the review
2. Highlight 2-3 specific root causes with numbers
3. Summarize 2 key solutions and their impact
4. Keep it under 200 words
5. End with an engagement question
6. Use the arrow (→) format exactly as shown
7. Write like a human engineer, not an AI.
8. NEVER use "we did this" or "we built this" - frame it as analysis of external work.
9. DO NOT use markdown formatting.
10. Write plain text that can be copied directly into Substack or LinkedIn.`;

const PUBLISH_PROMPT = `Role: You are a Technical Lead creating a LinkedIn post to promote a system review article. IMPORTANT: The system review analyzes and comments on others' work (papers, blog posts, technical articles about systems). You are an engineer providing analysis of external work, not describing your own work.

Based on the system review article below, create a LinkedIn marketing post following this EXACT template:

LinkedIn Post:

I reviewed a [system type] serving [scale metric] ([fictional case study, real architecture problems]).

The setup:
→ [Problem indicator 1]
→ [Problem indicator 2]
→ [Problem indicator 3]

The symptoms:
→ [Incident 1]: [impact]
→ [Incident 2]: [recovery action]
→ [User experience issue]: [metric]

The problems:
→ [Root cause 1]: [specific numbers]
→ [Root cause 2]: [specific numbers]
→ [Root cause 3]: [specific numbers]
→ [Root cause 4]: [specific numbers]

The fix:
→ [Solution 1] to [benefit]
→ [Solution 2] ([before] → [after])
→ [Solution 3] ([old metric] → [new metric])
→ [Solution 4] ([frequency/approach change])

Impact: [X better metric], [Y% cost savings], scales to [Z metric].

Full architecture breakdown in this week's ML@Scale:
[Link to newsletter]

---

[Engagement question]

---

P.S. Next review: [Teaser for next one].

System Review Article:
[The full system review article will be provided here]

Instructions:
1. Extract the key system type, scale, and problems from the review
2. Highlight 3-4 specific root causes with numbers
3. Summarize the solutions and their impact
4. Keep it under 300 words
5. End with an engagement question
6. Use the arrow (→) format exactly as shown
7. Write like a human engineer, not an AI.
8. NEVER use "we did this" or "we built this".
9. DO NOT use markdown formatting.
10. Write plain text.`;

document.getElementById('pre-publish-prompt').value = PRE_PUBLISH_PROMPT;
document.getElementById('publish-prompt').value = PUBLISH_PROMPT;

const savedKey = localStorage.getItem('gemini-api-key');
if (savedKey) document.getElementById('api-key').value = savedKey;
document.getElementById('api-key').addEventListener('input', function() {
  localStorage.setItem('gemini-api-key', this.value);
});

let currentImageData = null;

async function generateNewsletter() {
  const industryContext = document.getElementById('industry-context').value.trim();
  const apiKey = document.getElementById('api-key').value.trim();
  if (!industryContext || !apiKey) return;

  const btn = document.getElementById('gen-newsletter-btn');
  btn.disabled = true;
  btn.textContent = '⟳ Generating...';
  document.getElementById('error-area').innerHTML = '';
  document.getElementById('newsletter-section').style.display = 'none';
  document.getElementById('posts-section').style.display = 'none';
  document.getElementById('image-section').style.display = 'none';

  try {
    const resp = await fetch('api/generate-system-review.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ industryContext, apiKey })
    });
    const data = await resp.json();
    if (!resp.ok) throw new Error(data.error || 'Failed to generate');

    document.getElementById('review-article').value = data.reviewArticle;
    document.getElementById('newsletter-section').style.display = 'block';
    document.getElementById('newsletter-section').scrollIntoView({behavior: 'smooth'});
  } catch(e) {
    document.getElementById('error-area').innerHTML = '<div class="error-box">' + escHtml(e.message) + '</div>';
  } finally {
    btn.disabled = false;
    btn.textContent = '✦ Generate Newsletter';
  }
}

async function generatePosts() {
  const reviewArticle = document.getElementById('review-article').value.trim();
  const apiKey = document.getElementById('api-key').value.trim();
  if (!reviewArticle || !apiKey) return;

  const btn = document.getElementById('gen-posts-btn');
  btn.disabled = true;
  btn.textContent = '⟳ Generating Posts...';
  document.getElementById('posts-section').style.display = 'none';

  try {
    const resp = await fetch('api/generate-system-review.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        reviewArticle,
        apiKey,
        prePublishPrompt: document.getElementById('pre-publish-prompt').value,
        publishPrompt: document.getElementById('publish-prompt').value,
      })
    });
    const data = await resp.json();
    if (!resp.ok) throw new Error(data.error || 'Failed to generate posts');

    document.getElementById('result-pre').textContent = data.prePublishPost;
    document.getElementById('result-pub').textContent = data.marketingPost;
    document.getElementById('posts-section').style.display = 'block';
    document.getElementById('posts-section').scrollIntoView({behavior: 'smooth'});
  } catch(e) {
    document.getElementById('error-area').innerHTML = '<div class="error-box">' + escHtml(e.message) + '</div>';
  } finally {
    btn.disabled = false;
    btn.textContent = '✦ Generate Marketing Posts';
  }
}

async function generateImage() {
  const reviewArticle = document.getElementById('review-article').value.trim();
  const apiKey = document.getElementById('api-key').value.trim();
  if (!reviewArticle || !apiKey) return;

  const btn = document.getElementById('gen-image-btn');
  btn.disabled = true;
  btn.textContent = '⟳ Generating...';
  document.getElementById('image-section').style.display = 'none';

  try {
    const resp = await fetch('api/generate-image.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ reviewArticle, apiKey })
    });
    const data = await resp.json();
    if (!resp.ok) throw new Error(data.error || 'Failed to generate image');

    currentImageData = data.image;
    document.getElementById('infographic-img').src = 'data:image/png;base64,' + data.image;
    document.getElementById('image-section').style.display = 'block';
    document.getElementById('image-section').scrollIntoView({behavior: 'smooth'});
  } catch(e) {
    document.getElementById('error-area').innerHTML = '<div class="error-box">' + escHtml(e.message) + '</div>';
  } finally {
    btn.disabled = false;
    btn.textContent = '🖼 Generate Infographic';
  }
}

function downloadImage() {
  if (!currentImageData) return;
  const a = document.createElement('a');
  a.href = 'data:image/png;base64,' + currentImageData;
  a.download = 'system-review-infographic.png';
  a.click();
}

function switchTab(name) {
  document.querySelectorAll('.tab-btn').forEach((b, i) => {
    b.className = 'tab-btn' + (['pre','pub'][i] === name ? ' active' : '');
  });
  ['pre','pub'].forEach(n => {
    document.getElementById('tab-' + n).className = 'tab-content' + (n === name ? ' active' : '');
  });
}

function copyText(id) {
  const el = document.getElementById(id) || document.getElementById('review-article');
  navigator.clipboard.writeText(el.value || el.textContent);
}

function copyResult(id) {
  navigator.clipboard.writeText(document.getElementById(id).textContent);
}

function escHtml(s) {
  return s.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}
</script>
</body>
</html>
