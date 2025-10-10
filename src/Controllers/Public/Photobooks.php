<?php

declare(strict_types=1);

namespace CMS\Controllers\Public;

use CMS\Controllers\BaseController;
use CMS\Models\Content;
use CMS\Utils\Database;
use CMS\Utils\Auth;

class Photobooks extends BaseController
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

        $photobooks = Content::getPublishedPhotobooks($itemsPerPage, $offset);
        $totalPhotobooks = Content::count(['content_type' => Content::TYPE_PHOTOBOOK, 'status' => Content::STATUS_PUBLISHED]);
        $totalPages = ceil($totalPhotobooks / $itemsPerPage);

        $this->render('photobooks/index', [
            'photobooks' => $photobooks,
            'current_page' => $page,
            'total_pages' => $totalPages,
            'page_title' => 'Photobooks'
        ]);
    }

    public function show(string $alias): void
    {
        $photobook = Content::findByAlias($alias);
        
        if ($photobook === null || !$photobook->isPhotobook()) {
            http_response_code(404);
            $this->render('errors/404', ['page_title' => 'Photobook Not Found']);
            return;
        }

        $this->render('photobooks/show', [
            'photobook' => $photobook,
            'author' => $photobook->getAuthor(),
            'can_edit' => $this->auth->check(),
            'page_title' => $photobook->getAttribute('title'),
            'meta_description' => $photobook->getAttribute('meta_description'),
            'meta_keywords' => $photobook->getAttribute('meta_keywords')
        ]);
    }
}