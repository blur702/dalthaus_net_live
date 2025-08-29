<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = Database::getInstance();

// Get cached version if available
$cacheKey = 'homepage';
$cached = cacheGet($cacheKey);

if ($cached) {
    echo $cached;
    exit;
}

// Get published articles from unified content table
$articles = $pdo->query("
    SELECT *, slug as alias, body as content, published_at as published_date FROM content 
    WHERE type = 'article'
    AND status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY sort_order, created_at DESC 
    LIMIT 10
")->fetchAll();

// Get published photobooks from unified content table
$photobooks = $pdo->query("
    SELECT *, slug as alias, body as content, published_at as published_date FROM content 
    WHERE type = 'photobook'
    AND status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY sort_order, created_at DESC 
    LIMIT 10
")->fetchAll();

// Set page title (optional - will use site title by default)
// $pageTitle = 'Home';

ob_start();

// Include header template
require_once __DIR__ . '/../includes/header.php';
?>
        
        <!-- Main content area -->
        <main class="main-content" role="main">
            <div class="content-layout">
                <!-- Left column: Articles (66%) -->
                <section class="articles-section">
                    <h2 class="section-title">Articles</h2>
                    <div class="articles-list">
                        <?php if ($articles && count($articles) > 0): ?>
                            <?php foreach ($articles as $article): ?>
                            <article class="front-article-item">
                                <div class="front-article-thumb">
                                    <?php
                                    // Use teaser image first, then featured image, then extract from content
                                    $imgSrc = $article['teaser_image'] ?? $article['featured_image'] ?? null;
                                    if (!$imgSrc && !empty($article['content'])) {
                                        preg_match('/<img[^>]+src=["\'"]([^"\']+)["\'"]/', $article['content'], $imgMatch);
                                        $imgSrc = isset($imgMatch[1]) ? $imgMatch[1] : null;
                                    }
                                    $hasImage = $imgSrc && file_exists($_SERVER['DOCUMENT_ROOT'] . $imgSrc);
                                    
                                    if ($hasImage) {
                                        echo '<img src="' . htmlspecialchars($imgSrc) . '" alt="">';
                                    } else {
                                        echo '<div class="image-placeholder"></div>';
                                    }
                                    ?>
                                </div>
                                <div class="front-article-content">
                                    <h3 class="front-article-title">
                                        <a href="/article/<?= htmlspecialchars($article['alias']) ?>">
                                            <?= htmlspecialchars($article['title']) ?>
                                        </a>
                                    </h3>
                                    <div class="front-article-meta">
                                        Don Althaus · <?= date('d F Y', strtotime($article['published_date'] ?? $article['created_at'])) ?> · Articles
                                    </div>
                                    <div class="front-article-teaser">
                                        <?= htmlspecialchars($article['teaser_text'] ?? (mb_substr(strip_tags($article['content'] ?? ''), 0, 150) . '...')) ?>
                                    </div>
                                </div>
                            </article>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-content">No articles published yet.</p>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- Right column: Photobooks (33%) -->
                <aside class="photobooks-section">
                    <h2 class="section-title">Photo Books</h2>
                    <div class="photobooks-list">
                        <?php if ($photobooks && count($photobooks) > 0): ?>
                            <?php foreach ($photobooks as $book): ?>
                            <div class="front-photobook-item">
                                <?php
                                // Use teaser image first, then featured image, then extract from content
                                $imgSrc = $book['teaser_image'] ?? $book['featured_image'] ?? null;
                                if (!$imgSrc && !empty($book['body'])) {
                                    preg_match('/<img[^>]+src=["\'"]([^"\']+)["\'"]/', $book['body'], $imgMatch);
                                    $imgSrc = isset($imgMatch[1]) ? $imgMatch[1] : null;
                                }
                                $hasImage = $imgSrc && file_exists($_SERVER['DOCUMENT_ROOT'] . $imgSrc);
                                
                                if ($hasImage) {
                                    echo '<img src="' . htmlspecialchars($imgSrc) . '" alt="" class="front-photobook-thumbnail">';
                                } else {
                                    echo '<div class="image-placeholder front-photobook-thumbnail"></div>';
                                }
                                ?>
                                <h3 class="front-photobook-title">
                                    <a href="/photobook/<?= htmlspecialchars($book['alias']) ?>">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </a>
                                </h3>
                                <div class="front-photobook-meta">
                                    Don Althaus · <?= date('d F Y', strtotime($book['published_date'] ?? $book['created_at'])) ?> · Photo Books
                                </div>
                                <div class="front-photobook-excerpt">
                                    <?= htmlspecialchars($book['teaser_text'] ?? (mb_substr(strip_tags($book['body'] ?? ''), 0, 120) . '...')) ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="no-content">No photo books published yet.</p>
                        <?php endif; ?>
                    </div>
                </aside>
            </div>
        </main>
        
<?php
// Include footer template
require_once __DIR__ . '/../includes/footer.php';

$html = ob_get_clean();
cacheSet($cacheKey, $html);
echo $html;