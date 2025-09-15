<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Content;
use CMS\Models\ActivityLog;
use CMS\Models\User;
use CMS\Models\Page;
use CMS\Utils\Auth;
use Exception;

/**
 * Admin Dashboard Controller
 * 
 * Handles the main admin dashboard with overview statistics.
 * 
 * @package CMS\Controllers\Admin
 * @author  Kevin
 * @version 1.0.0
 */
class Dashboard extends BaseController
{
    /**
     * Initialize controller
     * 
     * @return void
     */
    protected function initialize(): void
    {
        // DEBUG: Log authentication check
        error_log("Dashboard::initialize() - Session data: " . print_r($_SESSION, true));
        error_log("Dashboard::initialize() - isAuthenticated(): " . ($this->isAuthenticated() ? 'true' : 'false'));
        
        // TEMPORARY DEBUG: Show auth status instead of redirecting
        if (isset($_GET['debug_auth'])) {
            echo "<h1>Dashboard Authentication Debug</h1>";
            echo "<p>isAuthenticated(): " . ($this->isAuthenticated() ? 'YES' : 'NO') . "</p>";
            echo "<p>Session data:</p><pre>";
            print_r($_SESSION);
            echo "</pre>";
            
            if (!$this->isAuthenticated()) {
                echo "<p style='color: red;'>requireAuth() would redirect to login!</p>";
                exit;
            } else {
                echo "<p style='color: green;'>Authentication passed, would continue to dashboard...</p>";
                echo "<p><a href='/admin/dashboard'>Try without debug</a></p>";
                exit;
            }
        }
        
        // Require authentication for all admin actions
        $this->requireAuth();
        
        // Set admin layout
        $this->view->layout('admin');
    }

    /**
     * Display admin dashboard
     * 
     * @return void
     */
    public function index(): void
    {
        // DEBUG: Log that we reached the index method
        error_log("Dashboard::index() - Successfully reached dashboard index method");
        
        // TEMPORARY: Show debug info if requested
        if (isset($_GET['debug_index'])) {
            echo "<h1>Dashboard Index Debug</h1>";
            echo "<p>✓ Authentication passed</p>";
            echo "<p>✓ Reached index() method</p>";
            echo "<p>About to load dashboard data and render view...</p>";
            echo "<p><a href='/admin/dashboard'>Try normal dashboard</a></p>";
            return;
        }
        
        // TEMPORARY: Test minimal render bypass
        if (isset($_GET['debug_minimal'])) {
            echo "<h1>Minimal Dashboard Test</h1>";
            echo "<p>✓ Authentication passed</p>";
            echo "<p>✓ Reached index() method</p>";
            echo "<p>✓ Bypassing all data loading</p>";
            echo "<p>Now testing minimal render...</p>";
            
            $this->render('admin/dashboard/index', [
                'stats' => [],
                'recent_content' => [],
                'recent_activity' => [],
                'activity_stats' => [],
                'greeting' => 'Hello',
                'content_trends' => ['dates' => [], 'articles' => [], 'photobooks' => []],
                'page_title' => 'Dashboard Test',
                'system_health' => [],
                'draft_reminders' => [],
                'most_viewed' => [],
            ]);
            return;
        }
        
        // DEBUG: Check each data loading step
        error_log("Dashboard::index() - Step 1: About to get dashboard stats");
        try {
            // Get dashboard statistics
            $stats = $this->getDashboardStats();
            error_log("Dashboard::index() - Step 1: Dashboard stats loaded successfully");
        } catch (Exception $e) {
            error_log("Dashboard::index() - Step 1 FAILED: " . $e->getMessage());
            throw $e;
        }
        
        error_log("Dashboard::index() - Step 2: About to get recent content");
        try {
            // Get recent content
            $recentContent = $this->getRecentContent();
            error_log("Dashboard::index() - Step 2: Recent content loaded successfully");
        } catch (Exception $e) {
            error_log("Dashboard::index() - Step 2 FAILED: " . $e->getMessage());
            throw $e;
        }
        
        error_log("Dashboard::index() - Step 3: About to get recent activity");
        try {
            // Get recent activity (safe fallback if table doesn't exist)
            $recentActivity_raw = ActivityLog::getRecentActivity(10);
            $recentActivity = array_map(function($item) {
                return is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item;
            }, $recentActivity_raw);
            error_log("Dashboard::index() - Step 3: Recent activity loaded successfully");
        } catch (Exception $e) {
            error_log("Dashboard::index() - Step 3 FAILED (using fallback): " . $e->getMessage());
            $recentActivity = []; // Fallback to empty array
        }
        
        error_log("Dashboard::index() - Step 4: About to get activity stats");
        try {
            // Get activity stats for different periods (safe fallback if table doesn't exist)
            $activityStats = [
                'today' => ActivityLog::getActivityStats('today'),
                'week' => ActivityLog::getActivityStats('week'),
                'month' => ActivityLog::getActivityStats('month')
            ];
            error_log("Dashboard::index() - Step 4: Activity stats loaded successfully");
        } catch (Exception $e) {
            error_log("Dashboard::index() - Step 4 FAILED (using fallback): " . $e->getMessage());
            $activityStats = [
                'today' => 0,
                'week' => 0, 
                'month' => 0
            ]; // Fallback to zero stats
        }
        
        // Get time-based greeting
        $hour = (int)date('G');
        if ($hour < 12) {
            $greeting = 'Good morning';
        } elseif ($hour < 18) {
            $greeting = 'Good afternoon';
        } else {
            $greeting = 'Good evening';
        }

        // Dummy data for missing variables
        $system_health = [
            'database' => ['status' => 'healthy', 'message' => 'Connected'],
            'cache' => ['status' => 'healthy', 'message' => 'OK'],
            'uploads' => ['status' => 'healthy', 'message' => 'Writable'],
            'cron' => ['status' => 'warning', 'message' => 'Last run 2h ago'],
            'security' => ['status' => 'healthy', 'message' => 'Secure'],
        ];
        $draft_reminders_raw = Content::all(['status' => Content::STATUS_DRAFT], 'updated_at DESC', 5);
        $draft_reminders = array_map(function($item) {
            return is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item;
        }, $draft_reminders_raw);
        $most_viewed = []; // Placeholder

        // DEBUG: Log before rendering
        error_log("Dashboard::index() - About to render view");
        
        // TEMPORARY: Debug render
        if (isset($_GET['debug_render'])) {
            echo "<h1>Dashboard Render Debug</h1>";
            echo "<p>✓ All data prepared successfully</p>";
            echo "<p>✓ About to call render('admin/dashboard/index', ...)</p>";
            echo "<p>If this shows but normal dashboard doesn't work, the issue is in view rendering</p>";
            echo "<p><a href='/admin/dashboard'>Try normal dashboard</a></p>";
            return;
        }

        $this->render('admin/dashboard/index', [
            'stats' => $stats,
            'recent_content' => $recentContent,
            'recent_activity' => $recentActivity,
            'activity_stats' => $activityStats,
            'greeting' => $greeting,
            'content_trends' => $this->getContentTrends(),
            'page_title' => 'Dashboard',
            'system_health' => $system_health,
            'draft_reminders' => $draft_reminders,
            'most_viewed' => $most_viewed,
        ]);
    }

