<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\MediaUpload;
use CMS\Utils\Database;
use CMS\Utils\Auth;

class Media extends BaseController
{
    protected function initialize(): void
    {
        $this->view->layout('admin');
    }

    public function index(): void
    {
        $page = $this->request->get('page', 1, 'int');
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $uploadType = $this->request->get('type');
        $userId = $this->request->get('user', null, 'int');
        $showUnused = $this->request->get('unused') !== null;

        if ($userId) {
            $uploads = MediaUpload::getByUser($userId, $limit, $offset);
        } elseif ($showUnused) {
            $uploads = MediaUpload::getUnused(7);
        } else {
            $uploads = $this->getAllUploads($limit, $offset, $uploadType);
        }

        $stats = MediaUpload::getStats();

        $this->render('admin/media/index', [
            'uploads' => $uploads,
            'stats' => $stats,
            'currentPage' => $page,
            'uploadType' => $uploadType,
            'showUnused' => $showUnused,
        ]);
    }

    private function getAllUploads(int $limit, int $offset, ?string $type = null): array
    {
        $sql = "SELECT mu.*, u.username, u.display_name, c.title as content_title
                FROM media_uploads mu
                LEFT JOIN users u ON mu.user_id = u.user_id
                LEFT JOIN content c ON mu.content_id = c.content_id";
        $params = [];
        if ($type) {
            $sql .= " WHERE mu.upload_type = ?";
            $params[] = $type;
        }
        $sql .= " ORDER BY mu.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function stats(): void
    {
        $this->render('admin/media/stats', [
            'stats' => MediaUpload::getStats(),
        ]);
    }

    public function markUsed(string $id): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->renderJson(['error' => 'Invalid request'], 400);
            return;
        }

        $upload = MediaUpload::find((int)$id);
        if (!$upload) {
            $this->renderJson(['error' => 'Upload not found.'], 404);
            return;
        }

        $contentId = $this->request->post('content_id', null, 'int');
        MediaUpload::markAsUsed($upload->getAttribute('filepath'), $contentId);
        $this->renderJson(['success' => true]);
    }

    public function cleanupOrphaned(): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid request.');
            $this->redirect('/admin/media');
            return;
        }

        $deleted = MediaUpload::deleteOrphaned();
        $this->setFlash('success', "Cleaned up {$deleted} orphaned upload record(s).");
        $this->redirect('/admin/media');
    }

    public function delete(string $id): void
    {
        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->renderJson(['error' => 'Invalid request'], 400);
            return;
        }

        $upload = MediaUpload::find((int)$id);
        if (!$upload) {
            $this->renderJson(['error' => 'Upload not found.'], 404);
            return;
        }

        $filepath = $upload->getAttribute('filepath');
        $fullPath = __DIR__ . '/../../../' . ltrim($filepath, '/');
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        $upload->delete();
        $this->renderJson(['success' => true]);
    }

    public function view(string $id): void
    {
        error_log("[Media::view] Called with ID: $id (type: " . gettype($id) . ")");

        $upload = MediaUpload::find((int)$id);
        if (!$upload) {
            error_log("[Media::view] Upload not found for ID: $id");
            $this->setFlash('error', 'Upload not found.');
            $this->redirect('/admin/media');
            return;
        }

        error_log("[Media::view] Found upload, rendering view");
        $this->render('admin/media/view', ['upload' => $upload]);
    }

    public function apiList(): void
    {
        // Log session and auth state for debugging
        error_log("===== Media::apiList() START =====");
        error_log("Session ID: " . session_id());
        error_log("Session data: " . print_r($_SESSION ?? 'no session', true));
        error_log("Auth object exists: " . ($this->auth ? 'yes' : 'no'));
        error_log("Auth check result: " . ($this->auth && $this->auth->check() ? 'TRUE' : 'FALSE'));

        // Check authentication for API endpoints
        if (!$this->auth || !$this->auth->check()) {
            error_log("Auth failed - returning 401");
            $this->renderJson(['error' => 'Unauthorized'], 401);
            return;
        }

        error_log("Auth passed - proceeding with query");
        $page = $this->request->get('page', 1, 'int');
        $limit = $this->request->get('limit', 24, 'int');
        $offset = ($page - 1) * $limit;
        $search = $this->request->get('search', '');
        $type = $this->request->get('type');

        $uploads = MediaUpload::search($search, $type, $limit, $offset);
        $total = MediaUpload::countWithSearch($search, $type);

        $this->renderJson([
            'success' => true,
            'data' => $uploads,
            'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => ceil($total / $limit)]
        ]);
    }

    public function apiUpdateMetadata(string $id): void
    {
        // Check authentication for API endpoints
        if (!$this->auth || !$this->auth->check()) {
            $this->renderJson(['error' => 'Unauthorized'], 401);
            return;
        }

        if (!$this->request->isPost() || !$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->renderJson(['error' => 'Invalid request'], 400);
            return;
        }

        $upload = MediaUpload::find((int)$id);
        if (!$upload) {
            $this->renderJson(['error' => 'Upload not found.'], 404);
            return;
        }

        $data = $this->request->json();
        $upload->setAttributes($data);
        $upload->save();

        $this->renderJson(['success' => true]);
    }

    public function browser(): void
    {
        $this->render('admin/media/browser');
    }
}