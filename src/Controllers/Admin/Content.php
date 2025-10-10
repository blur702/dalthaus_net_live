<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Content as ContentModel;
use App\Models\Autosave;
use CMS\Utils\FileUpload;
use CMS\Utils\Database;
use Exception;

class Content extends BaseController
{
    protected function initialize(): void
    {
        $this->requireAuth();
        $this->view->layout('admin');
    }

    public function index(): void
    {
        $page = (int) $this->getParam('page', 1);
        $type = $this->getParam('type', '');
        $search = $this->sanitize($this->getParam('search', ''));
        $status = $this->getParam('status', '');
        $sortBy = $this->getParam('sort_by', 'created_at');
        $sortDir = $this->getParam('sort_dir', 'DESC');
        
        // Validate content type
        $validTypes = [ContentModel::TYPE_ARTICLE, ContentModel::TYPE_PHOTOBOOK];
        if (!empty($type) && !in_array($type, $validTypes)) {
            $type = '';
        }
        
        // Build filters
        $filters = [
            'search' => $search,
            'status' => $status,
            'content_type' => $type,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir
        ];
        
        // Get content items
        $itemsPerPage = $this->config['app']['items_per_page'] ?? 10;
        $totalItems = ContentModel::countWithFilters($filters);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $itemsPerPage;
        
        $content = ContentModel::findWithFilters($filters, $itemsPerPage, (int) $offset);
        
        // Prepare pagination data
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => $itemsPerPage,
            'has_prev' => $page > 1,
            'has_next' => $page < $totalPages,
            'prev_page' => max(1, $page - 1),
            'next_page' => min($totalPages, $page + 1)
        ];
        
