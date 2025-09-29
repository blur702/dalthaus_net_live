<?php
/**
 * Authentication test endpoint
 */

require_once __DIR__ . '/bootstrap.php';

use CMS\Controllers\BaseController;

class DebugController extends BaseController {
    public function testAuth() {
        $this->renderJson([
            'authenticated' => $this->isAuthenticated(),
            'user_id' => $_SESSION['user_id'] ?? null,
            'is_admin' => $_SESSION['is_admin'] ?? null,
            'session_id' => session_id(),
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
}

$controller = new DebugController();
$controller->testAuth();
?>