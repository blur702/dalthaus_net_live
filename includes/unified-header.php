<?php
/**
 * Unified Header Component
 * Based on the homepage header - to be used across all public pages
 */
?>
<!-- Hamburger Menu (absolute positioned) -->
<div class="hamburger-menu" id="hamburgerMenu">
    <span></span>
    <span></span>
    <span></span>
</div>

<!-- Header -->
<header class="header">
    <h1 class="site-title">Dalthaus Photography</h1>
    <p class="site-slogan">Capturing moments, telling stories through light and shadow</p>
</header>

<!-- Mobile Navigation -->
<nav class="mobile-nav" id="mobileNav">
    <a href="/">Home</a>
    <a href="/articles">Articles</a>
    <a href="/photobooks">Photobooks</a>
    <a href="/about">About</a>
    <a href="/contact">Contact</a>
</nav>

<!-- Navigation Overlay -->
<div class="nav-overlay" id="navOverlay"></div>

<style>
    /* Header Styles */
    .header {
        padding: 40px 20px;
        text-align: center;
        border-bottom: 1px solid #e0e0e0;
        position: relative;
    }
    
    .site-title {
        font-family: 'Arimo', sans-serif;
        font-size: 2.5rem;
        font-weight: 700;
        color: #2c3e50;
        margin-bottom: 10px;
    }
    
    .site-slogan {
        font-size: 1.1rem;
        color: #7f8c8d;
        font-style: italic;
    }
    
    /* Hamburger Menu */
    .hamburger-menu {
        position: absolute;
        top: 30px;
        right: 30px;
        z-index: 1000;
        cursor: pointer;
        width: 30px;
        height: 25px;
    }
    
    .hamburger-menu span {
        display: block;
        width: 100%;
        height: 3px;
        background-color: #2c3e50;
        margin: 5px 0;
        transition: all 0.3s ease;
    }
    
    .hamburger-menu.active span:nth-child(1) {
        transform: rotate(45deg) translate(5px, 5px);
    }
    
    .hamburger-menu.active span:nth-child(2) {
        opacity: 0;
    }
    
    .hamburger-menu.active span:nth-child(3) {
        transform: rotate(-45deg) translate(7px, -6px);
    }
    
    /* Mobile Navigation */
    .mobile-nav {
        position: fixed;
        top: 0;
        right: -300px;
        width: 280px;
        height: 100vh;
        background: white;
        box-shadow: -2px 0 10px rgba(0,0,0,0.1);
        transition: right 0.3s ease;
        z-index: 999;
        padding: 80px 20px 20px;
        overflow-y: auto;
    }
    
    .mobile-nav.active {
        right: 0;
    }
    
    .mobile-nav a {
        display: block;
        padding: 15px 10px;
        color: #2c3e50;
        text-decoration: none;
        border-bottom: 1px solid #eee;
        font-size: 16px;
        transition: background 0.2s ease;
    }
    
    .mobile-nav a:hover {
        background: #f5f5f5;
        padding-left: 20px;
    }
    
    .nav-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.5);
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
        z-index: 998;
    }
    
    .nav-overlay.active {
        opacity: 1;
        visibility: visible;
    }
    
    /* Responsive */
    @media (max-width: 968px) {
        .site-title {
            font-size: 2rem;
        }
    }
    
    @media (max-width: 600px) {
        .site-title {
            font-size: 1.5rem;
        }
        
        .site-slogan {
            font-size: 0.9rem;
        }
        
        .hamburger-menu {
            top: 20px;
            right: 20px;
        }
    }
</style>

<script>
    // Hamburger Menu JavaScript
    (function() {
        function initHeaderMenu() {
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            const mobileNav = document.getElementById('mobileNav');
            const navOverlay = document.getElementById('navOverlay');
            
            if (!hamburgerMenu || !mobileNav || !navOverlay) return;

            function toggleMenu() {
                hamburgerMenu.classList.toggle('active');
                mobileNav.classList.toggle('active');
                navOverlay.classList.toggle('active');
                
                // Prevent body scroll when menu is open
                if (mobileNav.classList.contains('active')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }

            // Remove any existing listeners first
            const newHamburger = hamburgerMenu.cloneNode(true);
            hamburgerMenu.parentNode.replaceChild(newHamburger, hamburgerMenu);
            
            const newOverlay = navOverlay.cloneNode(true);
            navOverlay.parentNode.replaceChild(newOverlay, navOverlay);

            // Add event listeners
            document.getElementById('hamburgerMenu').addEventListener('click', toggleMenu);
            document.getElementById('navOverlay').addEventListener('click', toggleMenu);

            // Close menu on escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape' && document.getElementById('mobileNav').classList.contains('active')) {
                    toggleMenu();
                }
            });
        }
        
        // Initialize when DOM is ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initHeaderMenu);
        } else {
            initHeaderMenu();
        }
    })();
</script>