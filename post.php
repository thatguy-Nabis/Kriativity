<?php
require_once 'init.php';
require_once 'printer.php';
require_once 'config/database.php';

$post_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($post_id <= 0) {
  header('Location: homepage.php');
  exit;
}

try {
  $stmt = $pdo->prepare("
    SELECT c.*, u.username, u.full_name, u.profile_image 
    FROM content c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.id = :id AND c.is_published = 1
    ");
  $stmt->execute([':id' => $post_id]);
$post = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$post) {
    header('Location: homepage.php');
    exit;
  }

  $pdo->prepare("UPDATE content SET views = views + 1 WHERE id = :id")
    ->execute([':id' => $post_id]);
  $post['views']++;

} catch (PDOException $e) {
  error_log($e->getMessage());
  header('Location: homepage.php');
  exit;
}

$user_has_liked = false;
if (isset($_SESSION['user_id'])) {
  $stmt = $pdo->prepare("SELECT id FROM likes WHERE user_id=? AND content_id=?");
  $stmt->execute([$_SESSION['user_id'], $post_id]);
  $user_has_liked = (bool) $stmt->fetch();
}

$related_posts = [];
$stmt = $pdo->prepare("
    SELECT c.*, u.username 
    FROM content c 
    JOIN users u ON c.user_id = u.id
    WHERE c.category = ? AND c.id != ? AND c.is_published = 1
    ORDER BY RAND() LIMIT 4
");
$stmt->execute([$post['category'], $post_id]);
$related_posts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($post['title']) ?> – Kriativity</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <link rel="stylesheet" href="styles.css">
  <link rel="stylesheet" href="styles/post.css">

  <style>

  </style>
</head>

<body>

  <?php include 'header.php'; ?>

  <div class="post-container">
    <!-- breadcrumb -->
    <div class="post-breadcrumb">
      <a href="homepage.php">Home</a> / <?= htmlspecialchars($post['category']) ?>
    </div>

    <div class="post-header">
      <h1 class="post-title"><?= htmlspecialchars($post['title']) ?></h1>

      <div class="post-meta">
        <div class="post-author">
          <div class="author-avatar">
            <?php if (!empty($post['profile_image'])): ?>
              <img src="<?= htmlspecialchars($post['profile_image']) ?>" alt="Author">
            <?php else: ?>
              <?= strtoupper($post['full_name'][0] ?? 'U') ?>
            <?php endif; ?>
          </div>
          <div>
            <div class="author-name"><?= htmlspecialchars($post['full_name']) ?></div>
            <div class="author-username">@<?= htmlspecialchars($post['username']) ?></div>
          </div>
        </div>

        <div class="post-stats">
          👁️ <?= number_format($post['views']) ?>
          ❤️ <span id="likeCount"><?= number_format($post['likes']) ?></span>
        </div>

        <div class="post-category"><?= htmlspecialchars($post['category']) ?></div>
      </div>
    </div>

    <div class="post-main-content">
      <?php if ($post['content_type'] === 'video' && $post['image_url']): ?>
    <div class="video-player-wrap" id="videoWrap">
        <video id="mainVideo"
               src="<?= htmlspecialchars($post['image_url']) ?>"
               poster="<?= htmlspecialchars($post['thumbnail_url'] ?? '') ?>"
               preload="metadata"
               playsinline></video>

        <div class="video-controls" id="videoControls">
            <div class="vc-progress-bar" id="progressBar">
                <div class="vc-progress-fill" id="progressFill"></div>
                <div class="vc-progress-thumb" id="progressThumb"></div>
            </div>
            <div class="vc-bottom">
                <button class="vc-btn" id="playPauseBtn" title="Play / Pause">▶</button>
                <span class="vc-time" id="vcTime">0:00 / 0:00</span>
                <button class="vc-btn vc-mute" id="muteBtn" title="Mute">🔊</button>
                <button class="vc-btn vc-full" id="fullBtn" title="Fullscreen">⛶</button>
            </div>
        </div>

        <!-- big play overlay -->
        <div class="video-big-play" id="bigPlay">▶</div>
    </div>
<?php elseif ($post['image_url']): ?>
    <img src="<?= htmlspecialchars($post['image_url']) ?>" class="post-image" alt="<?= htmlspecialchars($post['title']) ?>">
<?php endif; ?>
    </div>
    <div id="reportModal" class="modal hidden" onclick="closeReportModal()">
  <div class="modal-box" onclick="event.stopPropagation()">
    <h3>Report Content</h3>

    <select id="reportType">
      <option value="">Select reason</option>
      <option value="spam">Spam</option>
      <option value="harassment">Harassment</option>
      <option value="inappropriate">Inappropriate</option>
      <option value="copyright">Copyright</option>
      <option value="other">Other</option>
    </select>

    <textarea id="reportDescription" placeholder="Describe the issue..." rows="4"></textarea>

    <div style="text-align:right;">
      <button onclick="submitReport()">Submit</button>
      <button onclick="closeReportModal()">Cancel</button>
    </div>
  </div>
</div>

<!-- ✅ COMMENTS SECTION OUTSIDE -->
<div class="comments-section">
  <h2 class="section-title">Comments</h2>

  <?php if (isset($_SESSION['user_id'])): ?>
    <div class="comment-input-box">
      <textarea id="commentInput" placeholder="Write a comment..."></textarea>
      <button id="postCommentBtn">Post</button>
    </div>
  <?php else: ?>
    <p><a href="login.php">Login</a> to comment</p>
  <?php endif; ?>

  <div id="commentsContainer"></div>
  <div id="commentsLoading" style="text-align:center;margin:1rem;">
    Loading comments...
  </div>
</div>



    <?php if ($related_posts): ?>
      <div class="related-section">
        <h2 class="section-title">Related Content</h2>
        <div class="related-grid">
          <?php foreach ($related_posts as $r): ?>
            <div class="related-card" onclick="location.href='post.php?id=<?= $r['id'] ?>'">
              <?php if ($r['image_url']): ?>
                <img src="<?= htmlspecialchars($r['image_url']) ?>" class="related-card-image">
              <?php else: ?>
                <div class="related-card-image"></div>
              <?php endif; ?>
              <div class="related-card-content">
                <div class="related-card-title"><?= htmlspecialchars($r['title']) ?></div>
                <div class="related-card-stats">
                  <span>👁️ <?= number_format($r['views']) ?></span>
                  <span>❤️ <?= number_format($r['likes']) ?></span>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <?php include 'footer.php'; ?>

  <div id="notification" class="notification"></div>

<script>
  const likeBtn = document.getElementById('likeBtn');
  const likeIcon = document.getElementById('likeIcon');
  const likeText = document.getElementById('likeText');
  const likeCount = document.getElementById('likeCount');
  const notification = document.getElementById('notification');

  const CURRENT_USER_ID = <?= isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : 'null' ?>;

  let isLiked = <?= $user_has_liked ? 'true' : 'false' ?>;
  let currentLikes = <?= (int)$post['likes'] ?>;

  // =========================
  // LIKE HANDLER
  // =========================
  if (likeBtn) {
    likeBtn.addEventListener('click', async () => {
      <?php if (!isset($_SESSION['user_id'])): ?>
        showNotification('Please login to like posts', 'error');
        setTimeout(() => window.location.href = 'login.php', 1500);
        return;
      <?php endif; ?>

      likeBtn.disabled = true;

      try {
        const response = await fetch('handlers/like_handler.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            post_id: <?= $post_id ?>,
            action: 'toggle'
          })
        });

        if (!response.ok) throw new Error('Network error');

        const result = await response.json();

        if (result.success) {
          isLiked = !!result.liked;

          likeBtn.classList.toggle('liked', isLiked);
          likeIcon.textContent = isLiked ? '❤️' : '🤍';
          likeText.textContent = isLiked ? 'Liked' : 'Like';

          likeCount.textContent = Number(result.likes || 0).toLocaleString();
        } else {
          showNotification(result.message || 'Something went wrong', 'error');
        }

      } catch (error) {
        console.error(error);
        showNotification('Failed to process like.', 'error');
      } finally {
        likeBtn.disabled = false;
      }
    });
  }

  // =========================
  // SHARE
  // =========================
  function sharePost() {
    const url = window.location.href;

    if (navigator.share) {
      navigator.share({
        title: <?= json_encode($post['title']) ?>,
        url
      }).catch(() => {});
    } else {
      navigator.clipboard.writeText(url).then(() => {
        showNotification('Link copied!', 'success');
      });
    }
  }

  // =========================
  // REPORT MODAL
  // =========================
  let reportTargetType = null;
  let reportTargetId = null;

  function openReportModal(type, id) {
    reportTargetType = type;
    reportTargetId = id;
    document.getElementById('reportModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
  }

  function closeReportModal() {
    document.getElementById('reportModal').classList.add('hidden');
    document.body.style.overflow = '';
  }

  async function submitReport() {
    const type = document.getElementById('reportType').value;
    const description = document.getElementById('reportDescription').value.trim();

    if (!type || !description) {
      showNotification('Please fill all fields', 'error');
      return;
    }

    try {
      const res = await fetch('handlers/report_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          target_type: reportTargetType,
          target_id: reportTargetId,
          report_type: type,
          description
        })
      });

      const data = await res.json();
      showNotification(data.message, data.success ? 'success' : 'error');
      closeReportModal();

    } catch (err) {
      console.error(err);
      showNotification('Report failed', 'error');
    }
  }

  // =========================
  // NOTIFICATIONS
  // =========================
  function showNotification(message, type = 'success') {
    notification.textContent = message;
    notification.className = `notification ${type} show`;

    setTimeout(() => {
      notification.classList.remove('show');
    }, 3000);
  }

  // =========================
  // ESCAPE HTML
  // =========================
  function escapeHtml(text) {
    return text
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#039;");
  }

  // =========================
  // COMMENTS
  // =========================
  const commentsContainer = document.getElementById('commentsContainer');
  const commentsLoading = document.getElementById('commentsLoading');
  const postCommentBtn = document.getElementById('postCommentBtn');
  const commentInput = document.getElementById('commentInput');

  let commentsData = [];

