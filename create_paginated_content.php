<?php

declare(strict_types=1);

require_once __DIR__ . '/vendor/autoload.php';

use CMS\Models\Content;
use CMS\Models\Page;
use CMS\Utils\Database;

// Initialize database
$config = require __DIR__ . '/config/config.php';
Database::getInstance($config['database']);

echo "Creating Sample Content with Pagebreaks\n";
echo "========================================\n\n";

// Create an article with pagebreaks
echo "Creating article with pagebreaks...\n";
$article = new Content();
$article->setAttribute('title', 'Multi-Page Article Example');
$article->setAttribute('url_alias', 'multi-page-article');
$article->setAttribute('teaser', 'This article demonstrates the pagination feature with multiple pages.');
$article->setAttribute('body', '
<h2>Chapter 1: Introduction</h2>
<p>This is the first page of our multi-page article. The pagination feature allows you to split long content into multiple pages for better readability.</p>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.</p>
<p>Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.</p>

<hr class="mce-pagebreak" />

<h2>Chapter 2: The Middle</h2>
<p>This is the second page of our article. You can navigate between pages using the pagination controls at the bottom.</p>
<p>Sed ut perspiciatis unde omnis iste natus error sit voluptatem accusantium doloremque laudantium, totam rem aperiam, eaque ipsa quae ab illo inventore veritatis et quasi architecto beatae vitae dicta sunt explicabo.</p>
<p>Nemo enim ipsam voluptatem quia voluptas sit aspernatur aut odit aut fugit, sed quia consequuntur magni dolores eos qui ratione voluptatem sequi nesciunt.</p>

<hr class="mce-pagebreak" />

<h2>Chapter 3: Advanced Topics</h2>
<p>This is the third page. The pagebreak feature works seamlessly with the TinyMCE editor.</p>
<p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident.</p>
<p>Similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga.</p>

<hr class="mce-pagebreak" />

<h2>Chapter 4: Conclusion</h2>
<p>This is the final page of our multi-page article. The pagination system automatically detects the pagebreaks and creates navigation controls.</p>
<p>Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus.</p>
<p>Thank you for reading this multi-page article demonstration!</p>
');
$article->setAttribute('content_type', Content::TYPE_ARTICLE);
$article->setAttribute('status', Content::STATUS_PUBLISHED);
$article->setAttribute('user_id', 2); // kevin user
$article->setAttribute('published_at', date('Y-m-d H:i:s'));
$article->setAttribute('created_at', date('Y-m-d H:i:s'));
$article->setAttribute('updated_at', date('Y-m-d H:i:s'));

if ($article->save()) {
    echo "✓ Article created successfully with 4 pages\n";
} else {
    echo "✗ Failed to create article\n";
}

// Create a photobook with pagebreaks
echo "\nCreating photobook with pagebreaks...\n";
$photobook = new Content();
$photobook->setAttribute('title', 'Gallery with Multiple Pages');
$photobook->setAttribute('url_alias', 'paginated-gallery');
$photobook->setAttribute('teaser', 'A photo gallery spread across multiple pages.');
$photobook->setAttribute('body', '
<h2>Gallery Page 1</h2>
<p>Welcome to our photo gallery. This gallery is split across multiple pages for easier browsing.</p>
<img src="/uploads/sample1.jpg" alt="Sample Image 1" style="max-width: 100%;">
<p>Beautiful landscape photography from around the world.</p>

<hr class="mce-pagebreak" />

<h2>Gallery Page 2</h2>
<p>More stunning photographs on the second page.</p>
<img src="/uploads/sample2.jpg" alt="Sample Image 2" style="max-width: 100%;">
<p>Urban photography capturing city life.</p>

<hr class="mce-pagebreak" />

<h2>Gallery Page 3</h2>
<p>The final page of our gallery.</p>
<img src="/uploads/sample3.jpg" alt="Sample Image 3" style="max-width: 100%;">
<p>Nature and wildlife photography.</p>
');
$photobook->setAttribute('content_type', Content::TYPE_PHOTOBOOK);
$photobook->setAttribute('status', Content::STATUS_PUBLISHED);
$photobook->setAttribute('user_id', 2); // kevin user
$photobook->setAttribute('published_at', date('Y-m-d H:i:s'));
$photobook->setAttribute('created_at', date('Y-m-d H:i:s'));
$photobook->setAttribute('updated_at', date('Y-m-d H:i:s'));

if ($photobook->save()) {
    echo "✓ Photobook created successfully with 3 pages\n";
} else {
    echo "✗ Failed to create photobook\n";
}

// Create a page with pagebreaks
echo "\nCreating static page with pagebreaks...\n";
$page = new Page();
$page->setAttribute('title', 'About Us - Multi Section');
$page->setAttribute('url_alias', 'about-multi-section');
$page->setAttribute('body', '
<h2>Our History</h2>
<p>Founded in 2020, our company has grown from a small startup to a leading provider in our industry.</p>
<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Our journey began with a simple idea: to make technology accessible to everyone.</p>

<hr class="mce-pagebreak" />

<h2>Our Mission</h2>
<p>We are dedicated to providing innovative solutions that make a difference in people\'s lives.</p>
<p>Our mission is to empower individuals and businesses through cutting-edge technology and exceptional service.</p>

<hr class="mce-pagebreak" />

<h2>Our Team</h2>
<p>Meet the talented individuals who make our success possible.</p>
<p>Our diverse team brings together expertise from various fields to deliver outstanding results for our clients.</p>
');
$page->setAttribute('meta_description', 'Learn about our company across multiple sections');
// Pages table only has updated_at column
$page->setAttribute('updated_at', date('Y-m-d H:i:s'));

if ($page->save()) {
    echo "✓ Page created successfully with 3 sections\n";
} else {
    echo "✗ Failed to create page\n";
}

echo "\n========================================\n";
echo "Sample content with pagebreaks created!\n";
echo "========================================\n\n";

echo "You can now visit:\n";
echo "- /article/multi-page-article (4 pages)\n";
echo "- /photobook/paginated-gallery (3 pages)\n";
echo "- /page/about-multi-section (3 sections)\n";
echo "\nUse the pagination controls at the bottom to navigate between pages.\n";