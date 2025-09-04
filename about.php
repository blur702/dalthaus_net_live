<?php
// About page for Dalthaus Photography
error_reporting(0); // Suppress errors for production

// Include files safely and initialize database
if (file_exists('includes/config.php')) {
    require_once 'includes/config.php';
}
if (file_exists('includes/database.php')) {
    require_once 'includes/database.php';
}
if (file_exists('includes/functions.php')) {
    require_once 'includes/functions.php';
} else if (file_exists('functions-fixed.php')) {
    require_once 'functions-fixed.php';
}

// Initialize database connection if not already done
if (!isset($pdo) && class_exists('Database')) {
    try {
        $pdo = Database::getInstance();
    } catch (Exception $e) {
        // Database connection failed, continue with defaults
        $pdo = null;
    }
}

// Set default values
$site_title = 'About - Dalthaus Photography';
$site_motto = 'Capturing moments, telling stories through light and shadow';

// Try to get from settings if available
if (function_exists('getSetting') && isset($pdo) && $pdo) {
    try {
        $title_from_db = getSetting('site_title', '');
        if ($title_from_db) {
            $site_title = 'About - ' . $title_from_db;
        }
        
        $motto_from_db = getSetting('site_motto', '');
        if ($motto_from_db) {
            $site_motto = $motto_from_db;
        }
    } catch (Exception $e) {
        // Error getting settings, use defaults
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Arimo:wght@400;700&family=Gelasio:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            font-family: 'Gelasio', serif;
            line-height: 1.6;
            color: #333;
            background: #fff;
        }
        
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
        
        .mobile-nav a:hover,
        .mobile-nav a.active {
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
        
        /* Main content */
        .main-content {
            flex: 1;
            max-width: 800px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        
        .page-title {
            font-family: 'Arimo', sans-serif;
            font-size: 2.2rem;
            font-weight: 700;
            color: #2c3e50;
            margin-bottom: 30px;
            text-align: center;
            border-bottom: 2px solid #3498db;
            padding-bottom: 15px;
        }
        
        .about-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #444;
        }
        
        .about-section {
            margin-bottom: 40px;
        }
        
        .about-section h2 {
            font-family: 'Arimo', sans-serif;
            font-size: 1.6rem;
            color: #2c3e50;
            margin-bottom: 20px;
            border-left: 4px solid #3498db;
            padding-left: 15px;
        }
        
        .about-section p {
            margin-bottom: 20px;
        }
        
        .profile-image {
            width: 100%;
            max-width: 400px;
            height: 300px;
            background: linear-gradient(135deg, #f0f0f0 0%, #e8e8e8 100%);
            border-radius: 12px;
            margin: 30px auto;
            border: 1px solid #ddd;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 1rem;
        }
        
        .skills-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        .skill-item {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .skill-item h3 {
            font-family: 'Arimo', sans-serif;
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .contact-cta {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            text-align: center;
            margin-top: 50px;
            border: 1px solid #e9ecef;
        }
        
        .contact-cta h2 {
            font-family: 'Arimo', sans-serif;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .contact-cta a {
            display: inline-block;
            background: #3498db;
            color: white;
            text-decoration: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 15px;
            transition: background 0.3s ease;
        }
        
        .contact-cta a:hover {
            background: #2980b9;
        }
        
        /* Footer */
        .footer {
            background: transparent;
            color: #7f8c8d;
            text-align: center;
            padding: 40px 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .footer-links {
            margin-top: 15px;
        }
        
        .footer-links a {
            color: #3498db;
            text-decoration: none;
            padding: 0 10px;
        }
        
        .footer-links a:hover {
            text-decoration: underline;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hamburger-menu {
                right: 20px;
                top: 20px;
            }
            
            .site-title {
                font-size: 2rem;
            }
            
            .page-title {
                font-size: 1.8rem;
            }
            
            .main-content {
                padding: 20px 15px;
            }
            
            .about-content {
                font-size: 1rem;
            }
            
            .skills-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Hamburger Menu -->
    <div class="hamburger-menu" id="hamburgerMenu">
        <span></span>
        <span></span>
        <span></span>
    </div>

    <!-- Header -->
    <header class="header">
        <h1 class="site-title">Dalthaus Photography</h1>
        <p class="site-slogan"><?php echo htmlspecialchars($site_motto); ?></p>
    </header>

    <!-- Mobile Navigation -->
    <nav class="mobile-nav" id="mobileNav">
        <a href="/">Home</a>
        <a href="/articles">Articles</a>
        <a href="/photobooks">Photobooks</a>
        <a href="/about" class="active">About</a>
        <a href="/contact">Contact</a>
    </nav>

    <!-- Navigation Overlay -->
    <div class="nav-overlay" id="navOverlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <h1 class="page-title">About Don Althaus</h1>
        
        <div class="about-content">
            <div class="profile-image">
                Portrait Photo
            </div>
            
            <div class="about-section">
                <h2>Photography Journey</h2>
                <p>With over two decades of experience in professional photography, I've dedicated my career to capturing the essence of life through light and shadow. My passion for photography began in the darkroom, where I learned the fundamentals of composition, exposure, and the magic of bringing images to life.</p>
                
                <p>From street photography in bustling urban landscapes to intimate portrait sessions, I believe every image tells a story. My work spans diverse genres including documentary photography, portraiture, automotive photography, and fine art compositions.</p>
            </div>
            
            <div class="about-section">
                <h2>Philosophy</h2>
                <p>Photography is more than just capturing moments—it's about preserving emotions, telling stories, and connecting with the human experience. I approach each project with curiosity and respect, whether documenting historical automotive legends or capturing the quiet dignity of everyday life.</p>
                
                <p>My technical expertise combines traditional photographic principles with modern digital techniques, ensuring each image meets the highest standards while maintaining authentic storytelling.</p>
            </div>
            
            <div class="about-section">
                <h2>Specializations</h2>
                <div class="skills-grid">
                    <div class="skill-item">
                        <h3>Automotive Photography</h3>
                        <p>Specialized in capturing classic and vintage automobiles, with extensive experience documenting racing history and automotive culture.</p>
                    </div>
                    
                    <div class="skill-item">
                        <h3>Portrait Photography</h3>
                        <p>Creating compelling portraits that reveal character and emotion through careful attention to lighting and composition.</p>
                    </div>
                    
                    <div class="skill-item">
                        <h3>Documentary Work</h3>
                        <p>Telling authentic stories through photojournalistic approaches, capturing moments of historical and cultural significance.</p>
                    </div>
                    
                    <div class="skill-item">
                        <h3>Fine Art Photography</h3>
                        <p>Exploring artistic expression through photography, with works featured in galleries and private collections.</p>
                    </div>
                </div>
            </div>
            
            <div class="about-section">
                <h2>Recognition & Experience</h2>
                <p>My work has been featured in various automotive publications and exhibited in regional galleries. I've had the privilege of documenting historic racing events and working with collectors to preserve automotive heritage through photography.</p>
                
                <p>Beyond commercial work, I'm committed to sharing knowledge through workshops and mentoring emerging photographers in both traditional and digital techniques.</p>
            </div>
        </div>
        
        <div class="contact-cta">
            <h2>Let's Work Together</h2>
            <p>Interested in discussing a photography project or commission? I'd love to hear about your vision and explore how we can bring it to life.</p>
            <a href="/contact">Get In Touch</a>
        </div>
    </main>

    <!-- Footer -->
    <footer class="footer">
        <p>&copy; <?php echo date('Y'); ?> Dalthaus Photography. All rights reserved.</p>
        <div class="footer-links">
            <a href="/privacy">Privacy Policy</a>
            <span>|</span>
            <a href="/terms">Terms of Service</a>
            <span>|</span>
            <a href="/contact">Contact</a>
        </div>
    </footer>

    <script>
        // Hamburger menu functionality
        document.addEventListener('DOMContentLoaded', function() {
            const hamburgerMenu = document.getElementById('hamburgerMenu');
            const mobileNav = document.getElementById('mobileNav');
            const navOverlay = document.getElementById('navOverlay');
            
            function toggleMenu() {
                hamburgerMenu.classList.toggle('active');
                mobileNav.classList.toggle('active');
                navOverlay.classList.toggle('active');
                
                if (mobileNav.classList.contains('active')) {
                    document.body.style.overflow = 'hidden';
                } else {
                    document.body.style.overflow = '';
                }
            }
            
            hamburgerMenu.addEventListener('click', toggleMenu);
            navOverlay.addEventListener('click', toggleMenu);
        });
    </script>
</body>
</html>