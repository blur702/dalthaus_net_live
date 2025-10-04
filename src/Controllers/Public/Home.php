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
        // Get recent articles (30 most recent)
        $articles = Content::getPublishedArticles(30);

        // Get recent photobooks (30 most recent)
        $photobooks = Content::getPublishedPhotobooks(30);

        // Render homepage template
        $this->render('home/index', [
            'articles' => $articles,
            'photobooks' => $photobooks,
            'page_title' => 'Home'
        ]);
    }
}
