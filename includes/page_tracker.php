<?php
// Simple page tracker
if (!function_exists('trackPageView')) {
    function trackPageView($page) {
        // Simple page view tracking - can be enhanced later
        error_log("Page view: " . $page);
    }
}
