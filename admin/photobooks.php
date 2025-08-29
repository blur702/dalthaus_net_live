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
                    $featured_image = $_POST['featured_image'] ?? null;
                    $teaser_image = $_POST['teaser_image'] ?? null;
                    $teaser_text = $_POST['teaser_text'] ?? null;
                    
                    $stmt = $pdo->prepare("
                        INSERT INTO content (type, title, slug, author, body, featured_image, teaser_image, teaser_text, status, published_at) 
                        VALUES ('photobook', ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $publishedAt = $status === 'published' ? date('Y-m-d H:i:s') : null;
                    if ($stmt->execute([$title, $slug, $author, $body, $featured_image, $teaser_image, $teaser_text, $status, $publishedAt])) {
                        $contentId = $pdo->lastInsertId();
                        // Create initial version
                        $stmt = $pdo->prepare("
                            INSERT INTO content_versions (content_id, version_number, title, body) 
                            VALUES (?, 1, ?, ?)
                        ");
                        $stmt->execute([$contentId, $title, $body]);
                        $message = 'Photobook created successfully. <a href="/photobook/' . htmlspecialchars($slug) . '" target="_blank">View Photobook</a>';
                        $_SESSION['message'] = $message;
                        header('Location: /admin/photobooks.php?edit=' . $contentId);
                        exit;
                    }
                } else {
                    $id = (int)$_POST['id'];
                    // Check if we're publishing for the first time
                    $existingStatus = $pdo->query("SELECT status, published_at FROM content WHERE id = $id")->fetch();
                    $publishedAt = null;
                    if ($status === 'published' && $existingStatus['status'] === 'draft' && !$existingStatus['published_at']) {
                        $publishedAt = date('Y-m-d H:i:s');
                    }
                    
                    $featured_image = $_POST['featured_image'] ?? null;
                    $teaser_image = $_POST['teaser_image'] ?? null;
                    $teaser_text = $_POST['teaser_text'] ?? null;
                    
                    $stmt = $pdo->prepare("
                        UPDATE content 
                        SET title = ?, slug = ?, author = ?, body = ?, featured_image = ?, teaser_image = ?, teaser_text = ?, status = ?" . 
                        ($publishedAt ? ", published_at = ?" : "") . "
                        WHERE id = ? AND type = 'photobook'
                    ");
                    $params = [$title, $slug, $author, $body, $featured_image, $teaser_image, $teaser_text, $status];
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
                        $message = 'Photobook updated successfully. <a href="/photobook/' . htmlspecialchars($slug) . '" target="_blank">View Photobook</a>';
                        cacheClear();
                        $_SESSION['message'] = $message;
                    }
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                $stmt = $pdo->prepare("UPDATE content SET deleted_at = NOW() WHERE id = ? AND type = 'photobook'");
                if ($stmt->execute([$id])) {
                    $message = 'Photobook moved to trash';
                    cacheClear();
                }
                break;
        }
    }
}

// Get photobooks
$query = "SELECT * FROM content WHERE type = 'photobook' AND deleted_at IS NULL ORDER BY sort_order, created_at DESC";
$photobooks = $pdo->query($query)->fetchAll();

// Get photobook for editing if ID provided
$editPhotobook = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM content WHERE id = ? AND type = 'photobook'");
    $stmt->execute([$_GET['edit']]);
    $editPhotobook = $stmt->fetch();
}

