<?php

declare(strict_types=1);

namespace CMS\Controllers\Public;

use CMS\Controllers\BaseController;
use CMS\Models\Page;
use CMS\Utils\Database;
use CMS\Utils\Auth;

class Pages extends BaseController
{
    public function __construct(Database $db, Auth $auth, array $config)
    {
        parent::__construct($db, $auth, $config);
    }

    protected function initialize(): void
    {
        $this->view->layout('default');
    }

    public function show(string $alias): void
    {
        $page = Page::findByAlias($alias);
        
        if ($page === null) {
            http_response_code(404);
            $this->render('errors/404', ['page_title' => 'Page Not Found']);
            return;
        }

        $this->render('pages/show', [
            'page' => $page,
            'page_title' => $page->getAttribute('title'),
            'meta_description' => $page->getAttribute('meta_description'),
            'meta_keywords' => $page->getAttribute('meta_keywords')
        ]);
    }
}