<?php
declare(strict_types=1);
namespace CMS\Controllers\Admin;
use CMS\Controllers\BaseController;
use CMS\Utils\Auth as AuthUtil;

class Auth extends BaseController
{
    private AuthUtil $auth;
    
    protected function initialize(): void
    {
        $this->auth = new AuthUtil($this->db, $this->config["security"]);
        $this->view->layout("auth");
    }
    
    public function login(): void
    {
        // Never redirect - always show login form
        $this->render("admin/auth/login", [
            "csrf_token" => $this->auth->generateCsrfToken(),
            "flash" => $this->getFlash(),
            "page_title" => "Admin Login"
        ]);
    }
    
    public function authenticate(): void
    {
        if (!$this->isPost()) {
            $this->redirect("/admin/login");
            return;
        }
        
        $username = $this->getParam("username", "", "post");
        $password = $this->getParam("password", "", "post");
        
        if ($this->auth->attempt($username, $password)) {
            $this->redirect("/admin/dashboard");
        } else {
            $this->setFlash("error", "Invalid credentials.");
            $this->redirect("/admin/login");
        }
    }
    
    public function logout(): void
    {
        $this->auth->logout();
        $this->redirect("/admin/login");
    }
}