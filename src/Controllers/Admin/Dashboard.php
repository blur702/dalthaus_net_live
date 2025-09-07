<?php

declare(strict_types=1);

namespace CMS\Controllers\Admin;

use CMS\Controllers\BaseController;
use CMS\Models\Content;
use CMS\Models\ActivityLog;
use CMS\Models\User;
use CMS\Models\Page;
use CMS\Utils\Auth;

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
        // Get dashboard statistics
        $stats = $this->getDashboardStats();
        
        // Get recent content
        $recentContent = $this->getRecentContent();
        
        // Get recent activity
        $recentActivity_raw = ActivityLog::getRecentActivity(10);
        $recentActivity = array_map(function($item) {
            return is_object($item) && method_exists($item, 'toArray') ? $item->toArray() : $item;
        }, $recentActivity_raw);
        
        // Get activity stats for different periods
        $activityStats = [
            'today' => ActivityLog::getActivityStats('today'),
            'week' => ActivityLog::getActivityStats('week'),
            'month' => ActivityLog::getActivityStats('month')
        ];
        
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
        
        // Active users this month (users who have activity logs)
        $stats['active_users_month'] = $this->db->fetchColumn(
            "SELECT COUNT(DISTINCT user_id) FROM activity_logs 
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"
        ) ?: 0;
        
        // Total activities today
        $stats['activities_today'] = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM activity_logs WHERE DATE(created_at) = CURDATE()"
        ) ?: 0;
        
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
