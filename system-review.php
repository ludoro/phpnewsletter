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
      <li>Publish day marketing post</li>
    </ul>
  </div>

  <div id="error-area"></div>

  <!-- Newsletter Article (shown after generation) -->
  <div id="newsletter-section" style="display:none">
    <div class="card">
      <h2>Newsletter Article</h2>
      <p class="desc">Edit your newsletter article, then generate marketing posts</p>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#a78bfa;margin-bottom:10px">Variation A</div>
          <div class="field">
            <label>Title A</label>
            <input type="text" id="review-title1" class="mono" />
          </div>
          <div class="field">
            <label>Subtitle A</label>
            <input type="text" id="review-subtitle1" class="mono" />
          </div>
        </div>
        <div>
          <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;color:#a78bfa;margin-bottom:10px">Variation B</div>
          <div class="field">
            <label>Title B</label>
            <input type="text" id="review-title2" class="mono" />
          </div>
          <div class="field">
            <label>Subtitle B</label>
            <input type="text" id="review-subtitle2" class="mono" />
          </div>
        </div>
      </div>

      <div class="field">
        <label>Newsletter Content</label>
        <textarea id="review-article" rows="20" class="mono"></textarea>
      </div>

      <div class="flex-between">
        <button class="btn btn-outline btn-sm" onclick="copyText('review-article-text')">Copy</button>
        <button class="btn btn-primary btn-lg" id="gen-posts-btn" onclick="generatePosts()">
          ✦ Generate Marketing Post
        </button>
      </div>
    </div>
  </div>

  <!-- Marketing Post -->
  <div id="posts-section" style="display:none">
    <div class="card">
      <h2>Generated Marketing Post</h2>
      <p class="desc">Your publish day marketing post</p>
      <div class="flex-end" style="margin-bottom:8px">
        <button class="btn btn-outline btn-sm" onclick="copyResult('result-pub')">Copy</button>
      </div>
      <div class="result-box" id="result-pub"></div>
    </div>
  </div>

</main>

<script>
const PUBLISH_PROMPT = `Using the newsletter content provided, write a publish-day social media post that follows this exact structure and tone:

I reviewed a [system type] serving [scale metric].

It had [X visible symptoms]. Nobody connected them.

The real problem was [root cause 1] plus [root cause 2] interacting in a way the team didn't model.

→ [Specific bad outcome with number]
→ [Second bad outcome with number]
→ [Third that shows the scope]

The fix isn't obvious. It required rethinking [core assumption].

Full architecture teardown in this week's ML@Scale:
[Link]

Rules:
- Fill in the bracketed placeholders using specifics from the newsletter content.
- Keep [Link] as a literal placeholder — do not invent a URL.
- Do not add any other text, intro, or explanation outside this structure.
- No markdown formatting. Plain text only.`;

document.getElementById('publish-prompt').value = PUBLISH_PROMPT;

const savedKey = localStorage.getItem('gemini-api-key');
if (savedKey) document.getElementById('api-key').value = savedKey;
document.getElementById('api-key').addEventListener('input', function() {
  localStorage.setItem('gemini-api-key', this.value);
});

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

  try {
    const resp = await fetch('api/generate-system-review.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ industryContext, apiKey })
    });
    const data = await resp.json();
    if (!resp.ok) throw new Error(data.error || 'Failed to generate');

    document.getElementById('review-title1').value    = data.title1    || '';
    document.getElementById('review-subtitle1').value = data.subtitle1 || '';
    document.getElementById('review-title2').value    = data.title2    || '';
    document.getElementById('review-subtitle2').value = data.subtitle2 || '';
    document.getElementById('review-article').value  = data.reviewArticle;
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
  btn.textContent = '⟳ Generating Post...';
  document.getElementById('posts-section').style.display = 'none';

  try {
    const resp = await fetch('api/generate-system-review.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({
        reviewArticle,
        apiKey,
        publishPrompt: document.getElementById('publish-prompt').value,
      })
    });
    const data = await resp.json();
    if (!resp.ok) throw new Error(data.error || 'Failed to generate post');

    document.getElementById('result-pub').textContent = data.marketingPost;
    document.getElementById('posts-section').style.display = 'block';
    document.getElementById('posts-section').scrollIntoView({behavior: 'smooth'});
  } catch(e) {
    document.getElementById('error-area').innerHTML = '<div class="error-box">' + escHtml(e.message) + '</div>';
  } finally {
    btn.disabled = false;
    btn.textContent = '✦ Generate Marketing Post';
  }
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
