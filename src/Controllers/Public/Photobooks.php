<?php

declare(strict_types=1);

namespace CMS\Controllers\Public;

use CMS\Controllers\BaseController;
use CMS\Models\Content;

/**
 * Photobooks Controller
 * 
 * Handles photobook listings and individual photobook display.
 * 
 * @package CMS\Controllers\Public
 * @author  Kevin
 * @version 1.0.0
 */
class Photobooks extends BaseController
{
    /**
     * Initialize controller
     * 
     * @return void
     */
    protected function initialize(): void
    {
        // Set default layout for public pages
        $this->view->layout('default');
    }

    /**
     * Display photobooks listing page
     * 
     * @return void
     */
    public function index(): void
    {
        // Handle database unavailable in development mode
        if ($this->db === null) {
            $this->render('photobooks/index', [
                'photobooks' => [],
                'current_page' => 1,
                'total_pages' => 0,
                'total_photobooks' => 0,
                'page_title' => 'Photobooks',
                'debug_message' => 'Database connection unavailable - showing empty photobooks list'
            ]);
            return;
        }

        $page = max(1, (int) $this->getParam('page', 1));
        $itemsPerPage = $this->config['app']['items_per_page'];
        $offset = ($page - 1) * $itemsPerPage;

        // Get published photobooks with pagination
        $photobooks = Content::getPublishedPhotobooks($itemsPerPage, $offset);
        
        // Get total count for pagination
        $totalPhotobooks = Content::count([
            'content_type' => Content::TYPE_PHOTOBOOK,
            'status' => Content::STATUS_PUBLISHED
        ]);
        
        $totalPages = ceil($totalPhotobooks / $itemsPerPage);

        // Render photobooks listing template
        $this->render('photobooks/index', [
            'photobooks' => $photobooks,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_photobooks' => $totalPhotobooks,
            'page_title' => 'Photobooks'
        ]);
    }

    /**
     * Display individual photobook
     * 
     * @param string $alias Photobook URL alias
     * @return void
     */
    public function show(string $alias): void
    {
        // Handle database unavailable in development mode
        if ($this->db === null) {
            $this->render('photobooks/show', [
                'photobook' => null,
                'content' => '',
                'current_page' => 1,
                'total_pages' => 0,
                'author' => null,
                'can_edit' => false,
                'page_title' => 'Photobook: ' . $alias,
                'debug_message' => 'Database connection unavailable - showing placeholder for photobook: ' . $alias
            ]);
            return;
        }

        // Find photobook by alias
        $photobook = Content::findByAlias($alias);
        
        if ($photobook === null || !$photobook->isPhotobook()) {
            // Prevent Cloudflare from caching 404 responses
            header('Cache-Control: no-cache, no-store, must-revalidate');
            header('Pragma: no-cache');
            header('Expires: 0');
            http_response_code(404);
            $this->render('errors/404', [
                'page_title' => 'Photobook Not Found'
            ]);
            return;
        }

        // Show paginated content based on TinyMCE page breaks
        $contentPages = $photobook->getContentPages();
        $currentPage = max(1, min(count($contentPages), (int) $this->getParam('p', 1)));
        $currentContent = $contentPages[$currentPage - 1] ?? '';

        // Get author information
        $author = $photobook->getAuthor();

        // Check if user can edit this content
        $canEdit = $this->isAuthenticated() && 
                   isset($_SESSION['is_admin']) && 
                   $_SESSION['is_admin'];

        // Render photobook template
        $this->render('photobooks/show', [
            'photobook' => $photobook,
            'content' => $currentContent,
            'current_page' => $currentPage,
            'total_pages' => count($contentPages),
            'author' => $author,
            'can_edit' => $canEdit,
            'page_title' => $photobook->getAttribute('title'),
            'meta_description' => $photobook->getAttribute('meta_description'),
            'meta_keywords' => $photobook->getAttribute('meta_keywords')
        ]);
    }
}
