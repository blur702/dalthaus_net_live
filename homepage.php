<?php
// Homepage with two-column layout for Articles and Photobooks
error_reporting(0); // Suppress errors for production

// Include files safely and initialize database
if (file_exists('includes/config.php')) {
    require_once 'includes/config.php';
}
if (file_exists('includes/database.php')) {
    require_once 'includes/database.php';
}
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} else if (file_exists('functions-fixed.php')) {
    require_once 'functions-fixed.php';
}

// Initialize database connection if not already done
if (!isset($pdo) && class_exists('Database')) {
    try {
        $pdo = Database::getInstance();
    } catch (Exception $e) {
        // Database connection failed, continue with defaults
        $pdo = null;
    }
}

// Set default values
$site_title = 'Dalthaus Photography';
$site_motto = 'Capturing moments, telling stories through light and shadow';

// Try to get from settings if available
if (function_exists('getSetting') && isset($pdo) && $pdo) {
    try {
        $title_from_db = getSetting('site_title', '');
        if ($title_from_db) {
            $site_title = $title_from_db;
        }
        
        $motto_from_db = getSetting('site_motto', '');
        if ($motto_from_db) {
            $site_motto = $motto_from_db;
        }
    } catch (Exception $e) {
        // Error getting settings, use defaults
    }
}

// Function to get recent articles
function getRecentArticles($limit = 4) {
    global $pdo;
    
    if (!isset($pdo) || !$pdo) {
        // Return sample data if no database
        return [
            [
                'id' => 1,
                'title' => 'Ramchargers Conquer The Automatic',
                'slug' => 'ramchargers-conquer-the-automatic',
                'content' => 'In the early 1960s, a renegade group of Chrysler engineers known as The Ramchargers were rewriting the rules of drag racing. Operating out of Detroit...',
                'author' => 'Don Althaus',
                'created_at' => '2025-09-01',
                'featured_image' => ''
            ],
            [
                'id' => 2,
                'title' => 'The title is about the dog!',
                'slug' => 'welcome',
                'content' => 'The quick brown fox jumped over the lazy dog\'s back but landed in the snow bank...giving the dog a good laugh as he was...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-29',
                'featured_image' => ''
            ],
            [
                'id' => 3,
                'title' => 'Street Photography in Urban Landscapes',
                'slug' => 'street-photography-urban',
                'content' => 'Capturing the essence of city life through the lens requires patience, timing, and an eye for the extraordinary in the ordinary...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-25',
                'featured_image' => ''
            ],
            [
                'id' => 4,
                'title' => 'Light and Shadow: The Art of Portrait Photography',
                'slug' => 'light-shadow-portraits',
                'content' => 'Understanding how light interacts with the human form is fundamental to creating compelling portraits that speak to the soul...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-20',
                'featured_image' => ''
            ]
        ];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, slug, body as content, author, created_at, featured_image 
            FROM content 
            WHERE type = 'article' AND status = 'published' 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no results from database, return sample data
        if (empty($results)) {
            return [
                [
                    'id' => 1,
                    'title' => 'Ramchargers Conquer The Automatic',
                    'slug' => 'ramchargers-conquer-the-automatic',
                    'content' => 'In the early 1960s, a renegade group of Chrysler engineers known as The Ramchargers were rewriting the rules of drag racing. Operating out of Detroit...',
                    'author' => 'Don Althaus',
                    'created_at' => '2025-09-01',
                    'featured_image' => ''
                ],
                [
                    'id' => 2,
                    'title' => 'The title is about the dog!',
                    'slug' => 'welcome',
                    'content' => 'The quick brown fox jumped over the lazy dog\'s back but landed in the snow bank...giving the dog a good laugh as he was...',
                    'author' => 'Don Althaus',
                    'created_at' => '2025-08-29',
                    'featured_image' => ''
                ],
                [
                    'id' => 3,
                    'title' => 'Street Photography in Urban Landscapes',
                    'slug' => 'street-photography-urban',
                    'content' => 'Capturing the essence of city life through the lens requires patience, timing, and an eye for the extraordinary in the ordinary...',
                    'author' => 'Don Althaus',
                    'created_at' => '2025-08-25',
                    'featured_image' => ''
                ],
                [
                    'id' => 4,
                    'title' => 'Light and Shadow: The Art of Portrait Photography',
                    'slug' => 'light-shadow-portraits',
                    'content' => 'Understanding how light interacts with the human form is fundamental to creating compelling portraits that speak to the soul...',
                    'author' => 'Don Althaus',
                    'created_at' => '2025-08-20',
                    'featured_image' => ''
                ]
            ];
        }
        
        return $results;
    } catch (PDOException $e) {
        error_log('Error getting articles: ' . $e->getMessage());
        // Return sample data on database error
        return [
            [
                'id' => 1,
                'title' => 'Ramchargers Conquer The Automatic',
                'slug' => 'ramchargers-conquer-the-automatic',
                'content' => 'In the early 1960s, a renegade group of Chrysler engineers known as The Ramchargers were rewriting the rules of drag racing. Operating out of Detroit...',
                'author' => 'Don Althaus',
                'created_at' => '2025-09-01',
                'featured_image' => ''
            ],
            [
                'id' => 2,
                'title' => 'The title is about the dog!',
                'slug' => 'welcome',
                'content' => 'The quick brown fox jumped over the lazy dog\'s back but landed in the snow bank...giving the dog a good laugh as he was...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-29',
                'featured_image' => ''
            ],
            [
                'id' => 3,
                'title' => 'Street Photography in Urban Landscapes',
                'slug' => 'street-photography-urban',
                'content' => 'Capturing the essence of city life through the lens requires patience, timing, and an eye for the extraordinary in the ordinary...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-25',
                'featured_image' => ''
            ],
            [
                'id' => 4,
                'title' => 'Light and Shadow: The Art of Portrait Photography',
                'slug' => 'light-shadow-portraits',
                'content' => 'Understanding how light interacts with the human form is fundamental to creating compelling portraits that speak to the soul...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-20',
                'featured_image' => ''
            ]
        ];
    }
}

