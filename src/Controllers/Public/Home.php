<?php

declare(strict_types=1);

namespace CMS\Controllers\Public;

use CMS\Controllers\BaseController;
use CMS\Models\Content;

/**
 * Home Controller
 * 
 * Handles the homepage display with articles and photobooks listings.
 * 
 * @package CMS\Controllers\Public
 * @author  Kevin
 * @version 1.0.0
 */
class Home extends BaseController
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
     * Display homepage
     * 
     * @return void
     */
    public function index(): void
    {
        // Get recent articles (3 most recent)
        $articles = Content::getPublishedArticles(3);
        
        // Get recent photobooks (3 most recent)
        $photobooks = Content::getPublishedPhotobooks(3);

        // Render homepage template
        $this->render('home/index', [
            'articles' => $articles,
            'photobooks' => $photobooks,
            'page_title' => 'Home'
        ]);
    }
}
