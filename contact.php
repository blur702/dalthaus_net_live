<?php
// Contact page for Dalthaus Photography
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
$site_title = 'Contact - Dalthaus Photography';
$site_motto = 'Capturing moments, telling stories through light and shadow';

// Try to get from settings if available
if (function_exists('getSetting') && isset($pdo) && $pdo) {
    try {
        $title_from_db = getSetting('site_title', '');
        if ($title_from_db) {
            $site_title = 'Contact - ' . $title_from_db;
        }
        
        $motto_from_db = getSetting('site_motto', '');
        if ($motto_from_db) {
            $site_motto = $motto_from_db;
        }
    } catch (Exception $e) {
        // Error getting settings, use defaults
    }
}

// Handle form submission
$form_submitted = false;
$form_success = false;
$form_errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_submitted = true;
    
    // Validate form data
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name)) {
        $form_errors[] = 'Name is required.';
    }
    
    if (empty($email)) {
        $form_errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $form_errors[] = 'Please enter a valid email address.';
    }
    
    if (empty($subject)) {
        $form_errors[] = 'Subject is required.';
    }
    
    if (empty($message)) {
        $form_errors[] = 'Message is required.';
    }
    
    // If no errors, process the form
    if (empty($form_errors)) {
        // Basic honeypot check
        $honeypot = trim($_POST['website'] ?? '');
        if (empty($honeypot)) {
            // Here you would typically send an email or store in database
            // For now, we'll just mark as successful
            $form_success = true;
            
            // You can add email functionality here
            /*
            $to = 'don@dalthaus.net';
            $email_subject = 'Contact Form: ' . $subject;
            $email_body = "Name: $name\nEmail: $email\nSubject: $subject\n\nMessage:\n$message";
            $headers = "From: $email\r\nReply-To: $email\r\n";
            mail($to, $email_subject, $email_body, $headers);
            */
        }
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
        
        .contact-intro {
            text-align: center;
            font-size: 1.1rem;
            color: #555;
            margin-bottom: 40px;
        }
        
        .contact-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            margin-top: 30px;
        }
        
        /* Contact Form */
        .contact-form {
            background: #f8f9fa;
            padding: 30px;
            border-radius: 12px;
            border: 1px solid #e9ecef;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-family: 'Arimo', sans-serif;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-family: inherit;
            font-size: 16px;
            transition: border-color 0.3s ease, box-shadow 0.3s ease;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-group textarea {
            height: 120px;
            resize: vertical;
        }
        
        .honeypot {
            position: absolute;
            left: -9999px;
            opacity: 0;
        }
        
        .form-submit {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
            font-family: 'Arimo', sans-serif;
        }
        
        .form-submit:hover {
            background: #2980b9;
        }
        
        .form-submit:disabled {
            background: #bdc3c7;
            cursor: not-allowed;
        }
        
        /* Contact Information */
        .contact-info {
            padding: 30px;
        }
        
        .contact-info h3 {
            font-family: 'Arimo', sans-serif;
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 1.4rem;
        }
        
        .info-item {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #3498db;
        }
        
        .info-item h4 {
            font-family: 'Arimo', sans-serif;
            color: #2c3e50;
            margin-bottom: 8px;
        }
        
        .info-item p {
            color: #666;
            margin: 0;
        }
        
        .info-item a {
            color: #3498db;
            text-decoration: none;
        }
        
        .info-item a:hover {
            text-decoration: underline;
        }
        
        /* Messages */
        .message {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .error-list {
            margin: 0;
            padding-left: 20px;
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
            .contact-layout {
                grid-template-columns: 1fr;
                gap: 30px;
            }
            
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
            
            .contact-form,
            .contact-info {
                padding: 20px;
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
        <a href="/about">About</a>
        <a href="/contact" class="active">Contact</a>
    </nav>

    <!-- Navigation Overlay -->
    <div class="nav-overlay" id="navOverlay"></div>

    <!-- Main Content -->
    <main class="main-content">
        <h1 class="page-title">Get In Touch</h1>
        
        <div class="contact-intro">
            <p>I'd love to hear about your photography needs. Whether you're looking for portrait sessions, event photography, or custom photobook projects, let's discuss how we can bring your vision to life.</p>
        </div>
        
        <?php if ($form_submitted): ?>
            <?php if ($form_success): ?>
                <div class="message success">
                    <p><strong>Thank you for your message!</strong> I'll get back to you within 24 hours.</p>
                </div>
            <?php elseif (!empty($form_errors)): ?>
                <div class="message error">
                    <p><strong>Please correct the following errors:</strong></p>
                    <ul class="error-list">
                        <?php foreach ($form_errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="contact-layout">
            <!-- Contact Form -->
            <div class="contact-form">
                <form method="POST" action="/contact">
                    <div class="form-group">
                        <label for="name">Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email *</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a topic...</option>
                            <option value="Portrait Photography" <?php echo ($_POST['subject'] ?? '') === 'Portrait Photography' ? 'selected' : ''; ?>>Portrait Photography</option>
                            <option value="Event Photography" <?php echo ($_POST['subject'] ?? '') === 'Event Photography' ? 'selected' : ''; ?>>Event Photography</option>
                            <option value="Automotive Photography" <?php echo ($_POST['subject'] ?? '') === 'Automotive Photography' ? 'selected' : ''; ?>>Automotive Photography</option>
                            <option value="Custom Photobook" <?php echo ($_POST['subject'] ?? '') === 'Custom Photobook' ? 'selected' : ''; ?>>Custom Photobook</option>
                            <option value="Fine Art Prints" <?php echo ($_POST['subject'] ?? '') === 'Fine Art Prints' ? 'selected' : ''; ?>>Fine Art Prints</option>
                            <option value="Workshop/Teaching" <?php echo ($_POST['subject'] ?? '') === 'Workshop/Teaching' ? 'selected' : ''; ?>>Workshop/Teaching</option>
                            <option value="General Inquiry" <?php echo ($_POST['subject'] ?? '') === 'General Inquiry' ? 'selected' : ''; ?>>General Inquiry</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="message">Message *</label>
                        <textarea id="message" name="message" placeholder="Tell me about your project, timeline, and any specific requirements..." required><?php echo htmlspecialchars($_POST['message'] ?? ''); ?></textarea>
                    </div>
                    
                    <!-- Honeypot field for spam protection -->
                    <div class="honeypot">
                        <label for="website">Website</label>
                        <input type="text" id="website" name="website">
                    </div>
                    
                    <button type="submit" class="form-submit">Send Message</button>
                </form>
            </div>
            
            <!-- Contact Information -->
            <div class="contact-info">
                <h3>Contact Information</h3>
                
                <div class="info-item">
                    <h4>Email</h4>
                    <p><a href="mailto:don@dalthaus.net">don@dalthaus.net</a></p>
                </div>
                
                <div class="info-item">
                    <h4>Response Time</h4>
                    <p>I typically respond to inquiries within 24 hours during business days.</p>
                </div>
                
                <div class="info-item">
                    <h4>Services</h4>
                    <p>Portrait photography, event coverage, automotive photography, custom photobooks, fine art prints, and photography workshops.</p>
                </div>
                
                <div class="info-item">
                    <h4>Service Areas</h4>
                    <p>Local and regional projects welcome. Travel arrangements can be discussed for special projects.</p>
                </div>
                
                <div class="info-item">
                    <h4>Project Planning</h4>
                    <p>For the best results, I recommend scheduling consultation calls for projects over $500 to discuss your vision and requirements in detail.</p>
                </div>
            </div>
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