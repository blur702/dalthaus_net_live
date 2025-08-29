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

// Load current settings
$settings = [];
$result = $pdo->query("SELECT setting_key, setting_value FROM settings");
foreach ($result as $row) {
    $settings[$row['setting_key']] = $row['setting_value'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid CSRF token';
    } else {
        try {
            // Update text settings
            $textSettings = [
                'site_title' => $_POST['site_title'] ?? '',
                'site_motto' => $_POST['site_motto'] ?? '',
                'header_height' => $_POST['header_height'] ?? '200',
                'header_overlay_color' => $_POST['header_overlay_color'] ?? 'rgba(0,0,0,0.3)',
                'header_text_color' => $_POST['header_text_color'] ?? '#333333',
                'maintenance_mode' => isset($_POST['maintenance_mode']) ? '1' : '0',
                'maintenance_message' => $_POST['maintenance_message'] ?? ''
            ];
            
            foreach ($textSettings as $key => $value) {
                $stmt = $pdo->prepare("
                    INSERT INTO settings (setting_key, setting_value) 
                    VALUES (?, ?) 
                    ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                ");
                $stmt->execute([$key, $value]);
            }
            
            // Handle header image upload
            if (!empty($_FILES['header_image']['name'])) {
                $uploadDir = __DIR__ . '/../uploads/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $fileInfo = pathinfo($_FILES['header_image']['name']);
                $extension = strtolower($fileInfo['extension']);
                $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                
                if (!in_array($extension, $allowedExtensions)) {
                    $error = 'Invalid file type. Only JPG, PNG, GIF, and WebP images are allowed.';
                } elseif ($_FILES['header_image']['size'] > 10485760) { // 10MB
                    $error = 'File too large. Maximum size is 10MB.';
                } else {
                    $filename = 'header_' . time() . '.' . $extension;
                    $filepath = $uploadDir . $filename;
                    
                    if (move_uploaded_file($_FILES['header_image']['tmp_name'], $filepath)) {
                        // Delete old header image if exists
                        if (!empty($settings['header_image'])) {
                            $oldFile = __DIR__ . '/..' . $settings['header_image'];
                            if (file_exists($oldFile)) {
                                unlink($oldFile);
                            }
                        }
                        
                        // Save new header image path
                        $stmt = $pdo->prepare("
                            INSERT INTO settings (setting_key, setting_value) 
                            VALUES ('header_image', ?) 
                            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                        ");
                        $stmt->execute(['/uploads/' . $filename]);
                    } else {
                        $error = 'Failed to upload image';
                    }
                }
            }
            
            // Handle header image removal
            if (isset($_POST['remove_header_image']) && $_POST['remove_header_image'] === '1') {
                if (!empty($settings['header_image'])) {
                    $oldFile = __DIR__ . '/..' . $settings['header_image'];
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                $pdo->exec("DELETE FROM settings WHERE setting_key = 'header_image'");
            }
            
            if (empty($error)) {
                $message = 'Settings updated successfully';
                
                // Clear cache to reflect changes immediately
                cacheClear();
                
                // Reload settings
                $settings = [];
                $result = $pdo->query("SELECT setting_key, setting_value FROM settings");
                foreach ($result as $row) {
                    $settings[$row['setting_key']] = $row['setting_value'];
                }
            }
        } catch (Exception $e) {
            $error = 'Error updating settings: ' . $e->getMessage();
            logMessage('Settings update error: ' . $e->getMessage(), 'error');
        }
    }
}

$csrf = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin</title>
    <link rel="icon" type="image/x-icon" href="/favicon.ico">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="stylesheet" href="/assets/css/admin.css">
    <style>
        .settings-form {
            max-width: 800px;
            margin: 0 auto;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-group input[type="text"],
        .form-group input[type="number"],
        .form-group input[type="color"],
        .form-group textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group textarea {
            min-height: 100px;
            resize: vertical;
        }
        .form-group .help-text {
            font-size: 12px;
            color: #666;
            margin-top: 5px;
        }
        .current-image {
            max-width: 500px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .current-image img {
            width: 100%;
            height: auto;
            display: block;
        }
        .image-controls {
            display: flex;
            gap: 10px;
            align-items: center;
            margin-top: 10px;
        }
        .remove-image {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .remove-image:hover {
            background: #c82333;
        }
        .color-input-wrapper {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .color-input-wrapper input[type="text"] {
            flex: 1;
        }
        .color-input-wrapper input[type="color"] {
            width: 50px;
            height: 36px;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
        }
    </style>
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
                <li><a href="/admin/settings.php" class="active">Settings</a></li>
                <li><a href="/admin/profile.php">Profile</a></li>
                <li><a href="/admin/sort.php">Sort Content</a></li>
                <li><a href="/admin/import.php">Import Documents</a></li>
                <li><a href="/admin/logout.php">Logout</a></li>
            </ul>
        </nav>
        
        <main class="admin-content">
            <h2>Site Settings</h2>
            
            <?php if ($message): ?>
            <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>
            
            <form method="post" enctype="multipart/form-data" class="settings-form">
                <input type="hidden" name="csrf_token" value="<?= $csrf ?>">
                
                <div class="form-group">
                    <label for="site_title">Site Title</label>
                    <input type="text" id="site_title" name="site_title" 
                           value="<?= htmlspecialchars($settings['site_title'] ?? 'Dalthaus.net') ?>" required>
                    <div class="help-text">The main title displayed in the site header</div>
                </div>
                
                <div class="form-group">
                    <label for="site_motto">Site Motto</label>
                    <textarea id="site_motto" name="site_motto"><?= htmlspecialchars($settings['site_motto'] ?? '') ?></textarea>
                    <div class="help-text">Optional tagline displayed below the site title. HTML formatting allowed for basic styling.</div>
                </div>
                
                <div class="form-group">
                    <label for="header_image">Header Background Image</label>
                    
                    <?php if (!empty($settings['header_image'])): ?>
                    <div class="current-image">
                        <img src="<?= htmlspecialchars($settings['header_image']) ?>" alt="Current header image">
                    </div>
                    <div class="image-controls">
                        <label>
                            <input type="checkbox" name="remove_header_image" value="1">
                            Remove current image
                        </label>
                    </div>
                    <?php endif; ?>
                    
                    <input type="file" id="header_image" name="header_image" accept="image/*">
                    <div class="help-text">Upload a background image for the site header (JPG, PNG, GIF, or WebP, max 10MB)</div>
                </div>
                
                <div class="form-group">
                    <label for="header_height">Header Height (pixels)</label>
                    <input type="number" id="header_height" name="header_height" 
                           value="<?= htmlspecialchars($settings['header_height'] ?? '200') ?>" 
                           min="100" max="500" step="10">
                    <div class="help-text">Height of the header area when a background image is set</div>
                </div>
                
                <div class="form-group">
                    <label for="header_overlay_color">Header Overlay Color</label>
                    <div class="color-input-wrapper">
                        <input type="text" id="header_overlay_color" name="header_overlay_color" 
                               value="<?= htmlspecialchars($settings['header_overlay_color'] ?? 'rgba(0,0,0,0.3)') ?>"
                               placeholder="rgba(0,0,0,0.3)">
                        <input type="color" id="header_overlay_picker" 
                               onchange="updateOverlayColor(this.value)">
                    </div>
                    <div class="help-text">Color overlay for the header image (use RGBA format for transparency)</div>
                </div>
                
                <div class="form-group">
                    <label for="header_text_color">Header Text Color</label>
                    <div class="color-input-wrapper">
                        <input type="text" id="header_text_color" name="header_text_color" 
                               value="<?= htmlspecialchars($settings['header_text_color'] ?? '#333333') ?>"
                               placeholder="#333333">
                        <input type="color" id="header_text_picker" 
                               value="<?= htmlspecialchars($settings['header_text_color'] ?? '#333333') ?>"
                               onchange="document.getElementById('header_text_color').value = this.value">
                    </div>
                    <div class="help-text">Color of the text in the site header (title and motto)</div>
                </div>
                
                <hr style="margin: 30px 0;">
                
                <h3>Maintenance Mode</h3>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="maintenance_mode" name="maintenance_mode" value="1" 
                               <?= !empty($settings['maintenance_mode']) && $settings['maintenance_mode'] === '1' ? 'checked' : '' ?>>
                        Enable Maintenance Mode
                    </label>
                    <div class="help-text">When enabled, visitors will see the maintenance message instead of the site. Admin area remains accessible via direct URL.</div>
                </div>
                
                <div class="form-group">
                    <label for="maintenance_message">Maintenance Message</label>
                    <textarea id="maintenance_message" name="maintenance_message" rows="10" style="font-family: monospace;"><?= htmlspecialchars($settings['maintenance_message'] ?? 'The site is currently undergoing maintenance. Please check back soon.') ?></textarea>
                    <div class="help-text">HTML allowed. This message will be displayed to visitors when maintenance mode is enabled.</div>
                </div>
                
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                    <a href="/admin/" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </main>
    </div>
    
    <script>
    function updateOverlayColor(hexColor) {
        // Convert hex to rgba with 0.3 opacity
        const r = parseInt(hexColor.substr(1, 2), 16);
        const g = parseInt(hexColor.substr(3, 2), 16);
        const b = parseInt(hexColor.substr(5, 2), 16);
        document.getElementById('header_overlay_color').value = `rgba(${r},${g},${b},0.3)`;
    }
    
    // Set initial color picker value if possible
    document.addEventListener('DOMContentLoaded', function() {
        const overlayInput = document.getElementById('header_overlay_color');
        const colorPicker = document.getElementById('header_overlay_picker');
        const value = overlayInput.value;
        
        // Try to extract RGB values and set color picker
        const rgbaMatch = value.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)/);
        if (rgbaMatch) {
            const r = parseInt(rgbaMatch[1]).toString(16).padStart(2, '0');
            const g = parseInt(rgbaMatch[2]).toString(16).padStart(2, '0');
            const b = parseInt(rgbaMatch[3]).toString(16).padStart(2, '0');
            colorPicker.value = '#' + r + g + b;
        }
    });
    </script>
</body>
</html>