<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Content as ContentModel;
use App\Models\Autosave;
use CMS\Utils\FileUpload;
use CMS\Utils\Database;
use CMS\Utils\Auth;
use Exception;

class Content extends BaseController
{
    public function __construct(Database $db, Auth $auth, array $config)
    {
        parent::__construct($db, $auth, $config);
    }

    protected function initialize(): void
    {
        $this->view->layout('admin');
    }

    public function index(): void
    {
        $page = (int) $this->request->get('page', 1);
        $type = $this->request->get('type', '');
        $search = $this->request->get('search', '');
        $status = $this->request->get('status', '');
        $sortBy = $this->request->get('sort_by', 'created_at');
        $sortDir = $this->request->get('sort_dir', 'DESC');
        
        $validTypes = [ContentModel::TYPE_ARTICLE, ContentModel::TYPE_PHOTOBOOK];
        if (!empty($type) && !in_array($type, $validTypes)) {
            $type = '';
        }
        
        $filters = [
            'search' => $search,
            'status' => $status,
            'content_type' => $type,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir
        ];
        
        $itemsPerPage = $this->config['app']['items_per_page'] ?? 10;
        $totalItems = ContentModel::countWithFilters($filters);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $itemsPerPage;
        
        $content = ContentModel::findWithFilters($filters, $itemsPerPage, (int) $offset);
        
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
        ];
        
