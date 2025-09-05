<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = getSetting('site_title', 'Dalthaus Photography');

// Get recent content for the homepage
$articles = getRecentArticles(4);
$photobooks = getRecentPhotobooks(3);

// Start output buffering to capture the page content
ob_start();
?>

<div class="content-layout">
    <section class="articles-section">
        <h2 class="section-title">Latest Articles</h2>
        <div class="articles-list">
            <?php if (!empty($articles)): ?>
                <?php foreach ($articles as $article): ?>
                <article class="front-article-item">
                    <a href="/article/<?= htmlspecialchars($article['slug']) ?>" class="front-article-thumb">
                        <?php if (!empty($article['featured_image'])): ?>
                            <img src="<?= htmlspecialchars($article['featured_image']) ?>" alt="<?= htmlspecialchars($article['title']) ?>">
                        <?php else: ?>
                            <div class="image-placeholder"></div>
                        <?php endif; ?>
                    </a>
                    <div class="front-article-content">
                        <h3 class="front-article-title">
                            <a href="/article/<?= htmlspecialchars($article['slug']) ?>"><?= htmlspecialchars($article['title']) ?></a>
                        </h3>
                        <div class="front-article-meta">
                            By <?= htmlspecialchars($article['author']) ?> on <?= date('F j, Y', strtotime($article['created_at'])) ?>
                        </div>
                        <div class="front-article-teaser">
                            <?= htmlspecialchars(substr(strip_tags($article['content']), 0, 100)) ?>...
                        </div>
                    </div>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-content">No articles have been published yet.</p>
            <?php endif; ?>
        </div>
    </section>

    <aside class="photobooks-section">
        <h2 class="section-title">Photo Books</h2>
        <div class="photobooks-list">
            <?php if (!empty($photobooks)): ?>
                <?php foreach ($photobooks as $photobook): ?>
                <article class="front-photobook-item">
                    <a href="/photobook/<?= htmlspecialchars($photobook['slug']) ?>">
                        <?php if (!empty($photobook['cover_image'])): ?>
                            <img src="<?= htmlspecialchars($photobook['cover_image']) ?>" alt="<?= htmlspecialchars($photobook['title']) ?>" class="front-photobook-thumbnail">
                        <?php else: ?>
                            <div class="image-placeholder front-photobook-thumbnail"></div>
                        <?php endif; ?>
                    </a>
                    <h3 class="front-photobook-title">
                        <a href="/photobook/<?= htmlspecialchars($photobook['slug']) ?>"><?= htmlspecialchars($photobook['title']) ?></a>
                    </h3>
                    <div class="front-photobook-meta">
                        By <?= htmlspecialchars($photobook['author']) ?> on <?= date('F j, Y', strtotime($photobook['created_at'])) ?>
                    </div>
                    <div class="front-photobook-excerpt">
                        <?= htmlspecialchars(substr(strip_tags($photobook['description']), 0, 80)) ?>...
                    </div>
                </article>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="no-content">No photo books have been published yet.</p>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php
// Get the captured content and assign it to a variable
$page_content = ob_get_clean();

// Include the template file
require_once __DIR__ . '/template.php';
?>