// Function to get recent photobooks
function getRecentPhotobooks($limit = 4) {
    global $pdo;
    
    if (!isset($pdo) || !$pdo) {
        // Return sample data if no database
        return [
            [
                'id' => 1,
                'title' => 'The Storyteller\'s Legacy',
                'slug' => 'storytellers-legacy',
                'description' => 'Once upon a time, in a small village nestled between rolling hills and ancient forests, there lived a young photographer named Elena. She had inherited...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-29',
                'cover_image' => ''
            ],
            [
                'id' => 2,
                'title' => 'Moments in Time',
                'slug' => 'moments-in-time',
                'description' => 'A collection of candid moments captured during street photography sessions across various cities, showcasing the beauty of everyday life...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-15',
                'cover_image' => ''
            ],
            [
                'id' => 3,
                'title' => 'Natural Wonders',
                'slug' => 'natural-wonders',
                'description' => 'Exploring the breathtaking landscapes and wildlife found in national parks, captured through the lens of environmental photography...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-10',
                'cover_image' => ''
            ]
        ];
    }
    
    try {
        $stmt = $pdo->prepare("
            SELECT id, title, slug, teaser_text as description, author, created_at, teaser_image as cover_image 
            FROM content 
            WHERE type = 'photobook' AND status = 'published' 
            ORDER BY created_at DESC 
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // If no results from database, return sample data
        if (empty($results)) {
            return [
                [
                    'id' => 1,
                    'title' => 'The Storyteller\'s Legacy',
                    'slug' => 'storytellers-legacy',
                    'description' => 'Once upon a time, in a small village nestled between rolling hills and ancient forests, there lived a young photographer named Elena. She had inherited...',
                    'author' => 'Don Althaus',
                    'created_at' => '2025-08-29',
                    'cover_image' => ''
                ],
                [
                    'id' => 2,
                    'title' => 'Moments in Time',
                    'slug' => 'moments-in-time',
                    'description' => 'A collection of candid moments captured during street photography sessions across various cities, showcasing the beauty of everyday life...',
                    'author' => 'Don Althaus',
                    'created_at' => '2025-08-15',
                    'cover_image' => ''
                ],
                [
                    'id' => 3,
                    'title' => 'Natural Wonders',
                    'slug' => 'natural-wonders',
                    'description' => 'Exploring the breathtaking landscapes and wildlife found in national parks, captured through the lens of environmental photography...',
                    'author' => 'Don Althaus',
                    'created_at' => '2025-08-10',
                    'cover_image' => ''
                ]
            ];
        }
        
        return $results;
    } catch (PDOException $e) {
        error_log('Error getting photobooks: ' . $e->getMessage());
        // Return sample data on database error
        return [
            [
                'id' => 1,
                'title' => 'The Storyteller\'s Legacy',
                'slug' => 'storytellers-legacy',
                'description' => 'Once upon a time, in a small village nestled between rolling hills and ancient forests, there lived a young photographer named Elena. She had inherited...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-29',
                'cover_image' => ''
            ],
            [
                'id' => 2,
                'title' => 'Moments in Time',
                'slug' => 'moments-in-time',
                'description' => 'A collection of candid moments captured during street photography sessions across various cities, showcasing the beauty of everyday life...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-15',
                'cover_image' => ''
            ],
            [
                'id' => 3,
                'title' => 'Natural Wonders',
                'slug' => 'natural-wonders',
                'description' => 'Exploring the breathtaking landscapes and wildlife found in national parks, captured through the lens of environmental photography...',
                'author' => 'Don Althaus',
                'created_at' => '2025-08-10',
                'cover_image' => ''
            ]
        ];
    }
}

// Get the data
$articles = getRecentArticles(4);
$photobooks = getRecentPhotobooks(3);

// Debug: Log what we got
error_log("Articles count: " . (is_array($articles) ? count($articles) : "not array"));
error_log("Photobooks count: " . (is_array($photobooks) ? count($photobooks) : "not array"));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;700&family=Gelasio:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Gelasio', serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }
        
        /* Header Styles */
        .header {
            padding: 40px 20px;
            text-align: center;
            border-bottom: 1px solid #e0e0e0;
            position: relative;
        }
        
        .site-title {
            font-family: 'Arimo', sans-serif;
            font-size: 2.5rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .site-slogan {
            font-size: 1.1rem;
            color: #7f8c8d;
            font-style: italic;
        }
        
        /* Hamburger Menu */
        .hamburger-menu {
            position: absolute;
            top: 30px;
            right: 30px;
            z-index: 1000;
            cursor: pointer;
            width: 30px;
            height: 25px;
        }
        
        .hamburger-menu span {
            display: block;
            width: 100%;
            height: 3px;
            background-color: #2c3e50;
            margin: 5px 0;
            transition: all 0.3s ease;
        }
        
        /* Mobile Navigation */
        .mobile-nav {
            position: fixed;
            top: 0;
            right: -300px;
            width: 280px;
            height: 100vh;
            background: white;
            box-shadow: -2px 0 10px rgba(0,0,0,0.1);
            transition: right 0.3s ease;
            z-index: 999;
            padding: 80px 20px 20px;
            overflow-y: auto;
        }
        
        .mobile-nav.active {
            right: 0;
        }
        
        .mobile-nav a {
            display: block;
            padding: 15px 10px;
            color: #2c3e50;
            text-decoration: none;
            border-bottom: 1px solid #eee;
            font-size: 16px;
            transition: background 0.2s ease;
        }
        
        .mobile-nav a:hover {
            background: #f5f5f5;
            padding-left: 20px;
        }
        
        .nav-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease;
            z-index: 998;
        }
        
        .nav-overlay.active {
            opacity: 1;
            visibility: visible;
        }
        
        /* Main content */
        .main-content {
            flex: 1;
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        /* Two column layout */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-top: 20px;
        }
        
        .section-title {
            font-family: 'Arimo', sans-serif;
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 10px;
        }
        
        /* Articles Section (Left Column) - Horizontal Layout */
        .articles-section {
            display: flex;
            flex-direction: column;
        }
        
        .article-item {
            display: grid;
            grid-template-columns: 160px 1fr;
            gap: 20px;
            padding: 20px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .article-item:last-child {
            border-bottom: none;
        }
        
        .article-image {
            width: 160px;
            height: 120px;
            aspect-ratio: 4/3;
            background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%);
            border-radius: 8px;
            flex-shrink: 0;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 0.9rem;
        }
        
        .article-content {
            display: flex;
            flex-direction: column;
        }
        
        .article-item h3 {
            font-family: 'Arimo', sans-serif;
            color: #2c3e50;
            margin: 0 0 8px 0;
            font-size: 1.2rem;
            font-weight: 700;
            line-height: 1.3;
        }
        
        .article-meta {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .article-excerpt {
            color: #555;
            line-height: 1.5;
            margin: 0 0 10px 0;
            font-size: 0.95rem;
        }
        
        .article-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            align-self: flex-start;
        }
        
        .article-link:hover {
            text-decoration: underline;
            color: #5dade2;
        }
        
        /* Photobooks Section (Right Column) - Vertical Layout */
        .photobooks-section {
            display: flex;
            flex-direction: column;
        }
        
        .photobook-item {
            display: flex;
            flex-direction: column;
            padding: 20px 0;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .photobook-item:last-child {
            border-bottom: none;
        }
        
        .photobook-image {
            width: 100%;
            height: 200px;
            aspect-ratio: 4/3;
            background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%);
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 0.9rem;
        }
        
        .photobook-content {
            display: flex;
            flex-direction: column;
        }
        
        .photobook-item h3 {
            font-family: 'Arimo', sans-serif;
            color: #2c3e50;
            margin: 0 0 8px 0;
            font-size: 1.3rem;
            font-weight: 700;
            line-height: 1.3;
        }
        
        .photobook-meta {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-bottom: 10px;
        }
        
        .photobook-description {
            color: #555;
            line-height: 1.5;
            margin: 0 0 10px 0;
            font-size: 0.95rem;
        }
        
        .photobook-link {
            color: #3498db;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            align-self: flex-start;
        }
        
        .photobook-link:hover {
            text-decoration: underline;
            color: #5dade2;
        }
        
        /* Footer */
        .footer {
            background: transparent;
            color: #7f8c8d;
            text-align: center;
            padding: 40px 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .footer-links {
            margin-top: 15px;
        }
        
        .footer-links a {
            color: #3498db;
            text-decoration: none;
            padding: 0 10px;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
            
            .article-item {
                grid-template-columns: 120px 1fr;
                gap: 15px;
            }
            
            .article-image {
                width: 120px;
                height: 90px;
            }
            
            .photobook-image {
                height: 180px;
            }
            
            .hamburger-menu {
                right: 20px;
                top: 20px;
            }
            
            .site-title {
                font-size: 2rem;
            }
        }
        
        @media (max-width: 480px) {
            .main-content {
                padding: 20px 15px;
            }
            
            .article-item {
                grid-template-columns: 1fr;
                gap: 12px;
            }
            
            .article-image {
                width: 100%;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <!-- Hamburger Menu -->
    <div class="hamburger-menu" id="hamburgerMenu">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <!-- Header -->
    <header class="header">
        <h1 class="site-title"><?php echo htmlspecialchars($site_title); ?></h1>
        <p class="site-slogan"><?php echo htmlspecialchars($site_motto); ?></p>
    </header>

    <!-- Mobile Navigation -->
    <nav class="mobile-nav" id="mobileNav">
        <a href="/">Home</a>
        <a href="/articles">Articles</a>
        <a href="/photobooks">Photobooks</a>
        <a href="/about">About</a>
        <a href="/contact">Contact</a>
    </nav>

    <!-- Navigation Overlay -->
    <div class="nav-overlay" id="navOverlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-grid">
            <!-- Articles Section (Left Column) -->
            <section class="articles-section">
                <h2 class="section-title">Articles</h2>
                <!-- DEBUG: Articles count: <?php echo count($articles); ?> -->
                <?php if (!empty($articles)): ?>
                    <?php foreach ($articles as $article): ?>
                        <article class="article-item">
                            <div class="article-image">
                                <?php if (!empty($article['featured_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($article['featured_image']); ?>" alt="<?php echo htmlspecialchars($article['title']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                <?php else: ?>
                                    Article Image
                                <?php endif; ?>
                            </div>
                            <div class="article-content">
                                <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                                <div class="article-meta">
                                    <?php echo htmlspecialchars($article['author']); ?> · <?php echo date('F j, Y', strtotime($article['created_at'])); ?>
                                </div>
                                <p class="article-excerpt">
                                    <?php 
                                    $excerpt = strip_tags($article['content']);
                                    echo htmlspecialchars(strlen($excerpt) > 120 ? substr($excerpt, 0, 120) . '...' : $excerpt);
                                    ?>
                                </p>
                                <a href="/articles" class="article-link">Read more →</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No articles found.</p>
                <?php endif; ?>
            </section>

            <!-- Photobooks Section (Right Column) -->
            <section class="photobooks-section">
                <h2 class="section-title">Photobooks</h2>
                <!-- DEBUG: Photobooks count: <?php echo count($photobooks); ?> -->
                <?php if (!empty($photobooks)): ?>
                    <?php foreach ($photobooks as $photobook): ?>
                        <article class="photobook-item">
                            <div class="photobook-image">
                                <?php if (!empty($photobook['cover_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($photobook['cover_image']); ?>" alt="<?php echo htmlspecialchars($photobook['title']); ?>" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                                <?php else: ?>
                                    Photobook Cover
                                <?php endif; ?>
                            </div>
                            <div class="photobook-content">
                                <h3><?php echo htmlspecialchars($photobook['title']); ?></h3>
                                <div class="photobook-meta">
                                    <?php echo htmlspecialchars($photobook['author']); ?> · <?php echo date('F j, Y', strtotime($photobook['created_at'])); ?>
                                </div>
                                <p class="photobook-description">
                                    <?php 
                                    $description = strip_tags($photobook['description']);
                                    echo htmlspecialchars(strlen($description) > 120 ? substr($description, 0, 120) . '...' : $description);
                                    ?>
                                </p>
                                <a href="/photobooks" class="photobook-link">View photobook →</a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No photobooks found.</p>
                <?php endif; ?>
            </section>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars($site_title); ?>. All rights reserved.</p>
        <div class="footer-links">
            <a href="/privacy">Privacy Policy</a>
            <span>|</span>
            <a href="/terms">Terms of Service</a>
            <span>|</span>
            <a href="/contact">Contact</a>
        </div>
    </footer>

    <script>
        // Hamburger menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            const mobileNav = document.getElementById('mobileNav');
            const navOverlay = document.getElementById('navOverlay');
            
            function toggleMenu() {
                hamburgerMenu.classList.toggle('active');
                mobileNav.classList.toggle('active');
                navOverlay.classList.toggle('active');
                
                if (mobileNav.classList.contains('active')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }
            
            hamburgerMenu.addEventListener('click', toggleMenu);
            navOverlay.addEventListener('click', toggleMenu);
        });
    </script>
</body>
</html>