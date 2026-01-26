<?php
require_once 'init.php';
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
    $post = $stmt->fetch();

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
    $user_has_liked = (bool)$stmt->fetch();
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
        <div class="author-avatar"><?= strtoupper($post['full_name'][0]) ?></div>
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
/* Like + Share logic unchanged */
</script>

</body>
</html>
