<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/page_tracker.php';
require_once __DIR__ . '/../includes/auth.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$alias = $_GET['params'][0] ?? '';

if (!$alias) {
    showError(404);
}

$pdo = Database::getInstance();

// Check if admin is logged in
$isAdmin = Auth::isLoggedIn() && $_SESSION['role'] === 'admin';

// Get article from unified content table
if ($isAdmin) {
    // Admins can view any article (published or draft)
    $stmt = $pdo->prepare("
        SELECT *, slug as alias, body as content, published_at as published_date FROM content 
        WHERE slug = ? 
        AND type = 'article'
        AND deleted_at IS NULL
    ");
} else {
    // Regular users can only view published articles
    $stmt = $pdo->prepare("
        SELECT *, slug as alias, body as content, published_at as published_date FROM content 
        WHERE slug = ? 
        AND type = 'article'
        AND status = 'published' 
        AND deleted_at IS NULL
    ");
}
$stmt->execute([$alias]);
$article = $stmt->fetch();

if (!$article) {
    showError(404);
}

// Get stored page information from content table
$pageInfo = getPageInfo($pdo, $article['id'], 'content');

// Parse article content into pages
$pages = explode('<!-- page -->', $article['body'] ?? '');
$totalPages = count($pages);

// Clean up pages - remove empty ones
$pages = array_filter($pages, function($page) {
    return !empty(trim($page));
});
$pages = array_values($pages); // Re-index
$totalPages = count($pages) ?: 1;

// If no page breaks, treat entire content as single page
if ($totalPages === 0) {
    $pages = [$article['content'] ?? ''];
    $totalPages = 1;
}

// If we don't have stored page info, generate it
if (!$pageInfo) {
    $pageData = extractPageInfo($article['content']);
    $pageInfo = $pageData;
}

// Get attachments
$stmt = $pdo->prepare("
    SELECT * FROM content_attachments 
    WHERE content_type = 'article' 
    AND content_id = ?
");
$stmt->execute([$article['id']]);
$attachments = $stmt->fetchAll();

// Set page title
$pageTitle = $article['title'];

// Additional styles for multi-page articles
$additionalStyles = '
<style>
    .article-content {
        transition: opacity 0.3s ease;
        min-height: 300px;
    }
    
    .elegant-nav {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 2rem;
        padding: 3rem 0 2rem;
        margin-top: 3rem;
        border-top: 1px solid #eee;
    }
    
    .nav-arrow {
        width: 40px;
        height: 40px;
        border: none;
        background: transparent;
        color: #666;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.3s ease;
    }
    
    .nav-arrow:hover:not(:disabled) {
        background: #f5f5f5;
        color: #333;
    }
    
    .nav-arrow:disabled {
        opacity: 0.3;
        cursor: not-allowed;
    }
    
    .page-select-wrapper {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }
    
    .elegant-select {
        appearance: none;
        background: transparent;
        border: none;
        font-size: 1.1rem;
        font-family: \'Gelasio\', serif;
        color: #333;
        text-align: center;
        cursor: pointer;
        padding: 0.25rem 1rem;
        min-width: 200px;
        position: relative;
    }
    
    .elegant-select:hover {
        color: #000;
    }
    
    .elegant-select:focus {
        outline: none;
        border-bottom: 1px solid #666;
    }
    
    .page-info {
        font-size: 0.85rem;
        color: #999;
        font-family: \'Arimo\', sans-serif;
        letter-spacing: 0.5px;
    }
    
    .back-nav {
        text-align: center;
        padding: 1.5rem 0;
    }
    
    .back-link {
        color: #666;
        text-decoration: none;
        font-family: \'Arimo\', sans-serif;
        font-size: 0.95rem;
        transition: color 0.3s;
    }
    
    .back-link:hover {
        color: #333;
    }
    
    @media print {
        .article-controls,
        .site-header,
        .site-footer,
        .article-nav {
            display: none;
        }
    }
</style>
';

// Include header template
require_once __DIR__ . '/../includes/header.php';
?>
    
    <main id="main" class="site-main">
        <div class="container">
            <article class="article-wrapper">
                <header class="article-header">
                    <h1><?= htmlspecialchars($article['title']) ?></h1>
                    <?php if ($isAdmin && $article['status'] === 'draft'): ?>
                    <div class="draft-indicator" style="background: #f39c12; color: white; padding: 5px 15px; border-radius: 3px; display: inline-block; margin-bottom: 10px; font-size: 14px;">
                        DRAFT - Not visible to public
                    </div>
                    <?php endif; ?>
                    <div class="article-meta">
                        Published on <?= date('F j, Y', strtotime($article['published_date'] ?? $article['created_at'])) ?>
                        <?php if ($article['updated_at'] > $article['created_at']): ?>
                        • Updated <?= date('F j, Y', strtotime($article['updated_at'])) ?>
                        <?php endif; ?>
                    </div>
                </header>
                
                
                <div id="article-content" class="article-body article-content">
                    <!-- Article pages will be loaded here -->
                </div>
                
                <?php if ($totalPages > 1): ?>
                <div class="elegant-nav">
                    <button class="nav-arrow nav-prev" onclick="navigatePage(-1)" aria-label="Previous page" disabled>
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                    </button>
                    
                    <div class="page-select-wrapper">
                        <select id="page-selector" onchange="goToPage(this.value)" class="elegant-select">
                            <?php if ($pageInfo && isset($pageInfo['pages'])): ?>
                                <?php foreach ($pageInfo['pages'] as $page): ?>
                                    <option value="<?= $page['page'] ?>">
                                        <?= htmlspecialchars($page['title']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                    <option value="<?= $i ?>">Page <?= $i ?></option>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </select>
                        <span class="page-info"><?= $totalPages ?> pages</span>
                    </div>
                    
                    <button class="nav-arrow nav-next" onclick="navigatePage(1)" aria-label="Next page">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </button>
                </div>
                
                <nav class="back-nav">
                    <a href="/articles" class="back-link">← Back to Articles</a>
                </nav>
                <?php else: ?>
                <nav class="back-nav" style="margin-top: 2rem;">
                    <a href="/articles" class="back-link">← Back to Articles</a>
                </nav>
                <?php endif; ?>
                
                <?php if ($attachments && count($attachments) > 0): ?>
                <div class="article-attachments">
                    <h3>Attachments</h3>
                    <ul>
                        <?php foreach ($attachments as $attachment): ?>
                        <li>
                            <a href="/download/<?= htmlspecialchars($attachment['filename']) ?>">
                                <?= htmlspecialchars($attachment['document_name']) ?>
                            </a>
                            (<?= number_format($attachment['file_size'] / 1024, 1) ?> KB)
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
            </article>
        </div>
    </main>
    
<?php
// Additional scripts for article navigation
$additionalScripts = '
<script>
    // Store pages data with processed images
    const pages = ' . json_encode(array_map('processContentImages', $pages)) . ';
    const slug = ' . json_encode($alias) . ';
    const totalPages = ' . $totalPages . ';
    let currentPage = 1;
        
    // Initialize
    document.addEventListener("DOMContentLoaded", function() {
        // Check if there\'s a page in the URL hash
        const hash = window.location.hash;
        if (hash) {
            const pageNum = parseInt(hash.replace("#page-", ""));
            if (pageNum && pageNum > 0 && pageNum <= totalPages) {
                currentPage = pageNum;
            }
        }
        
        loadPage(currentPage);
        updateHistory();
    });
    
    // Handle browser back/forward
    window.addEventListener("popstate", function(event) {
        if (event.state && event.state.page) {
            currentPage = event.state.page;
            loadPage(currentPage);
        }
    });
    
    function navigatePage(direction) {
        const newPage = currentPage + direction;
        if (newPage >= 1 && newPage <= totalPages) {
            currentPage = newPage;
            loadPage(currentPage);
            updateHistory();
            
            // Scroll to top of article
            document.querySelector(".article-wrapper").scrollIntoView({ behavior: "smooth" });
        }
    }
    
    function goToPage(page) {
        page = parseInt(page);
        if (page >= 1 && page <= totalPages) {
            currentPage = page;
            loadPage(currentPage);
            updateHistory();
            
            // Scroll to top of article
            document.querySelector(".article-wrapper").scrollIntoView({ behavior: "smooth" });
        }
    }
    
    function loadPage(pageNum) {
        const content = document.getElementById("article-content");
        const pageSelector = document.getElementById("page-selector");
        
        // Fade out current content
        content.style.opacity = "0";
        
        setTimeout(() => {
            // Update content
            content.innerHTML = pages[pageNum - 1] || "";
            
            // Update page selector
            if (pageSelector) {
                pageSelector.value = pageNum;
            }
            
            // Update navigation buttons
            updateNavButtons();
            
            // Update page dots
            updatePageDots();
            
            // Fade in new content
            content.style.opacity = "1";
        }, 200);
    }
    
    function updateNavButtons() {
        const prevButton = document.querySelector(".nav-prev");
        const nextButton = document.querySelector(".nav-next");
        
        if (prevButton) prevButton.disabled = currentPage === 1;
        if (nextButton) nextButton.disabled = currentPage === totalPages;
    }
    
    function updatePageDots() {
        // No longer using page dots
    }
    
    function updateHistory() {
        const url = `/article/${slug}#page-${currentPage}`;
        const state = { page: currentPage };
        
        if (window.location.hash === `#page-${currentPage}`) {
            return; // Already on this page
        }
        
        history.pushState(state, "", url);
    }
</script>
';

// Include footer template
require_once __DIR__ . '/../includes/footer.php';