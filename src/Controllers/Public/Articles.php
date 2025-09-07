<?php

declare(strict_types=1);

namespace CMS\Controllers\Public;

use CMS\Controllers\BaseController;
use CMS\Models\Content;

/**
 * Articles Controller
 * 
 * Handles article listings and individual article display.
 * 
 * @package CMS\Controllers\Public
 * @author  Kevin
 * @version 1.0.0
 */
class Articles extends BaseController
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
     * Display articles listing page
     * 
     * @return void
     */
    public function index(): void
    {
        $page = max(1, (int) $this->getParam('page', 1));
        $itemsPerPage = $this->config['app']['items_per_page'];
        $offset = ($page - 1) * $itemsPerPage;

        // Get published articles with pagination
        $articles = Content::getPublishedArticles($itemsPerPage, $offset);
        
        // Get total count for pagination
        $totalArticles = Content::count([
            'content_type' => Content::TYPE_ARTICLE,
            'status' => Content::STATUS_PUBLISHED
        ]);
        
        $totalPages = ceil($totalArticles / $itemsPerPage);

        // Render articles listing template
        $this->render('articles/index', [
            'articles' => $articles,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'total_articles' => $totalArticles,
            'page_title' => 'Articles'
        ]);
    }

    /**
     * Display individual article
     * 
     * @param string $alias Article URL alias
     * @return void
     */
    public function show(string $alias): void
    {
        // Find article by alias
        $article = Content::findByAlias($alias);
        
        if ($article === null || !$article->isArticle()) {
            http_response_code(404);
            $this->render('errors/404', [
                'page_title' => 'Article Not Found'
            ]);
            return;
        }

        // Get content pages (split by pagebreak)
        $contentPages = $article->getContentPages();
        $currentPage = max(1, min(count($contentPages), (int) $this->getParam('p', 1)));
        $currentContent = $contentPages[$currentPage - 1] ?? '';

        // Get author information
        $author = $article->getAuthor();

        // Render article template
        $this->render('articles/show', [
            'article' => $article,
            'content' => $currentContent,
            'current_page' => $currentPage,
            'total_pages' => count($contentPages),
            'author' => $author,
            'page_title' => $article->getAttribute('title'),
            'meta_description' => $article->getAttribute('meta_description'),
            'meta_keywords' => $article->getAttribute('meta_keywords')
        ]);
    }
}
