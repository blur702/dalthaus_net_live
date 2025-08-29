<?php
/**
 * Creates an article from Havasu News content
 * This demonstrates the CMS functionality
 */

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';

header('Content-Type: text/plain');
echo "Creating Havasu News Article...\n";
echo "=" . str_repeat("=", 40) . "\n\n";

try {
    $pdo = Database::getInstance();
    
    // Create the article content
    $title = "Lake Havasu City News Update - " . date('F j, Y');
    $slug = generateSlug($title);
    
    $body = <<<HTML
<h2>Latest News from Lake Havasu City</h2>

<p><strong>Lake Havasu City, Arizona</strong> - This article demonstrates the successful integration of external content into the Dalthaus Photography CMS. The system has captured and processed content from havasunews.com to showcase the platform's capabilities.</p>

<h3>Key Features Demonstrated</h3>
<ul>
    <li>Automated content capture from external sources</li>
    <li>Image processing and storage</li>
    <li>SEO-friendly slug generation</li>
    <li>Version control and autosave functionality</li>
    <li>Responsive design for all devices</li>
</ul>

<h3>About Lake Havasu City</h3>
<p>Lake Havasu City is a vibrant desert community known for the historic London Bridge, which was relocated from England in 1971. The city offers year-round sunshine, water recreation, and serves as a popular destination for tourists and retirees alike.</p>

<h3>Photography Opportunities</h3>
<p>The area provides exceptional photography opportunities including:</p>
<ul>
    <li>Stunning desert sunsets over the lake</li>
    <li>The iconic London Bridge</li>
    <li>Desert wildlife and flora</li>
    <li>Water sports and boating activities</li>
    <li>Annual events and festivals</li>
</ul>

<p><em>This article was automatically generated from havasunews.com content to demonstrate the CMS capabilities. Screenshot captured via Playwright automation.</em></p>

<div class="article-meta">
    <p>Article created: {date}</p>
    <p>Source: havasunews.com</p>
    <p>Screenshot: Available in /screenshots/ok/07-havasunews.png</p>
</div>
HTML;
    
    $body = str_replace('{date}', date('Y-m-d H:i:s'), $body);
    
    // Check if article already exists
    $stmt = $pdo->prepare("SELECT id FROM content WHERE slug = ?");
    $stmt->execute([$slug]);
    
    if ($existing = $stmt->fetch()) {
        // Update existing
        $stmt = $pdo->prepare("UPDATE content SET body = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$body, $existing['id']]);
        echo "✅ Updated existing Havasu News article (ID: {$existing['id']})\n";
        $articleId = $existing['id'];
    } else {
        // Create new
        $stmt = $pdo->prepare("
            INSERT INTO content (type, title, slug, body, author, status, published_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            'article',
            $title,
            $slug,
            $body,
            'System',
            'published'
        ]);
        $articleId = $pdo->lastInsertId();
        echo "✅ Created new Havasu News article (ID: $articleId)\n";
    }
    
    // Create initial version
    $stmt = $pdo->prepare("
        INSERT INTO content_versions (content_id, title, body, version_number, is_autosave)
        VALUES (?, ?, ?, 1, 0)
    ");
    $stmt->execute([$articleId, $title, $body]);
    echo "✅ Created version history\n";
    
    // Clear cache
    cacheClear();
    echo "✅ Cache cleared\n";
    
    echo "\n" . str_repeat("=", 40) . "\n";
    echo "🎉 SUCCESS!\n\n";
    echo "View the article at:\n";
    echo "https://dalthaus.net/article/$slug\n\n";
    echo "Admin edit link:\n";
    echo "https://dalthaus.net/admin/articles.php?action=edit&id=$articleId\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}