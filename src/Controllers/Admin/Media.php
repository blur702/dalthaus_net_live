<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\MediaUpload;

class Media extends BaseController
{
    /**
     * Display list of all media uploads
     */
    public function index(): void
    {
        $this->requireAuth();

        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $limit = 50;
        $offset = ($page - 1) * $limit;

        $uploadType = $_GET['type'] ?? null;
        $userId = isset($_GET['user']) ? (int)$_GET['user'] : null;
        $showUnused = isset($_GET['unused']);

        // Get uploads based on filters
        if ($userId) {
            $uploads = MediaUpload::getByUser($userId, $limit, $offset);
        } elseif ($showUnused) {
            $uploads = MediaUpload::getUnused(7); // Last 7 days
        } else {
            $uploads = $this->getAllUploads($limit, $offset, $uploadType);
        }

        // Get statistics
        $stats = MediaUpload::getStats();

        $this->view->render('Admin/media/index', [
            'uploads' => $uploads,
            'stats' => $stats,
            'currentPage' => $page,
            'uploadType' => $uploadType,
            'showUnused' => $showUnused,
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    /**
     * Get all uploads with optional type filter
     */
    private function getAllUploads(int $limit, int $offset, ?string $type = null): array
    {
        $pdo = $this->db->getConnection();

        $sql = "SELECT mu.*, u.username, u.display_name, c.title as content_title
                FROM media_uploads mu
                LEFT JOIN users u ON mu.user_id = u.user_id
                LEFT JOIN content c ON mu.content_id = c.content_id";

        $params = [];

        if ($type) {
            $sql .= " WHERE mu.upload_type = :type";
            $params['type'] = $type;
        }

        $sql .= " ORDER BY mu.created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, \PDO::PARAM_INT);

        if ($type) {
            $stmt->bindValue(':type', $type);
        }

        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    /**
     * Display media upload statistics
     */
    public function stats(): void
    {
        $this->requireAuth();

        $stats = MediaUpload::getStats();

        $this->view->render('Admin/media/stats', [
            'stats' => $stats,
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    /**
     * Mark upload as used
     */
    public function markUsed(int $id): void
    {
        $this->requireAuth();

        if (!$this->isPost()) {
            $this->renderJson(['error' => 'Invalid request method.'], 405);
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->renderJson(['error' => 'Security token validation failed.'], 403);
            return;
        }

        try {
            $upload = MediaUpload::find($id);

            if (!$upload) {
                $this->renderJson(['error' => 'Upload not found.'], 404);
                return;
            }

            $filepath = $upload->getAttribute('filepath');
            $contentId = isset($_POST['content_id']) ? (int)$_POST['content_id'] : null;

            $success = MediaUpload::markAsUsed($filepath, $contentId);

            if ($success) {
                $this->renderJson(['success' => true, 'message' => 'Upload marked as used.']);
            } else {
                $this->renderJson(['error' => 'Failed to update upload.'], 500);
            }
        } catch (\Exception $e) {
            error_log('Mark upload as used error: ' . $e->getMessage());
            $this->renderJson(['error' => 'An error occurred.'], 500);
        }
    }

    /**
     * Delete unused/orphaned uploads
     */
    public function cleanupOrphaned(): void
    {
        $this->requireAuth();

        if (!$this->isPost()) {
            $this->setFlash('error', 'Invalid request method.');
            $this->redirect('/admin/media');
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed.');
            $this->redirect('/admin/media');
            return;
        }

        try {
            $deleted = MediaUpload::deleteOrphaned();
            $this->setFlash('success', "Cleaned up {$deleted} orphaned upload record(s).");
        } catch (\Exception $e) {
            error_log('Cleanup orphaned uploads error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred during cleanup.');
        }

        $this->redirect('/admin/media');
    }

    /**
     * Delete a specific upload
     */
    public function delete(int $id): void
    {
        $this->requireAuth();

        if (!$this->isPost()) {
            $this->renderJson(['error' => 'Invalid request method.'], 405);
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->renderJson(['error' => 'Security token validation failed.'], 403);
            return;
        }

        try {
            $upload = MediaUpload::find($id);

            if (!$upload) {
                $this->renderJson(['error' => 'Upload not found.'], 404);
                return;
            }

            // Delete the physical file
            $filepath = $upload->getAttribute('filepath');
            $fullPath = __DIR__ . '/../../../' . ltrim($filepath, '/');

            if (file_exists($fullPath)) {
                unlink($fullPath);
            }

            // Delete the database record
            $success = $upload->delete();

            if ($success) {
                $this->renderJson(['success' => true, 'message' => 'Upload deleted successfully.']);
            } else {
                $this->renderJson(['error' => 'Failed to delete upload.'], 500);
            }
        } catch (\Exception $e) {
            error_log('Delete upload error: ' . $e->getMessage());
            $this->renderJson(['error' => 'An error occurred.'], 500);
        }
    }

    /**
     * View upload details
     */
    public function view(int $id): void
    {
        $this->requireAuth();

        try {
            $upload = MediaUpload::find($id);

            if (!$upload) {
                $this->setFlash('error', 'Upload not found.');
                $this->redirect('/admin/media');
                return;
            }

            $pdo = $this->db->getConnection();

            // Get full upload details with user and content info
            $sql = "SELECT mu.*, u.username, u.display_name, c.title as content_title, c.url_alias
                    FROM media_uploads mu
                    LEFT JOIN users u ON mu.user_id = u.user_id
                    LEFT JOIN content c ON mu.content_id = c.content_id
                    WHERE mu.id = :id";

            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id]);
            $uploadDetails = $stmt->fetch(\PDO::FETCH_ASSOC);

            $this->view->render('Admin/media/view', [
                'upload' => $uploadDetails,
                'csrf_token' => $this->generateCsrfToken()
            ]);
        } catch (\Exception $e) {
            error_log('View upload error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred.');
            $this->redirect('/admin/media');
        }
    }
}