$csrf = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Photobooks</title>
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
                <h2>Photobooks</h2>
                <div class="actions">
                    <button onclick="showCreateForm()" class="btn btn-primary">New Photobook</button>
                </div>
            </div>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <div id="photobook-form" style="<?= $editPhotobook ? '' : 'display:none' ?>">
                <h3><?= $editPhotobook ? 'Edit Photobook' : 'Create Photobook' ?></h3>
                <form method="post" id="editor-form">
                    <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                    <input type="hidden" name="action" value="<?= $editPhotobook ? 'update' : 'create' ?>">
                    <?php if ($editPhotobook): ?>
                    <input type="hidden" name="id" value="<?= $editPhotobook['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" value="<?= $editPhotobook ? htmlspecialchars($editPhotobook['title']) : '' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="slug">Slug</label>
                        <input type="text" id="slug" name="slug" value="<?= $editPhotobook ? htmlspecialchars($editPhotobook['slug']) : '' ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="author">Author</label>
                        <input type="text" id="author" name="author" value="<?= $editPhotobook ? htmlspecialchars($editPhotobook['author'] ?? 'Don Althaus') : 'Don Althaus' ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="featured_image">Featured Image</label>
                        <input type="hidden" id="featured_image" name="featured_image" value="<?= $editPhotobook ? htmlspecialchars($editPhotobook['featured_image'] ?? '') : '' ?>">
                        <input type="file" id="featured_image_file" accept="image/*" onchange="uploadImage('featured_image')">
                        <?php if ($editPhotobook && !empty($editPhotobook['featured_image'])): ?>
                        <div id="featured_image_preview" style="margin-top: 10px;">
                            <img src="<?= htmlspecialchars($editPhotobook['featured_image']) ?>" style="max-width: 200px; height: auto;">
                            <button type="button" onclick="removeImage('featured_image')" class="btn btn-sm btn-danger">Remove</button>
                        </div>
                        <?php else: ?>
                        <div id="featured_image_preview" style="margin-top: 10px; display: none;"></div>
                        <?php endif; ?>
                        <small>This image appears at the top of the first page of the photobook</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="teaser_image">Teaser Image</label>
                        <input type="hidden" id="teaser_image" name="teaser_image" value="<?= $editPhotobook ? htmlspecialchars($editPhotobook['teaser_image'] ?? '') : '' ?>">
                        <input type="file" id="teaser_image_file" accept="image/*" onchange="uploadImage('teaser_image')">
                        <?php if ($editPhotobook && !empty($editPhotobook['teaser_image'])): ?>
                        <div id="teaser_image_preview" style="margin-top: 10px;">
                            <img src="<?= htmlspecialchars($editPhotobook['teaser_image']) ?>" style="max-width: 200px; height: auto;">
                            <button type="button" onclick="removeImage('teaser_image')" class="btn btn-sm btn-danger">Remove</button>
                        </div>
                        <?php else: ?>
                        <div id="teaser_image_preview" style="margin-top: 10px; display: none;"></div>
                        <?php endif; ?>
                        <small>This image appears in photobook listings</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="teaser_text">Teaser Text</label>
                        <textarea id="teaser_text" name="teaser_text" rows="3" placeholder="Brief description for photobook listings..."><?= $editPhotobook ? htmlspecialchars($editPhotobook['teaser_text'] ?? '') : '' ?></textarea>
                        <small>Brief description shown in photobook listings (leave empty to auto-generate from content)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="body">Book Content</label>
                        <p class="help-text">Write your story here. Use the editor to add text, photos, and formatting. Use <code>&lt;!-- page --&gt;</code> to create page breaks for multi-page stories.</p>
                        <textarea id="body" name="body" rows="20"><?= $editPhotobook ? $editPhotobook['body'] : '' ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status</label>
                        <select id="status" name="status">
                            <option value="draft" <?= $editPhotobook && $editPhotobook['status'] === 'draft' ? 'selected' : '' ?>>Draft</option>
                            <option value="published" <?= $editPhotobook && $editPhotobook['status'] === 'published' ? 'selected' : '' ?>>Published</option>
                        </select>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save</button>
                        <a href="/admin/photobooks.php" class="btn btn-secondary">Cancel</a>
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
                    <?php foreach ($photobooks as $book): ?>
                    <tr>
                        <td><?= $book['sort_order'] ?></td>
                        <td><?= htmlspecialchars($book['title']) ?></td>
                        <td><?= htmlspecialchars($book['slug']) ?></td>
                        <td><span class="status-<?= $book['status'] ?>"><?= $book['status'] ?></span></td>
                        <td><?= date('Y-m-d', strtotime($book['created_at'])) ?></td>
                        <td>
                            <a href="/photobook/<?= htmlspecialchars($book['slug']) ?>" target="_blank" class="btn btn-sm">View</a>
                            <a href="?edit=<?= $book['id'] ?>" class="btn btn-sm">Edit</a>
                            <a href="/admin/versions.php?content_id=<?= $book['id'] ?>" class="btn btn-sm">Versions</a>
                            <form method="post" style="display:inline" onsubmit="return confirm('Move to trash?')">
                                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= $book['id'] ?>">
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
            plugins: 'link image code lists pagebreak preview fullscreen',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist | link image | pagebreak | preview fullscreen',
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
                    text-align: justify;
                    text-indent: 2em;
                }
                p:first-of-type {
                    text-indent: 0;
                }
                p:first-of-type:first-letter {
                    font-size: 3.5rem;
                    float: left;
                    line-height: 1;
                    margin: 0 0.1em 0 0;
                    font-weight: 600;
                    color: #667eea;
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
                .mce-pagebreak { 
                    border-top: 2px dashed #999; 
                    margin: 2em 0; 
                    padding: 1em 0;
                    text-align: center;
                    color: #999;
                }
                .mce-pagebreak::before {
                    content: 'Page Break';
                }
                a {
                    color: #8e44ad;
                    text-decoration: none;
                }
                a:hover {
                    color: #9b59b6;
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
            pagebreak_separator: '<!-- page -->',
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
            // Keep the <!-- page --> comments
            valid_children: '+body[style]',
            custom_elements: '~comment',
            setup: function(editor) {
                editor.on('init', function() {
                    editor.addShortcut('ctrl+shift+p', 'Insert page break', function() {
                        editor.insertContent('<!-- page -->');
                    });
                });
            }
        });
        
        function showCreateForm() {
            document.getElementById('photobook-form').style.display = 'block';
            document.querySelector('[name="action"]').value = 'create';
        }
        
        // Auto-generate slug
        document.getElementById('title')?.addEventListener('blur', function() {
            if (!document.getElementById('slug').value) {
                const slug = this.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
                document.getElementById('slug').value = slug;
            }
        });
        
        // Direct image upload function
        function uploadImage(fieldName) {
            const fileInput = document.getElementById(fieldName + '_file');
            const file = fileInput.files[0];
            
            if (!file) return;
            
            // Check file size (10MB max)
            if (file.size > 10485760) {
                alert('File too large. Maximum size is 10MB.');
                return;
            }
            
            // Check file type
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.');
                return;
            }
            
            const formData = new FormData();
            formData.append('image', file);
            formData.append('field', fieldName);
            formData.append('csrf_token', '<?= $csrf ?>');
            
            // Upload the file
            fetch('/admin/api/upload_image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update hidden field with image path
                    document.getElementById(fieldName).value = data.path;
                    
                    // Show preview
                    const preview = document.getElementById(fieldName + '_preview');
                    preview.innerHTML = `
                        <img src="${data.path}" style="max-width: 200px; height: auto;">
                        <button type="button" onclick="removeImage('${fieldName}')" class="btn btn-sm btn-danger">Remove</button>
                    `;
                    preview.style.display = 'block';
                } else {
                    alert('Upload failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                alert('Upload failed: ' + error.message);
            });
        }
        
        // Remove image function
        function removeImage(fieldName) {
            document.getElementById(fieldName).value = '';
            document.getElementById(fieldName + '_preview').style.display = 'none';
            document.getElementById(fieldName + '_file').value = '';
        }
    </script>
</body>
</html>