function renderComments() {
  commentsContainer.innerHTML = '';

  commentsData.forEach(comment => {
    const div = document.createElement('div');
    div.className = 'comment';

    const isOwner = comment.user_id === CURRENT_USER_ID;
    const initial = (comment.full_name || comment.username || '?')[0].toUpperCase();
    const avatarHTML = comment.profile_image
      ? `<img src="${escapeHtml(comment.profile_image)}" alt="Avatar">`
      : initial;

    div.innerHTML = `
      <div class="comment-header">
        <div class="comment-avatar">${avatarHTML}</div>
        <div class="comment-identity">
          <span class="comment-user">${escapeHtml(comment.full_name || comment.username)}</span>
          <span class="comment-username">@${escapeHtml(comment.username)}</span>
        </div>
        ${comment.is_edited == 1 ? '<span class="comment-edited">edited</span>' : ''}
      </div>

      <div class="comment-text">${escapeHtml(comment.comment_text)}</div>

      <div class="comment-actions">
        <button onclick="replyComment(${comment.id})">↩ Reply</button>
        ${isOwner ? `
          <button class="edit-btn" onclick="editComment(${comment.id})">✏ Edit</button>
          <button class="delete-btn" onclick="deleteComment(${comment.id})">✕ Delete</button>
        ` : ''}
      </div>
    `;

    commentsContainer.appendChild(div);
  });
}
  async function loadComments() {
    commentsLoading.style.display = 'block';

    try {
      const res = await fetch('handlers/comment_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'fetch',
          post_id: <?= $post_id ?>
        })
      });

      if (!res.ok) throw new Error('Fetch failed');

      const data = await res.json();

      if (data.success) {
        commentsData = data.comments;
        renderComments();
      }

    } catch (err) {
      console.error(err);
      showNotification('Failed to load comments', 'error');
    }

    commentsLoading.style.display = 'none';
  }

  loadComments();

  // =========================
  // ADD COMMENT
  // =========================
  postCommentBtn?.addEventListener('click', async () => {
    const text = commentInput.value.trim();
    if (!text) return;

    try {
      const res = await fetch('handlers/comment_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'add',
          post_id: <?= $post_id ?>,
          comment_text: text,
          parent_id: null
        })
      });

      const data = await res.json();

      if (data.success) {
        commentInput.value = '';
        loadComments();
      } else {
        showNotification(data.message, 'error');
      }

    } catch (err) {
      console.error(err);
      showNotification('Failed to post comment', 'error');
    }
  });

  // =========================
  // EDIT COMMENT
  // =========================
  async function editComment(id) {
    const newText = prompt("Edit your comment:");
    if (!newText) return;

    try {
      const res = await fetch('handlers/comment_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'edit',
          comment_id: id,
          comment_text: newText
        })
      });

      const data = await res.json();

      if (data.success) {
        loadComments();
      }

    } catch (err) {
      console.error(err);
    }
  }

  // =========================
  // DELETE COMMENT
  // =========================
  async function deleteComment(id) {
    if (!confirm("Delete this comment?")) return;

    try {
      const res = await fetch('handlers/comment_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          action: 'delete',
          comment_id: id
        })
      });

      const data = await res.json();

      if (data.success) {
        loadComments();
      }

    } catch (err) {
      console.error(err);
    }
  }

  // Placeholder
  function replyComment(id) {
    alert("Reply feature coming soon");
  }