        $this->render('admin/content/index', [
            'content' => $content,
            'filters' => $filters,
            'pagination' => $pagination,
            'flash' => $this->getFlash(),
            'page_title' => 'Content Management',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    public function drafts(): void
    {
        $page = (int) $this->getParam('page', 1);
        $type = $this->getParam('type', '');
        $age = $this->getParam('age', '');
        $sort = $this->getParam('sort', 'updated_at');
        
        // Build filters for drafts only
        $filters = [
            'status' => ContentModel::STATUS_DRAFT,
            'content_type' => $type,
            'sort_by' => $sort,
            'sort_dir' => 'DESC'
        ];
        
        // Add age filter
        if ($age) {
            $now = date('Y-m-d H:i:s');
            switch ($age) {
                case 'recent':
                    $filters['updated_after'] = date('Y-m-d H:i:s', strtotime('-24 hours'));
                    break;
                case 'week':
                    $filters['updated_after'] = date('Y-m-d H:i:s', strtotime('-1 week'));
                    break;
                case 'month':
                    $filters['updated_after'] = date('Y-m-d H:i:s', strtotime('-1 month'));
                    break;
                case 'old':
                    $filters['updated_before'] = date('Y-m-d H:i:s', strtotime('-1 month'));
                    break;
            }
        }
        
        // Get draft items
        $itemsPerPage = $this->config['app']['items_per_page'] ?? 10;
        $totalItems = ContentModel::countWithFilters($filters);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $itemsPerPage;
        
        $items = ContentModel::findWithFilters($filters, $itemsPerPage, $offset);
        
        // Calculate analytics data
        $analytics = $this->calculateDraftAnalytics();
        
        $this->render('admin/content/drafts', [
            'items' => $items,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'type_filter' => $type,
            'age_filter' => $age,
            'sort_filter' => $sort,
            'recent_drafts_count' => $analytics['recent_drafts_count'],
            'ready_to_publish_count' => $analytics['ready_to_publish_count'],
            'old_drafts_count' => $analytics['old_drafts_count'],
            'page_title' => 'Auto-save & Draft Management',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }
    
    private function calculateDraftAnalytics(): array
    {
        $db = Database::getInstance();
        
        // Recent drafts (last 24 hours)
        $recentQuery = "SELECT COUNT(*) as count FROM content 
                        WHERE status = 'draft' 
                        AND updated_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)";
        $recentResult = $db->query($recentQuery);
        $recentCount = $recentResult->fetch()['count'] ?? 0;
        
        // Ready to publish (has title and content)
        $readyQuery = "SELECT COUNT(*) as count FROM content 
                       WHERE status = 'draft' 
                       AND title IS NOT NULL AND title != '' 
                       AND (teaser IS NOT NULL AND teaser != '' OR body IS NOT NULL AND body != '')";
        $readyResult = $db->query($readyQuery);
        $readyCount = $readyResult->fetch()['count'] ?? 0;
        
        // Old drafts (older than 1 month)
        $oldQuery = "SELECT COUNT(*) as count FROM content 
                     WHERE status = 'draft' 
                     AND updated_at < DATE_SUB(NOW(), INTERVAL 1 MONTH)";
        $oldResult = $db->query($oldQuery);
        $oldCount = $oldResult->fetch()['count'] ?? 0;
        
        return [
            'recent_drafts_count' => $recentCount,
            'ready_to_publish_count' => $readyCount,
            'old_drafts_count' => $oldCount
        ];
    }

    public function create(): void
    {
        $type = $this->getParam('type', ContentModel::TYPE_ARTICLE);
        if (!in_array($type, [ContentModel::TYPE_ARTICLE, ContentModel::TYPE_PHOTOBOOK])) {
            $type = ContentModel::TYPE_ARTICLE;
        }

        // **FIX:** Retrieve form data and errors from session on validation failure
        $formData = $_SESSION['form_data'] ?? [];
        $formErrors = $_SESSION['form_errors'] ?? [];
        unset($_SESSION['form_data'], $_SESSION['form_errors']);

        $this->render('admin/content/create', [
            'content' => null,
            'content_type' => $type,
            'is_edit' => false,
            'form_data' => $formData, // Pass form data to the view
            'form_errors' => $formErrors, // Pass form errors to the view
            'flash' => $this->getFlash(),
            'page_title' => 'Create ' . ucfirst($type),
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    public function store(): void
    {
        // Check if POST data exceeded the limit
        if (empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
            $maxSize = ini_get('post_max_size');
            $this->setFlash('error', "The uploaded content exceeded the maximum allowed size of {$maxSize}. Please reduce file sizes or upload fewer images.");
            $this->redirect('/admin/content/create');
            return;
        }
        
        if (!$this->isPost()) {
            $this->redirect('/admin/content');
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed. Please try again.');
            $this->redirect('/admin/content/create');
            return;
        }

        try {
            $data = $this->getFormData();

            // Ensure URL alias is unique before validation
            if (!empty($data['url_alias'])) {
                $data['url_alias'] = $this->ensureUniqueUrlAlias($data['url_alias']);
            }

            $errors = $this->validateContentData($data);

            if (!empty($errors)) {
                // **FIX:** Store data and errors in session and redirect
                $_SESSION['form_data'] = $data;
                $_SESSION['form_errors'] = $errors;
                $this->setFlash('error', 'Please fix the validation errors below.');
                // Redirect back to the create form, preserving the content type
                $this->redirect('/admin/content/create?type=' . urlencode($data['content_type']));
                return;
            }

            // Handle file uploads
            $uploadErrors = $this->handleFileUploads($data);
            $errors = array_merge($errors, $uploadErrors);

            if (!empty($errors)) {
                // **FIX:** Store data and errors in session and redirect
                $_SESSION['form_data'] = $data;
                $_SESSION['form_errors'] = $errors;
                $this->setFlash('error', 'Please fix the validation errors below.');
                // Redirect back to the create form, preserving the content type
                $this->redirect('/admin/content/create?type=' . urlencode($data['content_type']));
                return;
            }

            // Set additional fields
            $data['user_id'] = $this->getCurrentUserId();
            $data['sort_order'] = ContentModel::getNextSortOrder();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            if ($data['status'] === ContentModel::STATUS_PUBLISHED && empty($data['published_at'])) {
                $data['published_at'] = date('Y-m-d H:i:s');
            } elseif (empty($data['published_at'])) {
                $data['published_at'] = null;
            }

            $content = ContentModel::create($data);

            if ($content && $content->getId()) {
                // Delete any autosaves associated with the UUID if provided
                $autosaveUUID = $this->getParam('autosave_uuid', '', 'post');
                if (!empty($autosaveUUID)) {
                    $autosaveModel = new Autosave();
                    $autosaveModel->deleteByUUID($autosaveUUID);
                }
                
                // Log for debugging
                error_log('Content created: ID=' . $content->getId() . ', URL=' . $data['url_alias'] . ', Status=' . $data['status']);
                
                $this->setFlash('success', ucfirst($data['content_type']) . ' created successfully.');
                $this->redirect('/admin/content/' . $content->getId() . '/edit');
            } else {
                throw new Exception('Failed to create content in the database.');
            }

        } catch (Exception $e) {
            error_log('Content store error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while creating content.');
            $this->redirect('/admin/content/create');
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function getFormData(): array
    {
        // Get the action to determine status
        $action = $this->getParam('action', 'draft', 'post');
        $status = ($action === 'publish') ? ContentModel::STATUS_PUBLISHED : ContentModel::STATUS_DRAFT;
        
        return [
            'title' => $this->sanitize($this->getParam('title', '', 'post')),
            'teaser' => $this->sanitize($this->getParam('teaser', '', 'post')),
            'body' => $this->getParam('body', '', 'post'),
            'url_alias' => $this->sanitize($this->getParam('url_alias', '', 'post')),
            'content_type' => $this->getParam('content_type', ContentModel::TYPE_ARTICLE, 'post'),
            'status' => $status,
            'published_at' => $this->getParam('published_at', '', 'post')
        ];
    }

    /**
     * Ensure the URL alias is unique by appending a number if necessary
     *
     * @param string $urlAlias - The proposed URL alias
     * @param int|null $excludeId - Content ID to exclude from duplicate check (for updates)
     * @return string - A unique URL alias
     */
    private function ensureUniqueUrlAlias(string $urlAlias, ?int $excludeId = null): string
    {
        $baseAlias = $urlAlias;
        $counter = 2;

        while (true) {
            $existing = ContentModel::findByUrlAlias($urlAlias);

            // If no existing content found, or it's the same content we're editing, we're good
            if (!$existing || ($excludeId && $existing->getId() === $excludeId)) {
                break;
            }

            // URL alias is taken, try adding a number
            $urlAlias = $baseAlias . '-' . $counter;
            $counter++;

            // Safety check to prevent infinite loop
            if ($counter > 100) {
                $urlAlias = $baseAlias . '-' . time();
                break;
            }
        }

        return $urlAlias;
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function validateContentData(array $data, ?int $excludeId = null): array
    {
        $errors = [];

        if (empty($data['title'])) {
            $errors['title'] = 'Title is required';
        }

        if (empty($data['url_alias'])) {
            $errors['url_alias'] = 'URL alias is required';
        }

        if (empty($data['body'])) {
            $errors['body'] = 'Content body is required';
        }

        if (!in_array($data['status'], [ContentModel::STATUS_DRAFT, ContentModel::STATUS_PUBLISHED])) {
            $errors['status'] = 'Invalid status selected';
        }

        return $errors;
    }

    public function edit(string $id = ''): void
    {
        $id = (int) $id;
        error_log('Edit content: Looking for ID=' . $id);
        
        $content = ContentModel::find($id);
        
        if (!$content) {
            error_log('Edit content: Content not found for ID=' . $id);
            $this->setFlash('error', 'Content not found.');
            $this->redirect('/admin/content');
            return;
        }
        
        error_log('Edit content: Found content ID=' . $id . ', Title=' . $content->getAttribute('title'));
        
        // Check for autosaves for this content
        $autosaveModel = new Autosave();
        $latestAutosave = $autosaveModel->getLatestForContent($id, $this->getCurrentUserId());
        
        $this->render('admin/content/edit', [
            'content' => $content,
            'content_type' => $content->getAttribute('content_type'),
            'is_edit' => true,
            'flash' => $this->getFlash(),
            'page_title' => 'Edit ' . ucfirst($content->getAttribute('content_type')),
            'csrf_token' => $this->generateCsrfToken(),
            'has_autosave' => !empty($latestAutosave),
            'autosave' => $latestAutosave
        ]);
    }

    public function update(string $id = ''): void
    {
        // Check if POST data exceeded the limit
        if (empty($_POST) && empty($_FILES) && $_SERVER['CONTENT_LENGTH'] > 0) {
            $maxSize = ini_get('post_max_size');
            $id = (int) $id;
            $this->setFlash('error', "The uploaded content exceeded the maximum allowed size of {$maxSize}. Please reduce file sizes or upload fewer images.");
            $this->redirect('/admin/content/' . $id . '/edit');
            return;
        }
        
        if (!$this->isPost()) {
            $this->redirect('/admin/content');
            return;
        }
        
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed.');
            $this->redirect('/admin/content');
            return;
        }
        
        $id = (int) $id;
        $content = ContentModel::find($id);
        
        if (!$content) {
            $this->setFlash('error', 'Content not found.');
            $this->redirect('/admin/content');
            return;
        }
        
        try {
            $data = $this->getFormData();

            // Ensure URL alias is unique before validation (excluding current content)
            if (!empty($data['url_alias'])) {
                $data['url_alias'] = $this->ensureUniqueUrlAlias($data['url_alias'], $id);
            }

            $errors = $this->validateContentData($data, $id);
            
            if (!empty($errors)) {
                $_SESSION['form_data'] = $data;
                $_SESSION['form_errors'] = $errors;
                $this->setFlash('error', 'Please fix the validation errors.');
                $this->redirect('/admin/content/' . $id . '/edit');
                return;
            }
            
            // Handle file uploads
            $uploadErrors = $this->handleFileUploads($data);
            $errors = array_merge($errors, $uploadErrors);

            if (!empty($errors)) {
                $_SESSION['form_data'] = $data;
                $_SESSION['form_errors'] = $errors;
                $this->setFlash('error', 'Please fix the validation errors.');
                $this->redirect('/admin/content/' . $id . '/edit');
                return;
            }
            
            // Update timestamps
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            if ($data['status'] === ContentModel::STATUS_PUBLISHED && empty($content->getAttribute('published_at'))) {
                $data['published_at'] = date('Y-m-d H:i:s');
            } elseif (empty($data['published_at'])) {
                $data['published_at'] = null;
            }
            
            // Set updated data on content model
            foreach ($data as $key => $value) {
                $content->setAttribute($key, $value);
            }
            
            if ($content->save()) {
                // Delete all autosaves for this content after successful save
                $autosaveModel = new Autosave();
                $autosaveModel->deleteByContentId($id);
                
                // Also delete autosaves by UUID if provided
                $autosaveUUID = $this->getParam('autosave_uuid', '', 'post');
                if (!empty($autosaveUUID)) {
                    $autosaveModel->deleteByUUID($autosaveUUID);
                }
                
                $this->setFlash('success', ucfirst($content->getAttribute('content_type')) . ' updated successfully.');
                $this->redirect('/admin/content/' . $id . '/edit');
            } else {
                throw new Exception('Failed to update content.');
            }
        } catch (Exception $e) {
            error_log('Content update error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while updating content.');
            $this->redirect('/admin/content/' . $id . '/edit');
        }
    }

    public function delete(string $id = ''): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/content');
            return;
        }
        
        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed.');
            $this->redirect('/admin/content');
            return;
        }
        
        $id = (int) $id;
        $content = ContentModel::find($id);
        
        if (!$content) {
            $this->setFlash('error', 'Content not found.');
            $this->redirect('/admin/content');
            return;
        }
        
        try {
            // Delete associated images
            $featuredImage = $content->getAttribute('featured_image');
            $teaserImage = $content->getAttribute('teaser_image');
            
            $uploadPath = dirname(__DIR__, 3) . '/uploads/';
            if ($featuredImage && file_exists($uploadPath . $featuredImage)) {
                unlink($uploadPath . $featuredImage);
            }
            
            if ($teaserImage && file_exists($uploadPath . $teaserImage)) {
                unlink($uploadPath . $teaserImage);
            }
            
            if ($content->delete()) {
                $this->setFlash('success', ucfirst($content->getAttribute('content_type')) . ' deleted successfully.');
            } else {
                throw new Exception('Failed to delete content.');
            }
        } catch (Exception $e) {
            error_log('Content delete error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while deleting content.');
        }
        
        $this->redirect('/admin/content');
    }

    public function reorder(): void
    {
        $type = $this->getParam('type', '');
        
        if (!empty($type) && !in_array($type, [ContentModel::TYPE_ARTICLE, ContentModel::TYPE_PHOTOBOOK])) {
            $type = '';
        }
        
        $content = ContentModel::getForReordering($type ?: null);
        
        $this->render('admin/content/reorder', [
            'content' => $content,
            'content_type' => $type,
            'flash' => $this->getFlash(),
            'page_title' => 'Reorder Content',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    public function updateOrder(): void
    {
        if (!$this->isPost()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed']);
            return;
        }
        
        try {
            $orderJson = $this->getParam('order', '', 'post');

            if (empty($orderJson)) {
                throw new Exception('No order data provided');
            }

            // Parse JSON order data
            $orderData = json_decode($orderJson, true);

            if (!is_array($orderData)) {
                throw new Exception('Invalid order data format');
            }

            // Transform from [{"id":15,"position":1},...] to ["15" => 1,...]
            $transformedOrder = [];
            foreach ($orderData as $item) {
                if (!isset($item['id']) || !isset($item['position'])) {
                    throw new Exception('Invalid order item format');
                }
                $transformedOrder[(string)$item['id']] = (int)$item['position'];
            }

            if (ContentModel::updateSortOrder($transformedOrder)) {
                $this->renderJson(['success' => true, 'message' => 'Order updated successfully']);
            } else {
                throw new Exception('Failed to update order');
            }
        } catch (Exception $e) {
            error_log('Update order error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => 'An error occurred while updating order']);
        }
    }


    public function createDraft(): void
    {
        if (!$this->isPost()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed']);
            return;
        }
        
        try {
            $title = $this->sanitize($this->getParam('title', '', 'post'));
            $contentType = $this->getParam('content_type', ContentModel::TYPE_ARTICLE, 'post');
            
            if (empty($title)) {
                throw new Exception('Title is required to create draft');
            }
            
            if (!in_array($contentType, [ContentModel::TYPE_ARTICLE, ContentModel::TYPE_PHOTOBOOK])) {
                $contentType = ContentModel::TYPE_ARTICLE;
            }
            
            // Generate URL alias from title
            $urlAlias = $this->generateUrlAlias($title);
            
            // Create minimal draft content
            $data = [
                'title' => $title,
                'url_alias' => $urlAlias,
                'body' => '',
                'teaser' => '',
                'content_type' => $contentType,
                'status' => ContentModel::STATUS_DRAFT,
                'user_id' => $this->getCurrentUserId(),
                'sort_order' => ContentModel::getNextSortOrder(),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'published_at' => null
            ];
            
            $content = ContentModel::create($data);
            
            if (!$content || !$content->getId()) {
                throw new Exception('Failed to create draft content');
            }
            
            $this->renderJson([
                'success' => true,
                'content_id' => $content->getId(),
                'url_alias' => $urlAlias,
                'message' => 'Draft created successfully'
            ]);
            
        } catch (Exception $e) {
            error_log('Create draft error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function uploadImage(): void
    {
        if (!$this->isPost()) {
            $this->renderJson(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        if (!$this->validateCsrfToken()) {
            $this->renderJson(['success' => false, 'message' => 'Security token validation failed']);
            return;
        }
        
        try {
            if (empty($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }
            
            // Create year/month folder structure for content images
            $yearMonth = date('Y/m');
            $imagePath = dirname(__DIR__, 3) . '/uploads/content/images/' . $yearMonth . '/';
            
            // Create directory if it doesn't exist
            if (!is_dir($imagePath)) {
                mkdir($imagePath, 0755, true);
            }
            
            $uploadConfig = [
                'upload_path' => $imagePath,
                'max_size' => 5 * 1024 * 1024, // 5MB
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
            ];
            
            $upload = new FileUpload($uploadConfig);
            $result = $upload->upload($_FILES['file']);
            
            if ($result['success']) {
                $this->renderJson([
                    'location' => '/uploads/content/images/' . $yearMonth . '/' . $result['filename']
                ]);
            } else {
                throw new Exception($result['error'] ?? 'Upload failed');
            }
        } catch (Exception $e) {
            error_log('Image upload error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, string>
     */
    private function handleFileUploads(array &$data): array
    {
        $errors = [];
        // Create year/month folder structure
        $yearMonth = date('Y/m');
        $uploadBasePath = dirname(__DIR__, 3) . '/uploads/content/';
        
        // Handle featured image upload
        if (!empty($_FILES['featured_image']['name'])) {
            $featuredPath = $uploadBasePath . 'featured/' . $yearMonth . '/';
            
            // Create directory if it doesn't exist
            if (!is_dir($featuredPath)) {
                mkdir($featuredPath, 0755, true);
            }
            
            $uploadConfig = [
                'upload_path' => $featuredPath,
                'max_size' => 5 * 1024 * 1024, // 5MB
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
            ];
            
            $upload = new FileUpload($uploadConfig);
            $result = $upload->upload($_FILES['featured_image']);

            if ($result['success']) {
                $data['featured_image'] = '/uploads/content/featured/' . $yearMonth . '/' . $result['filename'];
            } else {
                $errors['featured_image'] = $result['error'];
            }
        }

        // Handle teaser image upload (only for photobooks)
        if (!empty($_FILES['teaser_image']['name']) && ($data['content_type'] ?? '') === 'photobook') {
            $teaserPath = $uploadBasePath . 'teasers/' . $yearMonth . '/';

            // Create directory if it doesn't exist
            if (!is_dir($teaserPath)) {
                mkdir($teaserPath, 0755, true);
            }

            $uploadConfig = [
                'upload_path' => $teaserPath,
                'max_size' => 5 * 1024 * 1024, // 5MB
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp']
            ];

            $upload = new FileUpload($uploadConfig);
            $result = $upload->upload($_FILES['teaser_image']);

            if ($result['success']) {
                $data['teaser_image'] = '/uploads/content/teasers/' . $yearMonth . '/' . $result['filename'];
            } else {
                $errors['teaser_image'] = $result['error'];
            }
        }
        return $errors;
    }

    /**
     * Generate URL alias from title
     */
    private function generateUrlAlias(string $title): string
    {
        // Convert to lowercase and replace spaces with hyphens
        $alias = strtolower(trim($title));
        $alias = preg_replace('/[^a-z0-9\s\-]/', '', $alias);
        $alias = preg_replace('/\s+/', '-', $alias);
        $alias = preg_replace('/-+/', '-', $alias);
        $alias = trim($alias, '-');
        
        // Ensure uniqueness by checking existing aliases
        $baseAlias = $alias;
        $counter = 1;
        
        while (ContentModel::findByUrlAlias($alias)) {
            $alias = $baseAlias . '-' . $counter;
            $counter++;
        }
        
        return $alias ?: 'untitled-' . time();
    }
    
    /**
     * Bulk delete multiple drafts
     */
    public function bulkDelete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/admin/content/drafts');
            return;
        }
        
        if (!$this->validateCsrfToken()) {
            $this->redirect('/admin/content/drafts?error=invalid_token');
            return;
        }
        
        $contentIds = $_POST['content_ids'] ?? [];
        
        if (empty($contentIds) || !is_array($contentIds)) {
            $this->redirect('/admin/content/drafts?error=no_items_selected');
            return;
        }
        
        $deletedCount = 0;
        $errors = [];
        
        foreach ($contentIds as $contentId) {
            $contentId = (int) $contentId;
            if ($contentId <= 0) {
                continue;
            }
            
            try {
                // Verify this is a draft and belongs to current user (security check)
                $content = ContentModel::findById($contentId);
                if (!$content) {
                    $errors[] = "Content ID {$contentId} not found";
                    continue;
                }
                
                // Only allow deletion of drafts
                if ($content['status'] !== ContentModel::STATUS_DRAFT) {
                    $errors[] = "Content ID {$contentId} is not a draft";
                    continue;
                }
                
                // Delete the content
                if (ContentModel::delete($contentId)) {
                    $deletedCount++;
                } else {
                    $errors[] = "Failed to delete content ID {$contentId}";
                }
                
            } catch (Exception $e) {
                $errors[] = "Error deleting content ID {$contentId}: " . $e->getMessage();
            }
        }
        
        // Build redirect message
        $message = "Successfully deleted {$deletedCount} draft(s)";
        if (!empty($errors)) {
            $message .= ". Errors: " . implode(', ', $errors);
        }
        
        $redirectUrl = '/admin/content/drafts?message=' . urlencode($message);
        if ($deletedCount === 0 && !empty($errors)) {
            $redirectUrl = '/admin/content/drafts?error=' . urlencode($message);
        }
        
        $this->redirect($redirectUrl);
    }

    public function autosave(): void
    {
        if (!$this->isPost()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        try {
            $data = [
                'autosave_uuid' => $this->sanitize($this->getParam('autosave_uuid', '', 'post')),
                'master_content_uuid' => $this->sanitize($this->getParam('master_content_uuid', '', 'post')),
                'content_id' => $this->getParam('content_id', null, 'post') ? (int)$this->getParam('content_id', null, 'post') : null,
                'user_id' => $this->getCurrentUserId(),
                'title' => $this->sanitize($this->getParam('title', '', 'post')),
                'content' => $this->getParam('body', '', 'post'),
                'excerpt' => $this->sanitize($this->getParam('teaser', '', 'post')),
                'type' => $this->getParam('content_type', ContentModel::TYPE_ARTICLE, 'post'),
                'featured_image' => $this->sanitize($this->getParam('featured_image', '', 'post')),
                'meta_title' => $this->sanitize($this->getParam('meta_title', '', 'post')),
                'meta_description' => $this->sanitize($this->getParam('meta_description', '', 'post')),
                'meta_keywords' => $this->sanitize($this->getParam('meta_keywords', '', 'post'))
            ];

            // Validate required fields  
            if (empty($data['autosave_uuid']) || empty($data['title'])) {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'UUID and title are required for autosave'
                ]);
                return;
            }

            $autosaveModel = new Autosave();
            $autosaveId = $autosaveModel->createOrUpdate($data);

            if ($autosaveId) {
                $this->jsonResponse([
                    'success' => true,
                    'message' => 'Content autosaved successfully',
                    'autosave_id' => $autosaveId,
                    'timestamp' => date('Y-m-d H:i:s')
                ]);
            } else {
                throw new Exception('Failed to save autosave');
            }

        } catch (Exception $e) {
            error_log('Autosave error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to autosave content'
            ]);
        }
    }

    public function loadAutosave(): void
    {
        if (!$this->isPost()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        try {
            $autosaveUUID = $this->sanitize($this->getParam('autosave_uuid', '', 'post'));
            $masterContentUUID = $this->sanitize($this->getParam('master_content_uuid', '', 'post'));
            $contentId = $this->getParam('content_id', null, 'post') ? (int)$this->getParam('content_id', null, 'post') : null;

            $autosaveModel = new Autosave();
            
            if ($masterContentUUID) {
                // Load autosave by master content UUID (preferred method)
                $autosave = $autosaveModel->findByMasterUUID($masterContentUUID);
            } elseif ($contentId) {
                // Load autosave for existing content
                $autosave = $autosaveModel->findByContentId($contentId, $this->getCurrentUserId());
            } elseif ($autosaveUUID) {
                // Fallback to autosave UUID
                $autosave = $autosaveModel->findByUUID($autosaveUUID);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'No autosave identifier provided'
                ]);
                return;
            }

            if ($autosave) {
                $this->jsonResponse([
                    'success' => true,
                    'autosave' => $autosave
                ]);
            } else {
                $this->jsonResponse([
                    'success' => false,
                    'message' => 'No autosave found'
                ]);
            }

        } catch (Exception $e) {
            error_log('Load autosave error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to load autosave'
            ]);
        }
    }

    public function listAutosaves(): void
    {
        if (!$this->isPost()) {
            $this->jsonResponse(['success' => false, 'message' => 'Invalid request method']);
            return;
        }

        try {
            // In the new architecture, list all autosaves for the current user
            // since each content piece has only one autosave
            $autosaveModel = new Autosave();
            $autosaves = $autosaveModel->getAllAutosaves($this->getCurrentUserId());

            $this->jsonResponse([
                'success' => true,
                'autosaves' => $autosaves,
                'count' => count($autosaves)
            ]);

        } catch (Exception $e) {
            error_log('List autosaves error: ' . $e->getMessage());
            $this->jsonResponse([
                'success' => false,
                'message' => 'Failed to list autosaves'
            ]);
        }
    }

    public function autosaves(): void
    {
        $page = (int) $this->getParam('page', 1);
        $search = $this->sanitize($this->getParam('search', ''));
        
        // Get autosaves for current user
        $autosaveModel = new Autosave();
        $autosaves = $autosaveModel->getAllAutosaves($this->getCurrentUserId());
        
        // Filter by search if provided
        if (!empty($search)) {
            $autosaves = array_filter($autosaves, function($autosave) use ($search) {
                return stripos($autosave['title'], $search) !== false ||
                       stripos($autosave['content'], $search) !== false;
            });
        }
        
        // Pagination
        $itemsPerPage = 20;
        $totalItems = count($autosaves);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $itemsPerPage;
        $autosaves = array_slice($autosaves, $offset, $itemsPerPage);
        
        $this->render('admin/content/autosaves', [
            'autosaves' => $autosaves,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'search' => $search,
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    public function deleteAutosave(int $id): void
    {
        if (!$this->isPost()) {
            $this->redirect('/admin/autosaves');
            return;
        }

        if (!$this->validateCsrfToken()) {
            $this->setFlash('error', 'Security token validation failed.');
            $this->redirect('/admin/autosaves');
            return;
        }

        try {
            $autosave = Autosave::find($id);

            if (!$autosave) {
                $this->setFlash('error', 'Autosave not found.');
                $this->redirect('/admin/autosaves');
                return;
            }

            // Verify ownership
            if ($autosave->getAttribute('user_id') !== $this->getCurrentUserId()) {
                $this->setFlash('error', 'Unauthorized.');
                $this->redirect('/admin/autosaves');
                return;
            }

            $success = $autosave->delete();

            if ($success) {
                $this->setFlash('success', 'Autosave deleted successfully.');
            } else {
                $this->setFlash('error', 'Failed to delete autosave.');
            }

        } catch (Exception $e) {
            error_log('Delete autosave error: ' . $e->getMessage());
            $this->setFlash('error', 'An error occurred while deleting the autosave.');
        }

        $this->redirect('/admin/autosaves');
    }

    private function jsonResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
