<?php
/**
 * Core functions for the site
 */

/**
 * Get a single setting value from database
 */
function getSetting($key, $default = '') {
    if (!isset($GLOBALS['pdo']) || !$GLOBALS['pdo']) {
        return $default;
    }
    
    try {
        $stmt = $GLOBALS['pdo']->prepare("SELECT setting_value FROM settings WHERE setting_key = ? LIMIT 1");
        $stmt->execute([$key]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        error_log('Error getting setting: ' . $e->getMessage());
        return $default;
    }
}

/**
 * Get all settings as associative array
 */
function getSettings() {
    if (!isset($GLOBALS['pdo']) || !$GLOBALS['pdo']) {
        return [
            'site_title' => 'Dalthaus Photography',
            'site_motto' => 'Capturing moments, telling stories through light and shadow'
        ];
    }
    
    try {
        $stmt = $GLOBALS['pdo']->query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
        
        return $settings;
    } catch (PDOException $e) {
        error_log('Error getting settings: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get article by slug
 */
function getArticleBySlug($slug) {
    try {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM content WHERE slug = ? AND type = 'article' AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result : null;
    } catch (Exception $e) {
        error_log('Error getting article by slug: ' . $e->getMessage());
        return null;
    }
}

/**
 * Get photobook by slug
 */
function getPhotobookBySlug($slug) {
    try {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM content WHERE slug = ? AND type = 'photobook' AND status = 'published' LIMIT 1");
        $stmt->execute([$slug]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? $result : null;
    } catch (Exception $e) {
        error_log('Error getting photobook by slug: ' . $e->getMessage());
        return null;
    }
}

/**
 * Check maintenance mode
 */
function checkMaintenanceMode() {
    $isMaintenanceModeOn = getSetting('maintenance_mode', '0');
    $isAdmin = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    
    if ($isMaintenanceModeOn === '1' && !$isAdmin) {
        http_response_code(503);
        echo '<!DOCTYPE html><html><head><title>Maintenance</title></head><body><h1>Maintenance Mode</h1><p>Site is under maintenance.</p></body></html>';
        exit;
    }
}

/**
 * Show error page
 */
function showError($code = 404) {
    http_response_code($code);
    $title = $code === 404 ? 'Page Not Found' : 'Error';
    $message = $code === 404 ? 'The page you are looking for does not exist.' : 'An error occurred.';
    
    echo '<!DOCTYPE html><html><head><title>' . $title . '</title></head><body>';
    echo '<h1>' . $title . '</h1><p>' . $message . '</p>';
    echo '<p><a href="/">Return to Homepage</a></p></body></html>';
    exit;
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

/**
 * Check if maintenance mode is active
 */
function isMaintenanceMode() {
    return getSetting('maintenance_mode', '0') === '1';
}

/**
 * Clear cache files
 */
function cacheClear() {
    $cleared = 0;
    $cacheDir = __DIR__ . '/../cache';
    
    if (!is_dir($cacheDir)) {
        return true;
    }
    
    try {
        $files = glob($cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file) && unlink($file)) {
                $cleared++;
            }
        }
    } catch (Exception $e) {
        error_log("Cache clear error: " . $e->getMessage());
    }
    
    return true;
}

/**
 * Log messages for debugging
 */
function logMessage($message, $level = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    
    $logFile = __DIR__ . '/../logs/app.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Get all content of a specific type
 */
function getAllContent(string $type): array {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT * FROM content WHERE type = ? AND status = 'published' AND deleted_at IS NULL ORDER BY created_at DESC");
    $stmt->execute([$type]);
    return $stmt->fetchAll();
}

/**
 * Get recent articles
 */
function getRecentArticles(int $limit = 4): array {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT * FROM content WHERE type = 'article' AND status = 'published' AND deleted_at IS NULL ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get recent photobooks
 */
function getRecentPhotobooks(int $limit = 3): array {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT * FROM content WHERE type = 'photobook' AND status = 'published' AND deleted_at IS NULL ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get content for a specific page
 */
function getPageContent(string $slug): ?string {
    $pdo = Database::getInstance();
    $stmt = $pdo->prepare("SELECT body FROM content WHERE slug = ? AND type = 'page' AND status = 'published' LIMIT 1");
    $stmt->execute([$slug]);
    $result = $stmt->fetchColumn();
    return $result ?: null;
}

/**
 * Create a URL-friendly slug from a string
 */
function createSlug(string $text): string {
    $text = preg_replace('~[^\pL\d]+~u', '-', $text);
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    $text = trim($text, '-');
    $text = preg_replace('~-+~', '-', $text);
    $text = strtolower($text);
    if (empty($text)) {
        return 'n-a';
    }
    return $text;
}
?>