    /**
     * Get dashboard statistics
     * 
     * @return array<string, mixed>
     */
    private function getDashboardStats(): array
    {
        $baseStats = [
            'total_articles' => Content::count(['content_type' => Content::TYPE_ARTICLE]),
            'published_articles' => Content::count([
                'content_type' => Content::TYPE_ARTICLE,
                'status' => Content::STATUS_PUBLISHED
            ]),
            'draft_articles' => Content::count([
                'content_type' => Content::TYPE_ARTICLE,
                'status' => Content::STATUS_DRAFT
            ]),
            'total_photobooks' => Content::count(['content_type' => Content::TYPE_PHOTOBOOK]),
            'published_photobooks' => Content::count([
                'content_type' => Content::TYPE_PHOTOBOOK,
                'status' => Content::STATUS_PUBLISHED
            ]),
            'draft_photobooks' => Content::count([
                'content_type' => Content::TYPE_PHOTOBOOK,
                'status' => Content::STATUS_DRAFT
            ]),
            'total_users' => $this->db->count('users'),
            'total_pages' => $this->db->count('pages')
        ];

        // Add time-based statistics
        $timeStats = $this->getTimeBasedStats();
        
        return array_merge($baseStats, $timeStats);
    }

    /**
     * Get time-based statistics
     * 
     * @return array<string, mixed>
     */
    private function getTimeBasedStats(): array
    {
        $stats = [];
        
        // Content created today
        $stats['content_today'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM content WHERE DATE(created_at) = CURDATE()"
        ) ?: 0;
        
        // Content created this week
        $stats['content_week'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM content WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        ) ?: 0;
        
        // Content created this month
        $stats['content_month'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM content WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        ) ?: 0;
        
        // Published content this week
        $stats['published_week'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM content 
             WHERE status = 'published' 
               AND published_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        ) ?: 0;
        
        // Active users this month (users who have activity logs) - safe fallback
        try {
            $stats['active_users_month'] = $this->db->fetchColumn(
                "SELECT COUNT(DISTINCT user_id) FROM activity_logs 
                 WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
            ) ?: 0;
        } catch (Exception $e) {
            $stats['active_users_month'] = 0; // Fallback if activity_logs table doesn't exist
        }
        
        // Total activities today - safe fallback
        try {
            $stats['activities_today'] = $this->db->fetchColumn(
                "SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()"
            ) ?: 0;
        } catch (Exception $e) {
            $stats['activities_today'] = 0; // Fallback if activity_logs table doesn't exist
        }
        
        return $stats;
    }

    /**
     * Get recent content for dashboard
     * 
     * @return array<mixed>
     */
    private function getRecentContent(): array
    {
        $content = Content::getForAdmin([], 10);
        // Convert Content objects to arrays for view
        return array_map(function($item) {
            return is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item;
        }, $content);
    }
    
    /**
     * Get content trends for dashboard
     * 
     * @return array<string, mixed>
     */
    private function getContentTrends(): array
    {
        // Generate sample trend data for last 7 days
        $dates = [];
        $articles = [];
        $photobooks = [];
        
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = date('M j', strtotime("-$i days"));
            $articles[] = rand(0, 10);
            $photobooks[] = rand(0, 5);
        }
        
        return [
            'dates' => $dates,
            'articles' => $articles,
            'photobooks' => $photobooks
        ];
    }
}
