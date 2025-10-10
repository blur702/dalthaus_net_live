<?php

declare(strict_types=1);

namespace CMS\Controllers\Public;

use CMS\Controllers\BaseController;
use CMS\Models\Content;
use CMS\Utils\Database;
use CMS\Utils\Auth;

class Home extends BaseController
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
        $articles = Content::getPublishedArticles(30);
        $photobooks = Content::getPublishedPhotobooks(30);

        $this->render('home/index', [
            'articles' => $articles,
            'photobooks' => $photobooks,
            'page_title' => 'Home'
        ]);
    }
}