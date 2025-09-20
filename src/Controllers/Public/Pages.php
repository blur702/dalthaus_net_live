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

        // Show full content with page break indicators
        $content = $page->getContentWithPageBreaks();

        // Render page template
        $this->render('pages/show', [
            'page' => $page,
            'content' => $content,
            'page_title' => $page->getAttribute('title'),
            'meta_description' => $page->getAttribute('meta_description'),
            'meta_keywords' => $page->getAttribute('meta_keywords')
        ]);
    }
}
