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