        $this->render('admin/content/index', [
            'content' => $content,
            'filters' => $filters,
            'pagination' => $pagination,
            'page_title' => 'Content Management',
        ]);
    }

    public function create(): void
    {
        $type = $this->request->get('type', ContentModel::TYPE_ARTICLE);
        if (!in_array($type, [ContentModel::TYPE_ARTICLE, ContentModel::TYPE_PHOTOBOOK])) {
            $type = ContentModel::TYPE_ARTICLE;
        }

        $this->render('admin/content/create', [
            'content_type' => $type,
            'page_title' => 'Create ' . ucfirst($type),
        ]);
    }

    public function store(): void
    {
        if (!$this->request->isPost()) {
            $this->redirect('/admin/content');
            return;
        }

        if (!$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/content/create');
            return;
        }

        try {
            $data = $this->getFormData();
            $data['url_alias'] = $this->ensureUniqueUrlAlias($data['url_alias']);

            $errors = $this->validateContentData($data);
            if (!empty($errors)) {
                $this->setFlash('error', implode('; ', $errors));
                $this->redirect('/admin/content/create?type=' . urlencode($data['content_type']));
                return;
            }

            $data['user_id'] = $this->auth->id();
            $data['sort_order'] = ContentModel::getNextSortOrder();

            // Handle file uploads for new content (create temporary content object for method signature)
            error_log("[Content::store] Checking for file uploads");
            $tempContent = new ContentModel($this->db);
            $this->handleFileUploads($data, $tempContent);

            $content = ContentModel::create($data);

            $this->setFlash('success', ucfirst($data['content_type']) . ' created successfully.');
            $this->redirect('/admin/content/' . $content->getId() . '/edit');

        } catch (Exception $e) {
            $this->logError('Content store error', $e);
            $this->setFlash('error', 'An error occurred while creating content.');
            $this->redirect('/admin/content/create');
        }
    }

    private function getFormData(): array
    {
        $status = $this->request->post('action') === 'publish' ? ContentModel::STATUS_PUBLISHED : ContentModel::STATUS_DRAFT;
        
        return [
            'title' => $this->request->post('title', ''),
            'teaser' => $this->request->post('teaser', ''),
            'body' => $this->request->post('body', ''),
            'url_alias' => $this->request->post('url_alias', ''),
            'content_type' => $this->request->post('content_type', ContentModel::TYPE_ARTICLE),
            'status' => $status,
            'published_at' => $this->request->post('published_at', null)
        ];
    }

    private function ensureUniqueUrlAlias(string $urlAlias, ?int $excludeId = null): string
    {
        $baseAlias = $urlAlias;
        $counter = 2;
        while ($existing = ContentModel::findByUrlAlias($urlAlias)) {
            if ($excludeId && $existing->getId() === $excludeId) {
                break;
            }
            $urlAlias = $baseAlias . '-' . $counter++;
        }
        return $urlAlias;
    }

    private function validateContentData(array $data, ?int $excludeId = null): array
    {
        $errors = [];
        if (empty($data['title'])) {
            $errors['title'] = 'Title is required';
        }
        if (empty($data['url_alias'])) {
            $errors['url_alias'] = 'URL alias is required';
        }
        return $errors;
    }

    private function handleFileUploads(array &$data, ContentModel $content): void
    {
        error_log("[Content::handleFileUploads] Starting file upload handling");

        // Handle featured image upload
        $featuredImage = $this->request->file('featured_image');
        if ($featuredImage && $featuredImage['error'] === UPLOAD_ERR_OK) {
            error_log("[Content::handleFileUploads] Featured image upload detected");
            $uploadedPath = $this->uploadContentImage($featuredImage, 'featured');
            if ($uploadedPath) {
                $data['featured_image'] = $uploadedPath;
                error_log("[Content::handleFileUploads] Featured image uploaded: $uploadedPath");

                // Delete old featured image if it exists
                $oldImage = $content->getAttribute('featured_image');
                if ($oldImage) {
                    $this->deleteOldImage($oldImage);
                }
            } else {
                error_log("[Content::handleFileUploads] Featured image upload failed");
            }
        } else {
            if ($featuredImage) {
                error_log("[Content::handleFileUploads] Featured image error code: " . $featuredImage['error']);
            }
        }

        // Handle teaser image upload (for photobooks)
        $teaserImage = $this->request->file('teaser_image');
        if ($teaserImage && $teaserImage['error'] === UPLOAD_ERR_OK) {
            error_log("[Content::handleFileUploads] Teaser image upload detected");
            $uploadedPath = $this->uploadContentImage($teaserImage, 'teaser');
            if ($uploadedPath) {
                $data['teaser_image'] = $uploadedPath;
                error_log("[Content::handleFileUploads] Teaser image uploaded: $uploadedPath");

                // Delete old teaser image if it exists
                $oldImage = $content->getAttribute('teaser_image');
                if ($oldImage) {
                    $this->deleteOldImage($oldImage);
                }
            } else {
                error_log("[Content::handleFileUploads] Teaser image upload failed");
            }
        } else {
            if ($teaserImage) {
                error_log("[Content::handleFileUploads] Teaser image error code: " . $teaserImage['error']);
            }
        }

        error_log("[Content::handleFileUploads] File upload handling complete");
    }

    private function uploadContentImage(array $file, string $type): ?string
    {
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExtensions)) {
            error_log("[Content::uploadContentImage] Invalid file extension: $extension");
            return null;
        }

        // Create directory structure: /uploads/content/{type}s/YYYY/MM/
        $year = date('Y');
        $month = date('m');
        $uploadDir = __DIR__ . "/../../../uploads/content/{$type}s/$year/$month";

        if (!is_dir($uploadDir)) {
            if (!mkdir($uploadDir, 0755, true)) {
                error_log("[Content::uploadContentImage] Failed to create directory: $uploadDir");
                return null;
            }
            error_log("[Content::uploadContentImage] Created directory: $uploadDir");
        }

        // Generate unique, web-safe filename using timestamp and random string
        $timestamp = time();
        $randomString = bin2hex(random_bytes(8)); // 16 character hex string
        $filename = $type . '_' . $timestamp . '_' . $randomString . '.' . $extension;
        $uploadPath = $uploadDir . '/' . $filename;
        $relativePath = "/uploads/content/{$type}s/$year/$month/$filename";

        error_log("[Content::uploadContentImage] Generated filename: $filename");

        error_log("[Content::uploadContentImage] Attempting upload to: $uploadPath");

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            error_log("[Content::uploadContentImage] Upload successful: $relativePath");
            return $relativePath;
        }

        error_log("[Content::uploadContentImage] Upload failed");
        return null;
    }

    private function deleteOldImage(string $imagePath): void
    {
        error_log("[Content::deleteOldImage] Checking if image can be safely deleted: $imagePath");

        // Check if any other content is using this same image path
        $checkQuery = "SELECT content_id, title FROM content
                       WHERE (featured_image = ? OR teaser_image = ?)";
        $results = $this->db->fetchAll($checkQuery, [$imagePath, $imagePath]);

        if (count($results) > 1) {
            error_log("[Content::deleteOldImage] SKIPPING - Image is used by " . count($results) . " other content items");
            foreach ($results as $item) {
                error_log("[Content::deleteOldImage]   - Content ID {$item['content_id']}: {$item['title']}");
            }
            return;
        }

        // Handle both relative and absolute paths
        if (strpos($imagePath, '/') === 0) {
            // Absolute path from web root
            $fullPath = __DIR__ . '/../../../' . ltrim($imagePath, '/');
        } else {
            // Relative path
            $fullPath = __DIR__ . '/../../../uploads/' . $imagePath;
        }

        if (file_exists($fullPath)) {
            if (unlink($fullPath)) {
                error_log("[Content::deleteOldImage] Deleted old image: $fullPath");
            } else {
                error_log("[Content::deleteOldImage] Failed to delete old image: $fullPath");
            }
        } else {
            error_log("[Content::deleteOldImage] Old image not found: $fullPath");
        }
    }

    public function edit(string $id): void
    {
        $content = ContentModel::find((int)$id);
        if (!$content) {
            $this->setFlash('error', 'Content not found.');
            $this->redirect('/admin/content');
            return;
        }
        
        $this->render('admin/content/edit', [
            'content' => $content,
            'page_title' => 'Edit ' . ucfirst($content->getAttribute('content_type')),
        ]);
    }

    public function update(string $id): void
    {
        if (!$this->request->isPost()) {
            $this->redirect('/admin/content');
            return;
        }

        if (!$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/content/' . $id . '/edit');
            return;
        }

        $content = ContentModel::find((int)$id);
        if (!$content) {
            $this->setFlash('error', 'Content not found.');
            $this->redirect('/admin/content');
            return;
        }

        try {
            $data = $this->getFormData();
            $data['url_alias'] = $this->ensureUniqueUrlAlias($data['url_alias'], (int)$id);

            $errors = $this->validateContentData($data, (int)$id);
            if (!empty($errors)) {
                $this->setFlash('error', implode('; ', $errors));
                $this->redirect('/admin/content/' . $id . '/edit');
                return;
            }

            // Handle file uploads
            error_log("[Content::update] Checking for file uploads for content ID: $id");
            $this->handleFileUploads($data, $content);

            $content->setAttributes($data);
            $content->save();

            $this->setFlash('success', ucfirst($content->getAttribute('content_type')) . ' updated successfully.');
            $this->redirect('/admin/content/' . $id . '/edit');

        } catch (Exception $e) {
            $this->logError('Content update error', $e);
            $this->setFlash('error', 'An error occurred while updating content.');
            $this->redirect('/admin/content/' . $id . '/edit');
        }
    }

    public function delete(string $id): void
    {
        if (!$this->request->isPost()) {
            $this->redirect('/admin/content');
            return;
        }

        if (!$this->auth->validateCsrfToken($this->request->post('_token'))) {
            $this->setFlash('error', 'Invalid security token.');
            $this->redirect('/admin/content');
            return;
        }

        $content = ContentModel::find((int)$id);
        if ($content) {
            $content->delete();
            $this->setFlash('success', ucfirst($content->getAttribute('content_type')) . ' deleted successfully.');
        }

        $this->redirect('/admin/content');
    }

    public function autosave(): void
    {
        // Set JSON header
        header('Content-Type: application/json');

        if (!$this->request->isPost()) {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        // Verify CSRF token
        if (!$this->auth->validateCsrfToken($this->request->post('_token'))) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token']);
            return;
        }

        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                return;
            }

            $autosaveModel = new Autosave($this->db);

            $data = [
                'autosave_uuid' => $this->request->post('autosave_uuid'),
                'master_content_uuid' => $this->request->post('master_content_uuid'),
                'content_id' => $this->request->post('content_id') ? (int)$this->request->post('content_id') : null,
                'user_id' => $userId,
                'title' => $this->request->post('title', ''),
                'content' => $this->request->post('body', ''),
                'excerpt' => $this->request->post('teaser', ''),
                'type' => $this->request->post('content_type', 'article')
            ];

            // Only save if we have at least a title
            if (empty($data['title'])) {
                echo json_encode(['success' => false, 'message' => 'Title is required for autosave']);
                return;
            }

            $autosaveId = $autosaveModel->createOrUpdate($data);

            echo json_encode([
                'success' => true,
                'autosave_id' => $autosaveId,
                'message' => 'Content autosaved successfully'
            ]);

        } catch (Exception $e) {
            $this->logError('Autosave error', $e);
            echo json_encode(['success' => false, 'message' => 'Autosave failed: ' . $e->getMessage()]);
        }
    }

    public function loadAutosave(): void
    {
        // Set JSON header
        header('Content-Type: application/json');

        if (!$this->request->isPost()) {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                return;
            }

            $autosaveModel = new Autosave($this->db);
            $masterUuid = $this->request->post('master_content_uuid');

            if (!$masterUuid) {
                echo json_encode(['success' => false, 'message' => 'Master UUID required']);
                return;
            }

            $autosave = $autosaveModel->findByMasterUUID($masterUuid);

            if (!$autosave) {
                echo json_encode(['success' => false, 'message' => 'No autosave found']);
                return;
            }

            echo json_encode([
                'success' => true,
                'autosave' => $autosave
            ]);

        } catch (Exception $e) {
            $this->logError('Load autosave error', $e);
            echo json_encode(['success' => false, 'message' => 'Failed to load autosave: ' . $e->getMessage()]);
        }
    }

    public function listAutosaves(): void
    {
        // Set JSON header
        header('Content-Type: application/json');

        if (!$this->request->isPost()) {
            echo json_encode(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        try {
            $userId = $_SESSION['user_id'] ?? null;
            if (!$userId) {
                echo json_encode(['success' => false, 'message' => 'User not authenticated']);
                return;
            }

            $autosaveModel = new Autosave($this->db);
            $autosaves = $autosaveModel->getAllAutosaves($userId);

            echo json_encode([
                'success' => true,
                'autosaves' => $autosaves
            ]);

        } catch (Exception $e) {
            $this->logError('List autosaves error', $e);
            echo json_encode(['success' => false, 'message' => 'Failed to list autosaves: ' . $e->getMessage()]);
        }
    }

    public function reorder(): void
    {
        error_log("[Content::reorder] START - Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown'));

        $type = $this->request->get('type', '');
        error_log("[Content::reorder] Type filter: " . ($type ?: 'none'));

        // If type filter is specified, validate it
        if (!empty($type) && !in_array($type, [ContentModel::TYPE_ARTICLE, ContentModel::TYPE_PHOTOBOOK])) {
            error_log("[Content::reorder] Invalid type '$type', resetting to empty");
            $type = '';
        }

        // Get content for reordering (all content or filtered by type)
        $content = ContentModel::getForReordering($type);
        error_log("[Content::reorder] Retrieved " . count($content) . " content items");

        $this->render('admin/content/reorder', [
            'content' => $content,
            'content_type' => $type,
            'page_title' => 'Reorder Content',
        ]);
    }

    public function updateOrder(): void
    {
        error_log("[Content::updateOrder] START - Request method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown'));

        if (!$this->request->isPost()) {
            error_log("[Content::updateOrder] ERROR: Not a POST request");
            $this->renderJson(['success' => false, 'message' => 'Invalid request method'], 405);
            return;
        }

        $token = $this->request->post('_token');
        error_log("[Content::updateOrder] CSRF token received: " . ($token ? 'yes' : 'no'));

        if (!$this->auth->validateCsrfToken($token)) {
            error_log("[Content::updateOrder] ERROR: Invalid CSRF token");
            $this->renderJson(['success' => false, 'message' => 'Invalid CSRF token'], 403);
            return;
        }

        try {
            $orderJson = $this->request->post('order', '');
            error_log("[Content::updateOrder] Raw order JSON: " . $orderJson);

            if (empty($orderJson)) {
                throw new Exception('No order data provided');
            }

            $orderData = json_decode($orderJson, true);
            if (!is_array($orderData)) {
                throw new Exception('Invalid order data format');
            }

            error_log("[Content::updateOrder] Decoded order data: " . json_encode($orderData));

            // Transform array format from [{id: 1, position: 1}] to [1 => 1]
            $transformedData = [];
            foreach ($orderData as $item) {
                if (isset($item['id']) && isset($item['position'])) {
                    $transformedData[$item['id']] = $item['position'];
                }
            }

            error_log("[Content::updateOrder] Transformed data: " . json_encode($transformedData));

            if (ContentModel::updateSortOrder($transformedData)) {
                error_log("[Content::updateOrder] SUCCESS: Order updated");
                $this->renderJson(['success' => true, 'message' => 'Content order updated successfully']);
            } else {
                throw new Exception('Failed to update content order');
            }
        } catch (Exception $e) {
            error_log("[Content::updateOrder] EXCEPTION: " . $e->getMessage());
            error_log("[Content::updateOrder] Stack trace: " . $e->getTraceAsString());
            $this->logError('Update content order error', $e);
            $this->renderJson(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}