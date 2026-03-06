<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Content;
use CMS\Models\ActivityLog;
use CMS\Models\User;
use CMS\Models\Page;
use CMS\Utils\Database;
use CMS\Utils\Auth;
use Exception;

class Dashboard extends BaseController
{
    public function __construct(Database $db, Auth $auth, array $config)
    {
        parent::__construct($db, $auth, $config);
    }

    protected function initialize(): void
    {
        // requireAuth() is now handled by the router middleware
        $this->view->layout('admin');
    }

    public function index(): void
    {
        error_log("[DASHBOARD] === index() START ===");
        error_log("[DASHBOARD] Session ID: " . session_id());
        error_log("[DASHBOARD] Session data: " . json_encode($_SESSION ?? []));
        error_log("[DASHBOARD] Auth check result: " . ($this->auth->check() ? 'TRUE' : 'FALSE'));

        $stats = $this->getDashboardStats();
        $recentContent = $this->getRecentContent();
        $recentActivity = [];
        try {
            $recentActivity_raw = ActivityLog::getRecentActivity(10);
            $recentActivity = array_map(fn($item) => is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item, $recentActivity_raw);
        } catch (Exception $e) {
            error_log('Failed to get recent activity: ' . $e->getMessage());
        }

        $activityStats = ['today' => 0, 'week' => 0, 'month' => 0];
        try {
            $activityStats = [
                'today' => ActivityLog::getActivityStats('today'),
                'week' => ActivityLog::getActivityStats('week'),
                'month' => ActivityLog::getActivityStats('month')
            ];
        } catch (Exception $e) {
            error_log('Failed to get activity stats: ' . $e->getMessage());
        }

        $hour = (int)date('G');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

        $this->render('admin/dashboard/index', [
            'stats' => $stats,
            'recent_content' => $recentContent,
            'recent_activity' => $recentActivity,
            'activity_stats' => $activityStats,
            'greeting' => $greeting,
            'content_trends' => $this->getContentTrends(),
            'page_title' => 'Dashboard',
        ]);
    }

    private function getDashboardStats(): array
    {
        return [
            'total_articles' => Content::count(['content_type' => Content::TYPE_ARTICLE]),
            'published_articles' => Content::count(['content_type' => Content::TYPE_ARTICLE, 'status' => Content::STATUS_PUBLISHED]),
            'total_photobooks' => Content::count(['content_type' => Content::TYPE_PHOTOBOOK]),
            'published_photobooks' => Content::count(['content_type' => Content::TYPE_PHOTOBOOK, 'status' => Content::STATUS_PUBLISHED]),
            'total_users' => $this->db->count('users'),
            'total_pages' => $this->db->count('pages')
        ];
    }

    private function getRecentContent(): array
    {
        $content = Content::getForAdmin([], 10);
        return array_map(fn($item) => is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item, $content);
    }
    
    private function getContentTrends(): array
    {
        $dates = [];
        $articles = [];
        $photobooks = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = date('M j', strtotime("-$i days"));
            $articles[] = rand(0, 10);
            $photobooks[] = rand(0, 5);
        }
        return ['dates' => $dates, 'articles' => $articles, 'photobooks' => $photobooks];
    }
}