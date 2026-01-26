<?php

require_once 'init.php';


require_once 'config/database.php';

function getCategories($pdo)
{
  try {
    $stmt = $pdo->query("SELECT DISTINCT category
                             FROM content
                             WHERE is_published = 1 AND category IS NOT NULL AND category <> ''
                             ORDER BY category");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
  } catch (PDOException $e) {
    error_log("Error fetching categories: " . $e->getMessage());
    return [];
  }
}

function getContentFromDatabase($pdo, $page = 1, $limit = 12, $category = null, $search = null, $trending = false)
{
  $page = max(1, (int) $page);
  $limit = max(1, min(48, (int) $limit));
  $offset = ($page - 1) * $limit;

  $query = "SELECT c.*, u.username, u.full_name
              FROM content c
              JOIN users u ON c.user_id = u.id
              WHERE c.is_published = 1";

  $params = [];

  if ($category && $category !== 'all') {
    $query .= " AND c.category = :category";
    $params[':category'] = $category;
  }

  if ($search && trim($search) !== '') {
    $query .= " AND (c.title LIKE :search OR c.description LIKE :search)";
    $params[':search'] = '%' . trim($search) . '%';
  }

  $scoreExpr = "(COALESCE(c.views,0) * 0.3 + COALESCE(c.likes,0) * 0.7)";

  if ($trending)
    $query .= " ORDER BY {$scoreExpr} DESC, c.created_at DESC";
  else
    $query .= " ORDER BY c.created_at DESC";

  $query .= " LIMIT :limit OFFSET :offset";

  try {
    $stmt = $pdo->prepare($query);
    foreach ($params as $k => $v)
      $stmt->bindValue($k, $v);
    $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
    error_log("Error fetching content: " . $e->getMessage());
    return [];
  }
}

function getContentByIds($pdo, array $ids)
{
  $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
  if (empty($ids))
    return [];

  $placeholders = implode(',', array_fill(0, count($ids), '?'));

  $query = "SELECT c.*, u.username, u.full_name
              FROM content c
              JOIN users u ON c.user_id = u.id
              WHERE c.is_published = 1 AND c.id IN ($placeholders)";

  try {
    $stmt = $pdo->prepare($query);
    foreach ($ids as $i => $id)
      $stmt->bindValue($i + 1, $id, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $map = [];
    foreach ($rows as $r)
      $map[(int) $r['id']] = $r;

    $ordered = [];
    foreach ($ids as $id)
      if (isset($map[$id]))
        $ordered[] = $map[$id];
    return $ordered;
  } catch (PDOException $e) {
    error_log("Error fetching by ids: " . $e->getMessage());
    return [];
  }
}

$is_ajax = isset($_GET['ajax']) && $_GET['ajax'] === 'true';
if ($is_ajax) {
  // header('Content-Type: application/json');

  // For You fetch by ids
  if (isset($_GET['ids'])) {
    $idsRaw = (string) $_GET['ids'];
    $ids = array_filter(explode(',', $idsRaw));
    echo json_encode(['cards' => getContentByIds($pdo, $ids), 'hasMore' => false]);
    exit;
  }

  $page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
  $category = isset($_GET['category']) ? (string) $_GET['category'] : 'all';
  $search = isset($_GET['search']) ? (string) $_GET['search'] : null;
  $trending = isset($_GET['trending']) && ($_GET['trending'] === 'true' || $_GET['trending'] === '1');

  $cards = getContentFromDatabase($pdo, $page, 12, $category, $search, $trending);
  echo json_encode(['cards' => $cards, 'page' => $page, 'hasMore' => count($cards) === 12]);
  exit;
}

$categories = getCategories($pdo);
$isLoggedIn = isset($_SESSION['user_id']);

?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Kriativity - Explore</title>
  <link rel="stylesheet" href="./styles.css">
</head>

<body>

  <?php include 'header.php'; ?>

  <div class="content-tabs">
    <div class="tabs-container">
      <!-- ✅ Always start on ALL -->
      <button class="tab-button " data-tab="all">All</button>
      <?php if ($isLoggedIn): ?>
        <button class="tab-button" data-tab="for-you">✨ For You</button>
      <?php endif; ?>
      <button class="tab-button" data-tab="trending">🔥 Trending</button>
    </div>
  </div>

  <main class="main-container">

    <div class="tab-content active" id="all-tab">
      <h1 class="section-title">Explore All Content</h1>

      <div class="filters-container">
        <div class="filter-group">
          <label class="filter-label" for="categoryFilter">Category:</label>
          <select id="categoryFilter" class="filter-select">
            <option value="all">All Categories</option>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>">
                <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="cards-grid" id="cardsContainer"></div>

      <div class="empty-state" id="emptyState" style="display:none;">
        <div class="empty-state-icon">🔍</div>
        <div class="empty-state-text">No content found</div>
        <div class="empty-state-subtext">Try adjusting your filters</div>
      </div>

      <div class="loading-indicator" id="loadingIndicator">
        <div class="spinner"></div>
      </div>

      <div class="end-message" id="endMessage" style="display:none;">
        You've reached the end of available content
      </div>
    </div>

    <?php if ($isLoggedIn): ?>
      <div class="tab-content" id="for-you-tab">
        <div class="for-you-header">
          <h1 class="section-title">Curated For You</h1>
          <button id="refreshForYouBtn" class="refresh-btn">🔄 Refresh</button>
        </div>
        <div class="cards-grid" id="forYouContainer"></div>

        <div class="empty-state" id="forYouEmpty" style="display:none;">
          <div class="empty-state-icon">🎨</div>
          <div class="empty-state-text">Like a few posts to personalize your feed</div>
          <div class="empty-state-subtext">We’ll recommend similar content automatically.</div>
        </div>

        <div class="loading-indicator" id="forYouLoading">
          <div class="spinner"></div>
        </div>
      </div>
    <?php endif; ?>

    <div class="tab-content" id="trending-tab">
      <h1 class="section-title">🔥 Trending Now</h1>
      <p style="text-align:center;color:#a0a0a0;margin-bottom:2rem;">Most popular content right now</p>

      <div class="cards-grid" id="trendingContainer"></div>

      <div class="loading-indicator" id="trendingLoadingIndicator">
        <div class="spinner"></div>
      </div>
    </div>

  </main>

  <?php include 'footer.php'; ?>

  <script>
    const IS_LOGGED_IN = <?= $isLoggedIn ? 'true' : 'false' ?>;

    (() => {
      const tabButtons = document.querySelectorAll('.tab-button');
      const tabContents = document.querySelectorAll('.tab-content');

      window.trendingLoaded = false;
      window.forYouLoaded = false;

      function switchTab(tabName) {
        // deactivate all
        tabButtons.forEach(btn => btn.classList.remove('active'));
        tabContents.forEach(tab => tab.classList.remove('active'));

        // activate selected
        document.querySelector(`[data-tab="${tabName}"]`)?.classList.add('active');
        document.getElementById(`${tabName}-tab`)?.classList.add('active');

        // lazy-load logic
        if (tabName === 'for-you' && IS_LOGGED_IN && !window.forYouLoaded) {
          loadForYou();            // ✅ ONLY HERE
        }

        if (tabName === 'trending' && !window.trendingLoaded) {
          loadTrendingContent();  // ✅ ONLY HERE
        }
      }

      tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          switchTab(btn.dataset.tab);
        });
      });
    })();

    function escapeHtml(text) {
      const div = document.createElement('div');
      div.textContent = text ?? '';
      return div.innerHTML;
    }
    function formatNumber(num) {
      num = Number(num) || 0;
      if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
      if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
      return String(num);
    }
    function createCardElement(card) {
      const div = document.createElement('div');
      div.className = 'content-card';

      let imageStyle = '';
      if (card.image_url) {
        imageStyle = `background-image:url('${card.image_url}');background-size:cover;background-position:center;`;
      } else {
        const colors = ['#FF6B6B', '#4ECDC4', '#45B7D1', '#FFA07A', '#98D8C8', '#F7DC6F'];
        const color = colors[(card.id || 0) % colors.length];
        imageStyle = `background:linear-gradient(135deg, ${color}, #15051d);`;
      }

      div.innerHTML = `
    <div class="card-image" style="${imageStyle}">
      <div class="card-overlay"><span class="overlay-text">View Details →</span></div>
    </div>
    <div class="card-content">
      <span class="card-category">${escapeHtml(card.category || 'Uncategorized')}</span>
      <h3 class="card-title">${escapeHtml(card.title || '')}</h3>
      ${card.description ? `<p class="card-description">${escapeHtml(String(card.description).substring(0, 100))}${String(card.description).length > 100 ? '...' : ''}</p>` : ''}
      <div class="card-stats">
        <div class="stat-item"><span class="stat-icon">👁️</span><span>${formatNumber(card.views)} views</span></div>
        <div class="stat-item"><span class="stat-icon">❤️</span><span>${formatNumber(card.likes)} likes</span></div>
      </div>
      <div class="card-author"><span>by ${escapeHtml(card.username || 'Unknown')}</span></div>
    </div>
  `;
      div.addEventListener('click', () => window.location.href = `post.php?id=${card.id}`);
      return div;
    }

    // ALL infinite scroll
    (() => {
      let currentPage = 1;
      let isLoading = false;
      let hasMoreContent = true;
      let currentCategory = 'all';

      const cardsContainer = document.getElementById('cardsContainer');
      const loadingIndicator = document.getElementById('loadingIndicator');
      const endMessage = document.getElementById('endMessage');
      const emptyState = document.getElementById('emptyState');
      const categoryFilter = document.getElementById('categoryFilter');

      const tabButtons = document.querySelectorAll('.tab-button');
      const tabContents = document.querySelectorAll('.tab-content');

      let trendingLoaded = false;
      let forYouLoaded = false;


      function hideAllTabs() {
        tabContents.forEach(t => {
          t.classList.remove('active');
          t.style.display = 'none';
        });
        tabButtons.forEach(b => b.classList.remove('active'));
      }

      window.switchTab = function (tab) {
        hideAllTabs();

        document.querySelector(`[data-tab="${tab}"]`)?.classList.add('active');

        const section = document.getElementById(`${tab}-tab`);
        if (!section) return;

        section.classList.add('active');
        section.style.display = 'block';

        if (tab === 'trending' && !trendingLoaded) {
          trendingLoaded = true;
          loadTrendingContent();
        }

        if (tab === 'for-you' && IS_LOGGED_IN && !forYouLoaded) {
          forYouLoaded = true;
          loadForYou();
        }
      };

      /* ========= INITIAL TAB SELECTION ========= */
      document.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');

        if (tab === 'trending' || tab === 'for-you') {
          switchTab(tab);
        } else {
          switchTab('all'); // default
        }
      });

      /* ========= TAB BUTTON CLICKS ========= */
      tabButtons.forEach(btn => {
        btn.addEventListener('click', () => {
          const tab = btn.dataset.tab;
          switchTab(tab);
          history.replaceState(null, '', `?tab=${tab}`);
        });
      });

      function renderCards(cards) {
        cards.forEach(c => cardsContainer.appendChild(createCardElement(c)));
      }

      function loadMoreCards() {
        if (isLoading || !hasMoreContent) return;
        if (!document.getElementById('all-tab').classList.contains('active')) return;

        isLoading = true;
        loadingIndicator.classList.add('active');

        const params = new URLSearchParams({ ajax: 'true', page: String(currentPage), category: currentCategory });

        fetch(`homepage.php?${params.toString()}`)
          .then(r => r.json())
          .then(data => {
            const cards = data.cards || [];
            if (cards.length === 0) {
              hasMoreContent = false;
              if (currentPage === 1) emptyState.style.display = 'block';
              else endMessage.style.display = 'block';
            } else {
              renderCards(cards);
              hasMoreContent = !!data.hasMore;
              if (!hasMoreContent) endMessage.style.display = 'block';
            }
            currentPage++;
            isLoading = false;
            loadingIndicator.classList.remove('active');
          })
          .catch(err => {
            console.error(err);
            isLoading = false;
            loadingIndicator.classList.remove('active');
          });
      }

      function resetAndReload() {
        currentPage = 1;
        hasMoreContent = true;
        cardsContainer.innerHTML = '';
        endMessage.style.display = 'none';
        emptyState.style.display = 'none';
        loadMoreCards();
      }

      categoryFilter?.addEventListener('change', e => {
        currentCategory = e.target.value;
        resetAndReload();
      });

      const observer = new IntersectionObserver(entries => {
        entries.forEach(entry => entry.isIntersecting && loadMoreCards());
      }, { rootMargin: '250px' });

      observer.observe(loadingIndicator);
      loadMoreCards();
    })();

    // FOR YOU (Python -> IDs -> cards)
    function loadForYou() {
      if (!IS_LOGGED_IN) return;

      const container = document.getElementById('forYouContainer');
      const loader = document.getElementById('forYouLoading');
      const empty = document.getElementById('forYouEmpty');

      if (!container || !loader || !empty) return;

      loader.classList.add('active');
      empty.style.display = 'none';
      container.innerHTML = '';

      fetch('api/get_recommendations.php?limit=12')
        .then(r => r.json())
        .then(data => {
          const recs = Array.isArray(data.recommendations)
            ? data.recommendations
            : [];

          // ✅ EARLY EXIT: no recommendations
          if (recs.length === 0) {
            loader.classList.remove('active');
            empty.style.display = 'block';
            empty.querySelector('.empty-state-text').textContent =
              'No recommendations yet';
            window.forYouLoaded = true;
            return;
          }

          const ids = recs.map(r => r.content_id).filter(Boolean);

          if (ids.length === 0) {
            loader.classList.remove('active');
            empty.style.display = 'block';
            window.forYouLoaded = true;
            return;
          }

          return fetch(`homepage.php?ajax=true&ids=${ids.join(',')}`)
            .then(r => r.json())
            .then(cardsData => {
              const cards = cardsData.cards || [];

              if (cards.length === 0) {
                empty.style.display = 'block';
              } else {
                cards.forEach(card =>
                  container.appendChild(createCardElement(card))
                );
              }

              loader.classList.remove('active');
              window.forYouLoaded = true;
            });
        })
        .catch(err => {
          console.error('For You error:', err);
          loader.classList.remove('active');
          empty.style.display = 'block';
          window.forYouLoaded = true;
        });
    }

    // TRENDING
    function loadTrendingContent() {
      const container = document.getElementById('trendingContainer');
      const loader = document.getElementById('trendingLoadingIndicator');
      if (!container || !loader) return;

      loader.classList.add('active');

      fetch('homepage.php?ajax=true&page=1&trending=true')
        .then(r => r.json())
        .then(data => {
          container.innerHTML = '';
          (data.cards || []).forEach(card => container.appendChild(createCardElement(card)));
          loader.classList.remove('active');
          window.trendingLoaded = true;
        })
        .catch(err => {
          console.error('Trending error:', err);
          loader.classList.remove('active');
        });
    }
  </script>

</body>

</html>