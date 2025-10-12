<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Utils\Database;
use CMS\Utils\Auth as AuthUtil;
use CMS\Utils\Security;

class Auth extends BaseController
{
    public function __construct(Database $db, AuthUtil $auth, array $config)
    {
        parent::__construct($db, $auth, $config);
    }

    protected function initialize(): void
    {
        $this->view->layout("auth");
    }

    public function handleAdminRoot(): void
    {
        if ($this->auth->check()) {
            $this->redirect("/admin/dashboard");
        } else {
            $this->redirect("/admin/login");
        }
    }

    public function login(): void
    {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        $this->render("admin/auth/login", [
            "page_title" => "Admin Login"
        ]);
    }

    public function authenticate(): void
    {
        if (!$this->request->isPost()) {
            $this->redirect("/admin/login");
            return;
        }

        if (!$this->auth->validateCsrfToken($this->request->post('_token', ''))) {
            $this->setFlash("error", "Invalid security token. Please try again.");
            $this->redirect("/admin/login");
            return;
        }

        $username = $this->request->post("username", "");
        $password = $this->request->post("password", "");
        $rememberMe = (bool)$this->request->post("remember_me", false);

        if (empty($username) || empty($password)) {
            $this->setFlash("error", "Username and password are required.");
            $this->redirect("/admin/login");
            return;
        }

        if ($this->auth->attempt($username, $password, $rememberMe)) {
            // Use JavaScript redirect to allow session cookie to be set properly
            // This fixes SameSite=Lax cookie issues after session_regenerate_id()
            echo '<html><head><meta http-equiv="refresh" content="0;url=/admin/dashboard"></head><body>Redirecting...</body></html>';
            exit;
        } else {
            $this->setFlash("error", "Invalid username or password.");
            $this->redirect("/admin/login");
        }
    }

    public function logout(): void
    {
        $this->auth->logout();
        $this->setFlash("success", "You have been logged out successfully.");
        $this->redirect("/admin/login");
    }
}