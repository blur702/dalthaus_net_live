<?php
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


<style>
    /* Main container */
    .main-content,
    .listing-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }
    
    /* Page title */
    .page-title {
        font-family: 'Arimo', sans-serif;
        font-size: 2rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 40px;
        padding-bottom: 10px;
        border-bottom: 2px solid #3498db;
    }
    
    /* Grid container */
    .listings-grid,
    .items-list {
        display: flex;
        flex-direction: row;
        gap: 30px;
    }
    
    /* Individual card - THIS IS THE KEY FIX */
    .listing-card,
    .item-card {
        display: grid !important;
        grid-template-columns: 200px 1fr !important;
        gap: 25px;
        padding-bottom: 25px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .listing-card:last-child,
    .item-card:last-child {
        border-bottom: none;
    }
    
    /* Image container */
    .listing-card-image,
    .listing-thumbnail,
    .image-placeholder,
    .item-image {
        width: 200px !important;
        height: 150px !important;
        aspect-ratio: 4/3 !important;
        background: #f0f0f0;
        border-radius: 8px;
        overflow: hidden;
        flex-shrink: 0;
    }
    
    .listing-card-image img,
    .listing-thumbnail img,
    .item-image img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Content container */
    .listing-card-content,
    .item-content {
        display: flex;
        flex-direction: row;
        gap: 10px;
    }
    
    /* Title */
    .listing-title,
    .item-title {
        font-family: 'Arimo', sans-serif;
        font-size: 1.5rem;
        font-weight: 700;
        color: #2c3e50;
        text-decoration: none;
        transition: color 0.3s ease;
        line-height: 1.2;
    }
    
    .listing-title:hover,
    .item-title:hover {
        color: #3498db;
    }
    
    /* Meta info */
    .listing-meta,
    .item-meta {
        font-size: 0.9rem;
        color: #7f8c8d;
        font-style: italic;
    }
    
    /* Description/excerpt */
    .listing-excerpt,
    .item-description {
        font-size: 1rem;
        color: #555;
        line-height: 1.6;
        text-align: justify;
    }
    
    /* For homepage specific styles */
    .article-item {
        display: grid !important;
        grid-template-columns: 180px 1fr !important;
        gap: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .article-image {
        aspect-ratio: 4/3 !important;
        background: #f0f0f0;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .photobook-item {
        padding-bottom: 20px;
        margin-bottom: 20px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .photobook-image {
        width: 100%;
        aspect-ratio: 4/3 !important;
        background: #f0f0f0;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 15px;
    }
    
    /* Responsive */
    @media (max-width: 768px) {
        .listing-card,
        .item-card,
        .article-item {
            grid-template-columns: 1fr !important;
            gap: 15px;
        }
        
        .listing-card-image,
        .listing-thumbnail,
        .image-placeholder,
        .item-image {
            width: 100% !important;
            max-width: 400px;
            margin: 0 auto;
        }
    }
</style>


        
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