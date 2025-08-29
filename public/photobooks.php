<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$pdo = Database::getInstance();

// Get all published photobooks from unified content table
$photobooks = $pdo->query("
    SELECT *, slug as alias, body as content, published_at as published_date FROM content 
    WHERE type = 'photobook'
    AND status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY sort_order, created_at DESC
")->fetchAll();

// Set page title
$pageTitle = 'Photo Books';

// Include header template
require_once __DIR__ . '/../includes/header.php';
?>
        
        <main class="main-content" role="main">
            <div class="single-column-layout">
                <h1>Photo Books</h1>
                <div class="listings-grid">
                    <?php if ($photobooks && count($photobooks) > 0): ?>
                        <?php foreach ($photobooks as $book): ?>
                        <article class="listing-card">
                            <div class="listing-card-image">
                                <?php
                                $imgSrc = null;
                                if (isset($book['teaser_image']) && $book['teaser_image']) {
                                    $imgSrc = $book['teaser_image'];
                                } elseif (isset($book['featured_image']) && $book['featured_image']) {
                                    $imgSrc = $book['featured_image'];
                                }
                                if (!$imgSrc && !empty($book['body'])) {
                                    preg_match('/<img[^>]+src=["\'"]([^"\']+)["\'"]/', $book['body'], $imgMatch);
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
                                    <a href="/photobook/<?= htmlspecialchars($book['alias']) ?>">
                                        <?= htmlspecialchars($book['title']) ?>
                                    </a>
                                </h2>
                                <div class="listing-meta">
                                    Don Althaus · <?= date('d F Y', strtotime($book['published_date'] ?? $book['created_at'])) ?> · Photo Books
                                </div>
                                <div class="listing-excerpt">
                                    <?= htmlspecialchars((isset($book['summary']) ? $book['summary'] : null) ?: mb_substr(strip_tags($book['body'] ?? ''), 0, 300) . '...') ?>
                                </div>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-content">No photo books published yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
        
<?php
// Include footer template
require_once __DIR__ . '/../includes/footer.php';