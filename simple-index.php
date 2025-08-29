<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$pdo = Database::getInstance();

// Get published articles
$articles = $pdo->query("
    SELECT * FROM content 
    WHERE type = 'article' 
    AND status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll();

// Get published photobooks
$photobooks = $pdo->query("
    SELECT * FROM content 
    WHERE type = 'photobook' 
    AND status = 'published' 
    AND deleted_at IS NULL 
    ORDER BY created_at DESC 
    LIMIT 10
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dalthaus Photography</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
        }
        
        .header {
            background: #2c3e50;
            color: white;
            padding: 2rem 0;
            text-align: center;
        }
        
        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }
        
        .nav {
            background: #34495e;
            padding: 1rem 0;
            text-align: center;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            margin: 0 1rem;
            padding: 0.5rem 1rem;
            display: inline-block;
            transition: background 0.3s;
        }
        
        .nav a:hover {
            background: rgba(255,255,255,0.1);
            border-radius: 4px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }
        
        .section {
            background: white;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .section h2 {
            color: #2c3e50;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #3498db;
        }
        
        .article-item, .photobook-item {
            margin-bottom: 1.5rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .article-item:last-child, .photobook-item:last-child {
            border-bottom: none;
        }
        
        .article-item h3, .photobook-item h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .article-item a, .photobook-item a {
            color: #3498db;
            text-decoration: none;
        }
        
        .article-item a:hover, .photobook-item a:hover {
            text-decoration: underline;
        }
        
        .meta {
            font-size: 0.9rem;
            color: #7f8c8d;
            margin-bottom: 0.5rem;
        }
        
        .excerpt {
            color: #555;
        }
        
        .no-content {
            color: #7f8c8d;
            font-style: italic;
            padding: 1rem;
            text-align: center;
            background: #f8f9fa;
            border-radius: 4px;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 2rem 0;
            margin-top: 3rem;
        }
        
        .footer a {
            color: #3498db;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
            
            .nav a {
                display: block;
                margin: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <h1>Dalthaus Photography</h1>
        <p>Capturing moments in time</p>
    </header>
    
    <nav class="nav">
        <a href="/">Home</a>
        <a href="/public/articles.php">Articles</a>
        <a href="/public/photobooks.php">Photo Books</a>
        <a href="/admin/login.php">Admin</a>
    </nav>
    
    <div class="container">
        <div class="content-grid">
            <!-- Articles Section -->
            <div class="section">
                <h2>Recent Articles</h2>
                <?php if (empty($articles)): ?>
                    <div class="no-content">No articles published yet.</div>
                <?php else: ?>
                    <?php foreach ($articles as $article): ?>
                        <div class="article-item">
                            <h3><a href="/article/<?= htmlspecialchars($article['slug']) ?>"><?= htmlspecialchars($article['title']) ?></a></h3>
                            <div class="meta">
                                By <?= htmlspecialchars($article['author'] ?? 'Admin') ?> · 
                                <?= date('F j, Y', strtotime($article['published_at'] ?? $article['created_at'])) ?>
                            </div>
                            <div class="excerpt">
                                <?php
                                $excerpt = strip_tags($article['body']);
                                echo htmlspecialchars(substr($excerpt, 0, 200)) . (strlen($excerpt) > 200 ? '...' : '');
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Photobooks Section -->
            <div class="section">
                <h2>Photo Books</h2>
                <?php if (empty($photobooks)): ?>
                    <div class="no-content">No photo books published yet.</div>
                <?php else: ?>
                    <?php foreach ($photobooks as $book): ?>
                        <div class="photobook-item">
                            <h3><a href="/photobook/<?= htmlspecialchars($book['slug']) ?>"><?= htmlspecialchars($book['title']) ?></a></h3>
                            <div class="meta">
                                <?= date('F Y', strtotime($book['published_at'] ?? $book['created_at'])) ?>
                            </div>
                            <div class="excerpt">
                                <?php
                                $excerpt = strip_tags($book['body']);
                                echo htmlspecialchars(substr($excerpt, 0, 150)) . (strlen($excerpt) > 150 ? '...' : '');
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <footer class="footer">
        <p>&copy; <?= date('Y') ?> Dalthaus Photography. All rights reserved.</p>
        <p><a href="/admin/login.php">Admin Login</a> | <a href="/set-maintenance.php?token=maint-<?= date('Ymd') ?>">Maintenance Mode</a></p>
    </footer>
</body>
</html>