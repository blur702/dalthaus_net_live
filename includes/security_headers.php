<?php
/**
 * Security Headers Configuration
 * 
 * Sets comprehensive security headers to protect against various attacks.
 * Include this file at the beginning of index.php and admin entry points.
 * 
 * @package DalthausCMS
 * @since 1.0.0
 */
declare(strict_types=1);

// Prevent clickjacking attacks
header('X-Frame-Options: SAMEORIGIN');

// Prevent MIME type sniffing
header('X-Content-Type-Options: nosniff');

// Enable XSS protection in older browsers
header('X-XSS-Protection: 1; mode=block');

// Control referrer information
header('Referrer-Policy: strict-origin-when-cross-origin');

// Content Security Policy
// Allows self-hosted content, TinyMCE CDN, Google Fonts, and inline styles/scripts for TinyMCE
$cspHeader = "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' 'unsafe-eval' cdn.jsdelivr.net cdn.tiny.cloud; " .
    "style-src 'self' 'unsafe-inline' fonts.googleapis.com cdn.jsdelivr.net; " .
    "font-src 'self' fonts.gstatic.com; " .
    "img-src 'self' data: blob: https:; " .
    "connect-src 'self'; " .
    "frame-src 'self'; " .
    "object-src 'none'; " .
    "base-uri 'self'; " .
    "form-action 'self'; " .
    "upgrade-insecure-requests;";

header($cspHeader);

// Permissions Policy (formerly Feature Policy)
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// HSTS (HTTP Strict Transport Security) - only in production with HTTPS
if (defined('ENV') && ENV === 'production' && (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
}

// Prevent caching of sensitive pages (for admin areas)
if (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Cache-Control: post-check=0, pre-check=0', false);
    header('Pragma: no-cache');
    header('Expires: 0');
}