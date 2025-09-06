<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Content as ContentModel;
use CMS\Utils\FileUpload;
use Exception;

class Content extends BaseController
{
    private const ITEMS_PER_PAGE = 20;

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
            'sort_by' => $sortBy
        ];
        
        // Get content items
        $totalItems = ContentModel::countWithFilters($filters);
        $totalPages = ceil($totalItems / self::ITEMS_PER_PAGE);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * self::ITEMS_PER_PAGE;
        
        $content = ContentModel::findWithFilters($filters, self::ITEMS_PER_PAGE, $offset);
        
        // Prepare pagination data
        $pagination = [
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_items' => $totalItems,
            'items_per_page' => self::ITEMS_PER_PAGE,
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
            $this->handleFileUploads($data);

            // Set additional fields
            $data['user_id'] = $this->getCurrentUserId();
            $data['sort_order'] = ContentModel::getNextSortOrder();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            if ($data['status'] === ContentModel::STATUS_PUBLISHED && empty($data['published_at'])) {
                $data['published_at'] = date('Y-m-d H:i:s');
            }

            $content = ContentModel::create($data);

            if ($content) {
                $this->setFlash('success', ucfirst($data['content_type']) . ' created successfully.');
                $this->redirect('/admin/content/' . $content->getAttribute('content_id') . '/edit');
            } else {
                throw new Exception('Failed to create content in the database.');
            }

        } catch (Exception $e) {
            $this->logError('Content store error', $e);
            $this->setFlash('error', 'An error occurred while creating content.');
            $this->redirect('/admin/content/create');
        }
    }

    private function getFormData(): array
    {
        return [
            'title' => $this->sanitize($this->getParam('title', '', 'post')),
            'teaser' => $this->sanitize($this->getParam('teaser', '', 'post')),
            'body' => $this->getParam('body', '', 'post'),
            'url_alias' => $this->sanitize($this->getParam('url_alias', '', 'post')),
            'content_type' => $this->getParam('content_type', ContentModel::TYPE_ARTICLE, 'post'),
            'status' => $this->getParam('action', ContentModel::STATUS_DRAFT, 'post'),
            'published_at' => $this->getParam('published_at', '', 'post'),
            'meta_keywords' => $this->sanitize($this->getParam('meta_keywords', '', 'post')),
            'meta_description' => $this->sanitize($this->getParam('meta_description', '', 'post'))
        ];
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

        if (empty($data['body'])) {
            $errors['body'] = 'Content body is required';
        }

        if (!in_array($data['status'], [ContentModel::STATUS_DRAFT, ContentModel::STATUS_PUBLISHED])) {
            $errors['status'] = 'Invalid status selected';
        }

        return $errors;
    }

    public function edit(): void
    {
        $id = (int) $this->getParam('id');
        $content = ContentModel::findById($id);
        
        if (!$content) {
            $this->setFlash('error', 'Content not found.');
            $this->redirect('/admin/content');
            return;
        }
        
        $this->render('admin/content/edit', [
            'content' => $content,
            'content_type' => $content->getAttribute('content_type'),
            'is_edit' => true,
            'flash' => $this->getFlash(),
            'page_title' => 'Edit ' . ucfirst($content->getAttribute('content_type')),
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    public function update(): void
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
        
        $id = (int) $this->getParam('id');
        $content = ContentModel::findById($id);
        
        if (!$content) {
            $this->setFlash('error', 'Content not found.');
            $this->redirect('/admin/content');
            return;
        }
        
        try {
            $data = $this->getFormData();
            $errors = $this->validateContentData($data, $id);
            
            if (!empty($errors)) {
                $_SESSION['form_data'] = $data;
                $_SESSION['form_errors'] = $errors;
                $this->setFlash('error', 'Please fix the validation errors.');
                $this->redirect('/admin/content/' . $id . '/edit');
                return;
            }
            
            // Handle file uploads
            $this->handleFileUploads($data);
            
            // Update timestamps
            $data['updated_at'] = date('Y-m-d H:i:s');
            
            if ($data['status'] === ContentModel::STATUS_PUBLISHED && empty($content->getAttribute('published_at'))) {
                $data['published_at'] = date('Y-m-d H:i:s');
            }
            
            if ($content->update($data)) {
                $this->setFlash('success', ucfirst($content->getAttribute('content_type')) . ' updated successfully.');
                $this->redirect('/admin/content/' . $id . '/edit');
            } else {
                throw new Exception('Failed to update content.');
            }
        } catch (Exception $e) {
            $this->logError('Content update error', $e);
            $this->setFlash('error', 'An error occurred while updating content.');
            $this->redirect('/admin/content/' . $id . '/edit');
        }
    }

    public function delete(): void
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
        
        $id = (int) $this->getParam('id');
        $content = ContentModel::findById($id);
        
        if (!$content) {
            $this->setFlash('error', 'Content not found.');
            $this->redirect('/admin/content');
            return;
        }
        
        try {
            // Delete associated images
            $featuredImage = $content->getAttribute('featured_image');
            $teaserImage = $content->getAttribute('teaser_image');
            
            if ($featuredImage && file_exists(PUBLIC_PATH . '/uploads/' . $featuredImage)) {
                unlink(PUBLIC_PATH . '/uploads/' . $featuredImage);
            }
            
            if ($teaserImage && file_exists(PUBLIC_PATH . '/uploads/' . $teaserImage)) {
                unlink(PUBLIC_PATH . '/uploads/' . $teaserImage);
            }
            
            if ($content->delete()) {
                $this->setFlash('success', ucfirst($content->getAttribute('content_type')) . ' deleted successfully.');
            } else {
                throw new Exception('Failed to delete content.');
            }
        } catch (Exception $e) {
            $this->logError('Content delete error', $e);
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
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        if (!$this->validateCsrfToken()) {
            $this->json(['success' => false, 'message' => 'Security token validation failed']);
            return;
        }
        
        try {
            $order = $this->getParam('order', [], 'post');
            
            if (empty($order) || !is_array($order)) {
                throw new Exception('Invalid order data');
            }
            
            if (ContentModel::updateSortOrder($order)) {
                $this->json(['success' => true, 'message' => 'Order updated successfully']);
            } else {
                throw new Exception('Failed to update order');
            }
        } catch (Exception $e) {
            $this->logError('Update order error', $e);
            $this->json(['success' => false, 'message' => 'An error occurred while updating order']);
        }
    }

    public function autosave(): void
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        if (!$this->validateCsrfToken()) {
            $this->json(['success' => false, 'message' => 'Security token validation failed']);
            return;
        }
        
        try {
            $id = (int) $this->getParam('id');
            $field = $this->sanitize($this->getParam('field', '', 'post'));
            $value = $this->getParam('value', '', 'post');
            
            if (!$id || !$field) {
                throw new Exception('Invalid autosave data');
            }
            
            $content = ContentModel::findById($id);
            
            if (!$content) {
                throw new Exception('Content not found');
            }
            
            // Only allow certain fields to be autosaved
            $allowedFields = ['title', 'teaser', 'body', 'meta_keywords', 'meta_description'];
            
            if (!in_array($field, $allowedFields)) {
                throw new Exception('Field not allowed for autosave');
            }
            
            $content->update([
                $field => $value,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            
            $this->json([
                'success' => true,
                'message' => 'Autosaved',
                'timestamp' => date('g:i:s A')
            ]);
        } catch (Exception $e) {
            $this->logError('Autosave error', $e);
            $this->json(['success' => false, 'message' => 'Autosave failed']);
        }
    }

    public function uploadImage(): void
    {
        if (!$this->isPost()) {
            $this->json(['success' => false, 'message' => 'Invalid request method']);
            return;
        }
        
        if (!$this->validateCsrfToken()) {
            $this->json(['success' => false, 'message' => 'Security token validation failed']);
            return;
        }
        
        try {
            if (empty($_FILES['file'])) {
                throw new Exception('No file uploaded');
            }
            
            $upload = new FileUpload($_FILES['file']);
            $upload->setAllowedTypes(['jpg', 'jpeg', 'png', 'gif', 'webp']);
            $upload->setMaxSize(5 * 1024 * 1024); // 5MB
            $upload->setUploadPath(PUBLIC_PATH . '/uploads/content/');
            
            if ($upload->upload()) {
                $this->json([
                    'location' => '/uploads/content/' . $upload->getFileName()
                ]);
            } else {
                throw new Exception($upload->getError());
            }
        } catch (Exception $e) {
            $this->logError('Image upload error', $e);
            $this->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    private function handleFileUploads(array &$data): void
    {
        // Handle featured image upload
        if (!empty($_FILES['featured_image']['name'])) {
            $upload = new FileUpload($_FILES['featured_image']);
            $upload->setAllowedTypes(['jpg', 'jpeg', 'png', 'gif', 'webp']);
            $upload->setMaxSize(5 * 1024 * 1024); // 5MB
            $upload->setUploadPath(PUBLIC_PATH . '/uploads/');
            
            if ($upload->upload()) {
                $data['featured_image'] = $upload->getFileName();
            }
        }
        
        // Handle teaser image upload
        if (!empty($_FILES['teaser_image']['name'])) {
            $upload = new FileUpload($_FILES['teaser_image']);
            $upload->setAllowedTypes(['jpg', 'jpeg', 'png', 'gif', 'webp']);
            $upload->setMaxSize(5 * 1024 * 1024); // 5MB
            $upload->setUploadPath(PUBLIC_PATH . '/uploads/');
            
            if ($upload->upload()) {
                $data['teaser_image'] = $upload->getFileName();
            }
        }
    }
}
