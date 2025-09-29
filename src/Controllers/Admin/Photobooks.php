<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Content as ContentModel;
use Exception;

/**
 * Photobooks Management Controller
 * Handles photobook-specific administration tasks
 */
class Photobooks extends BaseController
{
    protected function initialize(): void
    {
        $this->requireAuth();
        $this->view->layout('admin');
    }

    /**
     * Display photobooks index page with management options
     */
    public function index(): void
    {
        $page = (int) $this->getParam('page', 1);
        $search = $this->sanitize($this->getParam('search', ''));
        $status = $this->getParam('status', '');
        $sortBy = $this->getParam('sort_by', 'created_at');
        $sortDir = $this->getParam('sort_dir', 'DESC');

        // Build filters specifically for photobooks
        $filters = [
            'search' => $search,
            'status' => $status,
            'content_type' => ContentModel::TYPE_PHOTOBOOK,
            'sort_by' => $sortBy,
            'sort_dir' => $sortDir
        ];

        // Get photobooks
        $itemsPerPage = $this->config['app']['items_per_page'] ?? 10;
        $totalItems = ContentModel::countWithFilters($filters);
        $totalPages = ceil($totalItems / $itemsPerPage);
        $page = max(1, min($page, $totalPages ?: 1));
        $offset = ($page - 1) * $itemsPerPage;

        $photobooks = ContentModel::findWithFilters($filters, $itemsPerPage, (int) $offset);

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

        $this->render('admin/photobooks/index', [
            'photobooks' => $photobooks,
            'filters' => $filters,
            'pagination' => $pagination,
            'flash' => $this->getFlash(),
            'page_title' => 'Photobooks Management',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    /**
     * Display photobook reordering interface
     */
    public function reorder(): void
    {
        $photobooks = ContentModel::getForReordering(ContentModel::TYPE_PHOTOBOOK);

        $this->render('admin/photobooks/reorder', [
            'photobooks' => $photobooks,
            'flash' => $this->getFlash(),
            'page_title' => 'Reorder Photobooks',
            'csrf_token' => $this->generateCsrfToken()
        ]);
    }

    /**
     * Update photobook sort order via AJAX
     */
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

            // Verify all items are photobooks
            foreach ($orderData as $item) {
                if (!isset($item['id'])) {
                    throw new Exception('Invalid order item format');
                }

                $content = ContentModel::find($item['id']);
                if (!$content || $content->getAttribute('content_type') !== ContentModel::TYPE_PHOTOBOOK) {
                    throw new Exception('Only photobooks can be reordered in this interface');
                }
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
                $this->renderJson(['success' => true, 'message' => 'Photobook order updated successfully']);
            } else {
                throw new Exception('Failed to update photobook order');
            }
        } catch (Exception $e) {
            error_log('Update photobook order error: ' . $e->getMessage());
            $this->renderJson(['success' => false, 'message' => 'An error occurred while updating photobook order']);
        }
    }
}