let selectedCommentId = null;

// OPEN MODAL
function openCommentModal(commentId) {
  selectedCommentId = commentId;
  document.getElementById("commentModal").classList.remove("hidden");
}

// CLOSE MODAL
function closeModal() {
  document.getElementById("commentModal").classList.add("hidden");
  selectedCommentId = null;
}

// EDIT BUTTON
document.addEventListener('DOMContentLoaded', function () {
  const modalEditBtn   = document.getElementById("modalEditBtn");
  const modalDeleteBtn = document.getElementById("modalDeleteBtn");

  if (modalEditBtn) {
    modalEditBtn.addEventListener("click", function () {
      if (!selectedCommentId) return;
      editComment(selectedCommentId);
      closeModal();
    });
  }

  if (modalDeleteBtn) {
    modalDeleteBtn.addEventListener("click", function () {
      if (!selectedCommentId) return;
      deleteComment(selectedCommentId);
      closeModal();
    });
  }
});
// ── VIDEO PLAYER ──────────────────────────────────────────────
(function () {
    const wrap       = document.getElementById('videoWrap');
    if (!wrap) return;

    const video      = document.getElementById('mainVideo');
    const bigPlay    = document.getElementById('bigPlay');
    const playBtn    = document.getElementById('playPauseBtn');
    const muteBtn    = document.getElementById('muteBtn');
    const fullBtn    = document.getElementById('fullBtn');
    const progressBar   = document.getElementById('progressBar');
    const progressFill  = document.getElementById('progressFill');
    const progressThumb = document.getElementById('progressThumb');
    const vcTime     = document.getElementById('vcTime');

    function fmt(s) {
        s = Math.floor(s || 0);
        const m = Math.floor(s / 60);
        const sec = String(s % 60).padStart(2, '0');
        return `${m}:${sec}`;
    }

    function updatePlayUI() {
        const paused = video.paused;
        playBtn.textContent = paused ? '▶' : '⏸';
        bigPlay.classList.toggle('hidden', !paused);
    }

    // Click on video area = play/pause
    wrap.addEventListener('click', e => {
        if (e.target.closest('.video-controls')) return;
        video.paused ? video.play() : video.pause();
    });

    playBtn.addEventListener('click', e => {
        e.stopPropagation();
        video.paused ? video.play() : video.pause();
    });

    video.addEventListener('play',  updatePlayUI);
    video.addEventListener('pause', updatePlayUI);
    video.addEventListener('ended', updatePlayUI);

    // Progress
    video.addEventListener('timeupdate', () => {
        if (!video.duration) return;
        const pct = (video.currentTime / video.duration) * 100;
        progressFill.style.width  = pct + '%';
        progressThumb.style.left  = pct + '%';
        vcTime.textContent = `${fmt(video.currentTime)} / ${fmt(video.duration)}`;
    });

    // Seek
    let seeking = false;
    function seek(e) {
        const rect = progressBar.getBoundingClientRect();
        const pct  = Math.max(0, Math.min(1, (e.clientX - rect.left) / rect.width));
        video.currentTime = pct * video.duration;
    }
    progressBar.addEventListener('mousedown', e => { seeking = true; seek(e); });
    document.addEventListener('mousemove',    e => { if (seeking) seek(e); });
    document.addEventListener('mouseup',      ()  => { seeking = false; });

    // Mute
    muteBtn.addEventListener('click', e => {
        e.stopPropagation();
        video.muted = !video.muted;
        muteBtn.textContent = video.muted ? '🔇' : '🔊';
    });

    // Fullscreen
    fullBtn.addEventListener('click', e => {
        e.stopPropagation();
        if (!document.fullscreenElement) wrap.requestFullscreen();
        else document.exitFullscreen();
    });

    // Show controls briefly on touch
    wrap.addEventListener('touchstart', () => {
        wrap.classList.add('show-controls');
        clearTimeout(wrap._hideTimer);
        wrap._hideTimer = setTimeout(() => wrap.classList.remove('show-controls'), 3000);
    });

    updatePlayUI();
})();
</script>
<div id="commentModal" class="modal-overlay hidden">
  <div class="modal-box">
    
    <h3>Comment Options</h3>

    <button class="modal-btn edit-btn" id="modalEditBtn">
      ✏️ Edit Comment
    </button>

    <button class="modal-btn delete-btn" id="modalDeleteBtn">
      🗑️ Delete Comment
    </button>

    <button class="modal-btn cancel-btn" onclick="closeModal()">
      ❌ Cancel
    </button>

  </div>
</div>
</body>

</html>