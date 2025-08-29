<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = Database::getInstance();

// Get all published articles from unified content table
$articles = $pdo->query("
    SELECT *, slug as alias, body as content, published_at as published_date FROM content 
    WHERE type = 'article' 
    AND status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY sort_order, created_at DESC
")->fetchAll();

// Set page title
$pageTitle = 'Articles';

// Include header template
require_once __DIR__ . '/../includes/header.php';
?>
        
        <main class="main-content" role="main">
            <div class="single-column-layout">
                <h1>Articles</h1>
                <div class="listings-grid">
                    <?php if ($articles && count($articles) > 0): ?>
                        <?php foreach ($articles as $article): ?>
                        <article class="listing-card">
                            <div class="listing-card-image">
                                <?php
                                $imgSrc = isset($article['featured_image']) ? $article['featured_image'] : null;
                                if (!$imgSrc && !empty($article['content'])) {
                                    preg_match('/<img[^>]+src=["\'"]([^"\']+)["\'"]/', $article['content'], $imgMatch);
                                    $imgSrc = isset($imgMatch[1]) ? $imgMatch[1] : null;
                                }
                                $hasImage = $imgSrc && file_exists($_SERVER['DOCUMENT_ROOT'] . $imgSrc);
                                
                                if ($hasImage) {
                                    echo '<img src="' . htmlspecialchars($imgSrc) . '" alt="" class="listing-thumbnail">';
                                } else {
                                    echo '<div class="image-placeholder listing-thumbnail"></div>';
                                }
                                ?>
                            </div>
                            <div class="listing-card-content">
                                <h2 class="listing-title">
                                    <a href="/article/<?= htmlspecialchars($article['alias']) ?>">
                                        <?= htmlspecialchars($article['title']) ?>
                                    </a>
                                </h2>
                                <div class="listing-meta">
                                    Don Althaus · <?= date('d F Y', strtotime($article['published_date'] ?? $article['created_at'])) ?> · Articles
                                </div>
                                <div class="listing-excerpt">
                                    <?= htmlspecialchars(($article['excerpt'] ?? null) ?: mb_substr(strip_tags($article['content'] ?? ''), 0, 300) . '...') ?>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-content">No articles published yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
<?php
// Include footer template
require_once __DIR__ . '/../includes/footer.php';