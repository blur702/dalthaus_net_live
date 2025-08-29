<?php
declare(strict_types=1);

// Disable error display to prevent HTML in JSON response
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Set JSON response header early
header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/auth.php';
    require_once __DIR__ . '/../../includes/database.php';
    require_once __DIR__ . '/../../includes/functions.php';
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check authentication
    if (!Auth::isLoggedIn() || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server configuration error']);
    exit;
}

try {
    // Get JSON data
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Invalid JSON data']);
        exit;
    }
    
    // Validate CSRF token
    if (!validateCSRFToken($input['csrf_token'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
        exit;
    }
    
    $location = $input['location'] ?? '';
    $order = $input['order'] ?? [];
    $type = $input['type'] ?? '';
    
    if (empty($order)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'No order data provided']);
        exit;
    }
    
    $pdo = Database::getInstance();
    $pdo->beginTransaction();
    
    // Determine if we're sorting menus or content
    if ($location === 'top' || $location === 'bottom') {
        // Sorting menu items
        $stmt = $pdo->prepare("UPDATE menus SET sort_order = ? WHERE id = ?");
    } elseif ($type === 'content') {
        // Sorting content items (articles, photobooks, pages)
        $stmt = $pdo->prepare("UPDATE content SET sort_order = ? WHERE id = ?");
    } else {
        throw new Exception('Invalid sort type');
    }
    
    foreach ($order as $item) {
        if (!isset($item['order']) || !isset($item['id'])) {
            throw new Exception('Invalid order item format');
        }
        $stmt->execute([$item['order'], $item['id']]);
    }
    
    $pdo->commit();
    
    // Clear cache
    cacheClear();
    
    echo json_encode(['success' => true, 'message' => 'Order saved successfully']);
    
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the actual error
    logMessage('Sort API error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), 'error');
    
    // Return generic error to client
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to save order']);
}