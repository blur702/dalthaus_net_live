<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

Auth::requireAdmin();
$pdo = Database::getInstance();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
            case 'update':
                $title = sanitizeInput($_POST['title'] ?? '');
                $body = $_POST['body'] ?? '';
                $status = $_POST['status'] ?? 'draft';
                $slug = $_POST['slug'] ?? createSlug($title);
                $author = sanitizeInput($_POST['author'] ?? 'Don Althaus');
                
                if ($action === 'create') {
                    $stmt = $pdo->prepare("
                        INSERT INTO content (type, title, slug, author, body, status, published_at) 
                        VALUES ('page', ?, ?, ?, ?, ?, ?)
                    ");
                    $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
                    if ($stmt->execute([$title, $slug, $author, $body, $status, $publishedAt])) {
                        $contentId = $pdo->lastInsertId();
                        // Create initial version
                        $stmt = $pdo->prepare("
                            INSERT INTO content_versions (content_id, version_number, title, body) 
                            VALUES (?, 1, ?, ?)
                        ");
                        $stmt->execute([$contentId, $title, $body]);
                        $message = 'Page created successfully';
                    }
                } else {
                    $id = (int)$_POST['id'];
                    // Check if we're publishing for the first time
                    $existingStatus = $pdo->query("SELECT status, published_at FROM content WHERE id = $id")->fetch();
                    $publishedAt = null;
                    if ($status === 'published' && $existingStatus['status'] === 'draft' && !$existingStatus['published_at']) {
                        $publishedAt = date('Y-m-d H:i:s');
                    }
                    
                    $stmt = $pdo->prepare("
                        UPDATE content 
                        SET title = ?, slug = ?, author = ?, body = ?, status = ?" . 
                        ($publishedAt ? ", published_at = ?" : "") . "
                        WHERE id = ? AND type = 'page'
                    ");
                    $params = [$title, $slug, $author, $body, $status];
                    if ($publishedAt) $params[] = $publishedAt;
                    $params[] = $id;
                    if ($stmt->execute($params)) {
                        // Create new version
                        $versionNum = $pdo->query("SELECT MAX(version_number) FROM content_versions WHERE content_id = $id")->fetchColumn() + 1;
                        $stmt = $pdo->prepare("
                            INSERT INTO content_versions (content_id, version_number, title, body, is_autosave) 
                            VALUES (?, ?, ?, ?, FALSE)
                        ");
                        $stmt->execute([$id, $versionNum, $title, $body]);
                        $message = 'Page updated successfully';
                        cacheClear();
                    }
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE content SET deleted_at = NOW() WHERE id = ? AND type = 'page'");
                if ($stmt->execute([$id])) {
                    $message = 'Page moved to trash';
                    cacheClear();
                }
                break;
        }
    }
}

// Get pages
$query = "SELECT * FROM content WHERE type = 'page' AND deleted_at IS NULL ORDER BY sort_order, created_at DESC";
$pages = $pdo->query($query)->fetchAll();

// Get page for editing if ID provided
$editPage = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM content WHERE id = ? AND type = 'page'");
    $stmt->execute([$_GET['edit']]);
    $editPage = $stmt->fetch();
}

$csrf = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Pages</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">    <link rel="stylesheet" href="/assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/tinymce@6/tinymce.min.js"></script>
</head>
<body>
    <div class="admin-wrapper">
                <nav class="admin-nav">
            <h1>CMS Admin</h1>
            <ul>
                <li><a href="/admin/">Dashboard</a></li>
                <li><a href="/admin/articles.php">Articles</a></li>
                <li><a href="/admin/photobooks.php">Photobooks</a></li>
                <li><a href="/admin/pages.php">Pages</a></li>
                <li><a href="/admin/menus.php">Menus</a></li>
                <li><a href="/admin/settings.php">Settings</a></li>
                <li><a href="/admin/profile.php">Profile</a></li>
                <li><a href="/admin/sort.php">Sort Content</a></li>
                <li><a href="/admin/import.php">Import Documents</a></li>
                <li><a href="/admin/logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="admin-content">
            <div class="page-header">
                <h2>Pages</h2>
                <div class="actions">
                    <button onclick="showCreateForm()" class="btn btn-primary">New Page</button>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div id="page-form" style="<?= $editPage ? '' : 'display:none' ?>">
                <h3><?= $editPage ? 'Edit Page' : 'Create Page' ?></h3>
                <form method="post" id="editor-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="<?= $editPage ? 'update' : 'create' ?>">
                    <?php if ($editPage): ?>
                    <input type="hidden" name="id" value="<?= $editPage['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="<?= $editPage ? htmlspecialchars($editPage['title']) : '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" value="<?= $editPage ? htmlspecialchars($editPage['slug']) : '' ?>">
                        <small>URL path for the page (e.g., "about-us" for /about-us)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" value="<?= $editPage ? htmlspecialchars($editPage['author'] ?? 'Don Althaus') : 'Don Althaus' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="body">Page Content</label>
                        <textarea id="body" name="body" rows="20"><?= $editPage ? $editPage['body'] : '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?= $editPage && $editPage['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= $editPage && $editPage['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="/admin/pages.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
            
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Title</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pages as $page): ?>
                    <tr>
                        <td><?= $page['sort_order'] ?></td>
                        <td><?= htmlspecialchars($page['title']) ?></td>
                        <td><?= htmlspecialchars($page['slug']) ?></td>
                        <td><span class="status-<?= $page['status'] ?>"><?= $page['status'] ?></span></td>
                        <td><?= date('Y-m-d', strtotime($page['created_at'])) ?></td>
                        <td>
                            <a href="/<?= htmlspecialchars($page['slug']) ?>" target="_blank" class="btn btn-sm">View</a>
                            <a href="?edit=<?= $page['id'] ?>" class="btn btn-sm">Edit</a>
                            <a href="/admin/versions.php?content_id=<?= $page['id'] ?>" class="btn btn-sm">Versions</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Move to trash?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $page['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>
    
    <script src="/assets/js/autosave.js"></script>
    <script>
        tinymce.init({
            selector: '#body',
            height: 600,
            menubar: true,
            license_key: 'gpl',
            plugins: 'link image code lists preview fullscreen',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image | preview fullscreen',
            promotion: false,
            branding: false,
            content_css: [],
            content_style: `
                @import url('https://fonts.googleapis.com/css2?family=Gelasio:wght@400;500;600;700&family=Arimo:wght@400;500;600;700&display=swap');
                body { 
                    font-family: 'Gelasio', serif; 
                    font-size: 1.125rem;
                    line-height: 1.8; 
                    color: #333;
                    padding: 1rem;
                    max-width: 100%;
                }
                h1, h2, h3, h4, h5, h6 {
                    font-family: 'Arimo', sans-serif;
                    font-weight: 600;
                    line-height: 1.3;
                    color: #2c3e50;
                }
                p {
                    margin-bottom: 1.5rem;
                }
                img { 
                    max-width: 100%; 
                    height: auto; 
                    margin: 2rem auto; 
                    display: block;
                    border-radius: 4px;
                    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
                }
                img.img-left {
                    float: left;
                    margin: 1rem 2rem 1rem 0;
                    max-width: 40%;
                }
                img.img-right {
                    float: right;
                    margin: 1rem 0 1rem 2rem;
                    max-width: 40%;
                }
                img.img-center {
                    margin: 2rem auto;
                }
                img.img-full {
                    width: 100%;
                    max-width: none;
                    margin: 2rem 0;
                }
                a {
                    color: #666;
                    text-decoration: none;
                }
                a:hover {
                    color: #999;
                    text-decoration: underline;
                }
            `,
            image_caption: true,
            image_dimensions: false,
            image_class_list: [
                {title: 'Full Width', value: 'img-full'},
                {title: 'Float Left', value: 'img-left'},
                {title: 'Float Right', value: 'img-right'},
                {title: 'Centered', value: 'img-center'}
            ],
            // Allow a comprehensive set of HTML elements and attributes
            extended_valid_elements: 'img[class|src|alt|title|width|height|loading|style],' +
                'a[href|target|rel|title|class|style],' +
                'iframe[src|width|height|frameborder|allowfullscreen|class|style],' +
                'video[src|controls|width|height|poster|preload|autoplay|muted|loop|class|style],' +
                'audio[src|controls|preload|autoplay|loop|class|style],' +
                'source[src|type],' +
                'figure[class|style],' +
                'figcaption[class|style],' +
                'mark[class|style],' +
                'small[class|style],' +
                'cite[class|style],' +
                'code[class|style],' +
                'pre[class|style],' +
                'blockquote[cite|class|style],' +
                'table[class|style|border|cellpadding|cellspacing],' +
                'thead[class|style],' +
                'tbody[class|style],' +
                'tfoot[class|style],' +
                'tr[class|style],' +
                'td[class|style|colspan|rowspan],' +
                'th[class|style|colspan|rowspan],' +
                'caption[class|style],' +
                'div[class|style|id],' +
                'span[class|style],' +
                'article[class|style],' +
                'section[class|style],' +
                'header[class|style],' +
                'footer[class|style],' +
                'aside[class|style],' +
                'nav[class|style]',
            valid_children: '+body[style]'
        });
        
        function showCreateForm() {
            document.getElementById('page-form').style.display = 'block';
            document.querySelector('[name="action"]').value = 'create';
        }
        
        // Auto-generate slug
        document.getElementById('title')?.addEventListener('blur', function() {
            if (!document.getElementById('slug').value) {
                const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
                document.getElementById('slug').value = slug;
            }
        });
    </script>
</body>
</html>