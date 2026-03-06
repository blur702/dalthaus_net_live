<?php

declare(strict_types=1);

namespace CMS\Controllers\Public;

use CMS\Controllers\BaseController;
use CMS\Models\Content;
use CMS\Utils\Database;
use CMS\Utils\Auth;

class Articles extends BaseController
{
    public function __construct(Database $db, Auth $auth, array $config)
    {
        parent::__construct($db, $auth, $config);
    }

    protected function initialize(): void
    {
        $this->view->layout('default');
    }

    public function index(): void
    {
        $page = $this->request->get('page', 1, 'int');
        $itemsPerPage = $this->config['app']['items_per_page'];
        $offset = ($page - 1) * $itemsPerPage;

        $articles = Content::getPublishedArticles($itemsPerPage, $offset);
        $totalArticles = Content::count(['content_type' => Content::TYPE_ARTICLE, 'status' => Content::STATUS_PUBLISHED]);
        $totalPages = ceil($totalArticles / $itemsPerPage);

        $this->render('articles/index', [
            'articles' => $articles,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'page_title' => 'Articles'
        ]);
    }

    public function show(string $alias): void
    {
        // If user is authenticated (admin), allow viewing drafts
        if ($this->auth->check()) {
            $article = Content::findByUrlAlias($alias);
        } else {
            $article = Content::findByAlias($alias);
        }

        if ($article === null || !$article->isArticle()) {
            http_response_code(404);
            $this->render('errors/404', ['page_title' => 'Article Not Found']);
            return;
        }

        // Show draft indicator for authenticated users viewing unpublished content
        $isDraft = $article->getAttribute('status') !== Content::STATUS_PUBLISHED;

        $this->render('articles/show', [
            'article' => $article,
            'content' => $article->getAttribute('body'),
            'author' => $article->getAuthor(),
            'can_edit' => $this->auth->check(),
            'is_draft' => $isDraft,
            'page_title' => $article->getAttribute('title'),
            'meta_description' => $article->getAttribute('meta_description'),
            'meta_keywords' => $article->getAttribute('meta_keywords')
        ]);
    }
}