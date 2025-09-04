<?php
declare(strict_types=1);

// Simple database connection
$conn = @mysqli_connect('localhost', 'dalthaus_photocms', 'f-I*GSo^Urt*k*&#', 'dalthaus_photocms');

if (!$conn) {
    http_response_code(500);
    echo "Database connection failed";
    exit;
}

// Get published articles
$articles = [];
$result = mysqli_query($conn, "SELECT * FROM content WHERE type = 'article' AND status = 'published' AND deleted_at IS NULL ORDER BY sort_order, created_at DESC LIMIT 10");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $articles[] = $row;
    }
}

// Get published photobooks  
$photobooks = [];
$result = mysqli_query($conn, "SELECT * FROM content WHERE type = 'photobook' AND status = 'published' AND deleted_at IS NULL ORDER BY sort_order, created_at DESC LIMIT 10");
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $photobooks[] = $row;
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dalthaus Photography</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;500;600&family=Gelasio:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Gelasio', serif;
            background: rgb(248, 248, 248);
            color: rgb(20, 20, 20);
            line-height: 1.6;
        }
        
        .header {
            background: rgb(44, 62, 80);
            color: white;
            padding: 1rem 0;
        }
        
        .nav {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-family: 'Arimo', sans-serif;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .nav-links {
            display: flex;
            gap: 2rem;
        }
        
        .nav-links a {
            color: white;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }
        
        .nav-links a:hover {
            opacity: 0.8;
        }
        
        .main-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .content-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 3rem;
        }
        
        .section-title {
            font-family: 'Arimo', sans-serif;
            font-size: 1.5rem;
            margin-bottom: 1.5rem;
            color: rgb(44, 62, 80);
            border-bottom: 2px solid rgb(52, 152, 219);
            padding-bottom: 0.5rem;
        }
        
        .front-article-item {
            background: white;
            border-radius: 4px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .front-article-title {
            font-family: 'Arimo', sans-serif;
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .front-article-title a {
            color: rgb(44, 62, 80);
            text-decoration: none;
        }
        
        .front-article-title a:hover {
            color: rgb(52, 152, 219);
        }
        
        .front-article-meta {
            color: rgb(127, 140, 141);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .front-article-teaser {
            color: rgb(60, 60, 60);
        }
        
        .front-photobook-item {
            background: white;
            border-radius: 4px;
            padding: 1rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .front-photobook-title {
            font-family: 'Arimo', sans-serif;
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
        }
        
        .front-photobook-title a {
            color: rgb(142, 68, 173);
            text-decoration: none;
        }
        
        .front-photobook-title a:hover {
            opacity: 0.8;
        }
        
        .front-photobook-meta {
            color: rgb(127, 140, 141);
            font-size: 0.8rem;
            margin-bottom: 0.5rem;
        }
        
        .front-photobook-excerpt {
            font-size: 0.9rem;
            color: rgb(60, 60, 60);
        }
        
        .no-content {
            color: rgb(127, 140, 141);
            font-style: italic;
        }
        
        @media (max-width: 768px) {
            .content-layout {
                grid-template-columns: 1fr;
                gap: 2rem;
            }
        }
    </style>
</head>
<body>

    <header class="header">
        <nav class="nav">
            <div class="logo">Dalthaus Photography</div>
            <div class="nav-links">
                <a href="/">Home</a>
                <a href="/admin/login.php">Admin</a>
            </div>
        </nav>
    </header>
    
    <main class="main-content" role="main">
        <div class="content-layout">
            <section class="articles-section">
                <h2 class="section-title">Articles</h2>
                <div class="articles-list">
                    <?php if ($articles && count($articles) > 0): ?>
                        <?php foreach ($articles as $article): ?>
                        <article class="front-article-item">
                            <h3 class="front-article-title">
                                <a href="/article/<?= htmlspecialchars($article['slug']) ?>">
                                    <?= htmlspecialchars($article['title']) ?>
                                </a>
                            </h3>
                            <div class="front-article-meta">
                                Don Althaus · <?= date('d F Y', strtotime($article['published_at'] ?? $article['created_at'])) ?> · Articles
                            </div>
                            <div class="front-article-teaser">
                                <?= htmlspecialchars(mb_substr(strip_tags($article['body'] ?? ''), 0, 150) . '...') ?>
                            </div>
                        </article>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="no-content">No articles published yet.</p>
                    <?php endif; ?>
                </div>
            </section>
            
            <aside class="photobooks-section">
                <h2 class="section-title">Photo Books</h2>
                <div class="photobooks-list">
                    <?php if ($photobooks && count($photobooks) > 0): ?>
                        <?php foreach ($photobooks as $book): ?>
                        <div class="front-photobook-item">
                            <h3 class="front-photobook-title">
                                <a href="/photobook/<?= htmlspecialchars($book['slug']) ?>">
                                    <?= htmlspecialchars($book['title']) ?>
                                </a>
                            </h3>
                            <div class="front-photobook-meta">
                                Don Althaus · <?= date('d F Y', strtotime($book['published_at'] ?? $book['created_at'])) ?> · Photo Books
                            </div>
                            <div class="front-photobook-excerpt">
                                <?= htmlspecialchars(mb_substr(strip_tags($book['body'] ?? ''), 0, 120) . '...') ?>
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
</body>
</html>