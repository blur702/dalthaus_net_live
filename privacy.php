<?php
/**
 * Privacy Policy Page
 */

$page_title = "Privacy Policy - Dalthaus Photography";
$page_description = "Privacy policy for Dalthaus Photography website";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($page_description); ?>">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.7;
            color: #333;
            background: #f8f9fa;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .header {
            background: white;
            padding: 20px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 40px;
        }
        
        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: #2c3e50;
            text-decoration: none;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            gap: 30px;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #555;
            font-weight: 500;
            transition: color 0.3s ease;
        }
        
        .nav-links a:hover {
            color: #3498db;
        }
        
        .content {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 40px;
        }
        
        .page-title {
            font-size: 42px;
            color: #2c3e50;
            margin-bottom: 30px;
            line-height: 1.3;
        }
        
        .content h2 {
            color: #2c3e50;
            margin: 30px 0 15px 0;
            font-size: 24px;
        }
        
        .content h3 {
            color: #34495e;
            margin: 25px 0 12px 0;
            font-size: 20px;
        }
        
        .content p {
            margin-bottom: 20px;
            font-size: 16px;
        }
        
        .content ul {
            margin: 20px 0;
            padding-left: 30px;
        }
        
        .content li {
            margin-bottom: 8px;
        }
        
        .last-updated {
            color: #666;
            font-style: italic;
            margin-bottom: 30px;
            font-size: 14px;
        }
        
        .footer {
            background: #2c3e50;
            color: white;
            text-align: center;
            padding: 40px 0;
            margin-top: 60px;
        }
        
        @media (max-width: 768px) {
            .nav {
                flex-direction: column;
                gap: 20px;
            }
            
            .nav-links {
                gap: 20px;
            }
            
            .page-title {
                font-size: 32px;
            }
            
            .content {
                padding: 25px;
            }
            
            .container {
                padding: 0 15px;
            }
        }
    </style>
</head>
<body>
    <header class="header">
        <nav class="nav">
            <a href="/" class="logo">DALTHAUS</a>
            <ul class="nav-links">
                <li><a href="/">Home</a></li>
                <li><a href="/articles">Articles</a></li>
                <li><a href="/photobooks">Photobooks</a></li>
                <li><a href="/about">About</a></li>
                <li><a href="/contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <div class="content">
            <h1 class="page-title">Privacy Policy</h1>
            
            <div class="last-updated">
                Last updated: <?php echo date('F j, Y'); ?>
            </div>

            <h2>Information We Collect</h2>
            <p>At Dalthaus Photography, we are committed to protecting your privacy. This policy outlines how we collect, use, and protect your personal information when you visit our website.</p>

            <h3>Information You Provide</h3>
            <ul>
                <li>Contact information when you reach out through our contact form</li>
                <li>Email address if you subscribe to our newsletter</li>
                <li>Comments or feedback you provide</li>
            </ul>

            <h3>Information We Automatically Collect</h3>
            <ul>
                <li>Browser type and version</li>
                <li>Pages visited and time spent on our site</li>
                <li>IP address (anonymized)</li>
                <li>Device information for responsive design purposes</li>
            </ul>

            <h2>How We Use Your Information</h2>
            <p>We use the information we collect to:</p>
            <ul>
                <li>Respond to your inquiries and provide customer service</li>
                <li>Send you updates about our work (only if you subscribe)</li>
                <li>Improve our website and user experience</li>
                <li>Analyze website usage and performance</li>
            </ul>

            <h2>Information Sharing</h2>
            <p>We do not sell, trade, or share your personal information with third parties, except:</p>
            <ul>
                <li>With your explicit consent</li>
                <li>To comply with legal requirements</li>
                <li>To protect our rights and safety</li>
            </ul>

            <h2>Cookies and Tracking</h2>
            <p>Our website uses minimal cookies for essential functionality:</p>
            <ul>
                <li>Session cookies for form functionality</li>
                <li>Analytics cookies to understand site usage (anonymized)</li>
            </ul>
            <p>You can disable cookies in your browser settings, though some site features may not work properly.</p>

            <h2>Data Security</h2>
            <p>We implement appropriate security measures to protect your personal information against unauthorized access, alteration, disclosure, or destruction. However, no method of transmission over the internet is 100% secure.</p>

            <h2>Your Rights</h2>
            <p>You have the right to:</p>
            <ul>
                <li>Access the personal information we have about you</li>
                <li>Request correction of inaccurate information</li>
                <li>Request deletion of your personal information</li>
                <li>Opt out of communications at any time</li>
            </ul>

            <h2>Photography and Model Rights</h2>
            <p>All photographs displayed on this website are the property of Dalthaus Photography. Models and subjects featured have provided appropriate consent for their images to be used.</p>

            <h2>Contact Us</h2>
            <p>If you have any questions about this privacy policy or how we handle your information, please contact us at:</p>
            <p><strong>Email:</strong> privacy@dalthaus.net<br>
            <strong>Website:</strong> <a href="/contact" style="color: #3498db;">Contact Form</a></p>

            <h2>Changes to This Policy</h2>
            <p>We may update this privacy policy from time to time. We will notify you of any changes by posting the new policy on this page with an updated "last modified" date.</p>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> Dalthaus Photography. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>