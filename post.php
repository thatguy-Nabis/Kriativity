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
          <div class="author-avatar"><?= strtoupper($post['full_name'][0] ?? 'U') ?></div>
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
      <?php if ($post['image_url']): ?>
        <img src="<?= htmlspecialchars($post['image_url']) ?>" class="post-image">
      <?php endif; ?>

      <?php if ($post['description']): ?>
        <p class="post-description-text"><?= nl2br(htmlspecialchars($post['description'])) ?></p>
      <?php endif; ?>

      <div class="post-actions">
        <button id="likeBtn" class="action-btn btn-like <?= $user_has_liked ? 'liked' : '' ?>">
          <span id="likeIcon"><?= $user_has_liked ? '❤️' : '🤍' ?></span>
          <span id="likeText"><?= $user_has_liked ? 'Liked' : 'Like' ?></span>
        </button>

        <button class="action-btn btn-share" onclick="sharePost()">🔗 Share</button>
        <button class="action-btn btn-report" onclick="openReportModal('content', <?= $post['id'] ?>)">
          🚩 Report
        </button>

      </div>
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

    div.innerHTML = `
      <div class="comment-header">
        <div class="comment-avatar">${initial}</div>
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
document.getElementById("modalEditBtn").addEventListener("click", function () {
  if (!selectedCommentId) return;

  editComment(selectedCommentId);
  closeModal();
});

// DELETE BUTTON
document.getElementById("modalDeleteBtn").addEventListener("click", function () {
  if (!selectedCommentId) return;

  deleteComment(selectedCommentId);
  closeModal();
});
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