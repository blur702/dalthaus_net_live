<?php

declare(strict_types=1);

namespace CMS\Controllers\Public;

use CMS\Controllers\BaseController;
use CMS\Models\Page;

/**
 * Pages Controller
 * 
 * Handles static page display.
 * 
 * @package CMS\Controllers\Public
 * @author  Kevin
 * @version 1.0.0
 */
class Pages extends BaseController
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
     * Display individual page
     * 
     * @param string $alias Page URL alias
     * @return void
     */
    public function show(string $alias): void
    {
        // Find page by alias
        $page = Page::findByAlias($alias);
        
        if ($page === null) {
            http_response_code(404);
            $this->render('errors/404', [
                'page_title' => 'Page Not Found'
            ]);
            return;
        }

        // Check if user wants full view with page breaks
        $viewMode = $this->getParam('view', 'paginated');
        
        if ($viewMode === 'full') {
            // Show full content with visual page breaks
            $content = $page->getContentWithPageBreaks();
            $this->render('pages/show', [
                'page' => $page,
                'content' => $content,
                'view_mode' => 'full',
                'current_page' => 1,
                'total_pages' => 1,
                'page_title' => $page->getAttribute('title'),
                'meta_description' => $page->getAttribute('meta_description'),
                'meta_keywords' => $page->getAttribute('meta_keywords')
            ]);
        } else {
            // Show paginated content (default)
            $contentPages = $page->getContentPages();
            $currentPage = max(1, min(count($contentPages), (int) $this->getParam('p', 1)));
            $currentContent = $contentPages[$currentPage - 1] ?? '';

            // Render page template
            $this->render('pages/show', [
                'page' => $page,
                'content' => $currentContent,
                'view_mode' => 'paginated',
                'current_page' => $currentPage,
                'total_pages' => count($contentPages),
                'page_title' => $page->getAttribute('title'),
                'meta_description' => $page->getAttribute('meta_description'),
                'meta_keywords' => $page->getAttribute('meta_keywords')
            ]);
        }
    }
}
