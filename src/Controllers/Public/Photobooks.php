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
        // Find photobook by alias
        $photobook = Content::findByAlias($alias);
        
        if ($photobook === null || !$photobook->isPhotobook()) {
            http_response_code(404);
            $this->render('errors/404', [
                'page_title' => 'Photobook Not Found'
            ]);
            return;
        }

        // Check if user wants full view with page breaks
        $viewMode = $this->getParam('view', 'paginated');
        
        if ($viewMode === 'full') {
            // Show full content with visual page breaks
            $content = $photobook->getContentWithPageBreaks();
            $this->render('photobooks/show', [
                'photobook' => $photobook,
                'content' => $content,
                'view_mode' => 'full',
                'current_page' => 1,
                'total_pages' => 1,
                'author' => $photobook->getAuthor(),
                'page_title' => $photobook->getAttribute('title'),
                'meta_description' => $photobook->getAttribute('meta_description'),
                'meta_keywords' => $photobook->getAttribute('meta_keywords')
            ]);
        } else {
            // Show paginated content (default)
            $contentPages = $photobook->getContentPages();
            $currentPage = max(1, min(count($contentPages), (int) $this->getParam('p', 1)));
            $currentContent = $contentPages[$currentPage - 1] ?? '';

            // Get author information
            $author = $photobook->getAuthor();

            // Render photobook template
            $this->render('photobooks/show', [
                'photobook' => $photobook,
                'content' => $currentContent,
                'view_mode' => 'paginated',
                'current_page' => $currentPage,
                'total_pages' => count($contentPages),
                'author' => $author,
                'page_title' => $photobook->getAttribute('title'),
                'meta_description' => $photobook->getAttribute('meta_description'),
                'meta_keywords' => $photobook->getAttribute('meta_keywords')
            ]);
        }
    }
}
