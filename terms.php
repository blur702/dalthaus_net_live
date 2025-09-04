<?php
/**
 * Terms of Service Page
 */

$page_title = "Terms of Service - Dalthaus Photography";
$page_description = "Terms of service for Dalthaus Photography website";
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
            <h1 class="page-title">Terms of Service</h1>
            
            <div class="last-updated">
                Last updated: <?php echo date("F j, Y"); ?>
            </div>

            <h2>Acceptance of Terms</h2>
            <p>By accessing and using the Dalthaus Photography website, you accept and agree to be bound by the terms and provision of this agreement.</p>

            <h2>Website Usage</h2>
            <p>You may use our website for lawful purposes only. You agree not to use the website:</p>
            <ul>
                <li>In any way that violates applicable laws or regulations</li>
                <li>To harm, abuse, harass, threaten, defame, or otherwise infringe upon the rights of others</li>
                <li>To upload, post, or transmit any content that is unlawful, harmful, or objectionable</li>
                <li>To interfere with or disrupt the website or servers connected to it</li>
            </ul>

            <h2>Photography and Intellectual Property</h2>
            <p>All photographs, images, text, graphics, and other content on this website are the exclusive property of Dalthaus Photography and are protected by copyright and other intellectual property laws.</p>
            
            <h3>Usage Rights</h3>
            <ul>
                <li>You may view and download content for personal, non-commercial use only</li>
                <li>Commercial use requires written permission</li>
                <li>You may not reproduce, distribute, modify, or create derivative works</li>
                <li>Attribution must be maintained when sharing is permitted</li>
            </ul>

            <h2>Photography Services</h2>
            <p>If you engage Dalthaus Photography for services:</p>
            <ul>
                <li>All arrangements must be confirmed in writing</li>
                <li>Payment terms will be specified in individual contracts</li>
                <li>Cancellation policies apply as specified in service agreements</li>
                <li>Image delivery timelines are estimates and may vary</li>
            </ul>

            <h2>Model and Subject Rights</h2>
            <p>All individuals appearing in photographs on this website have provided appropriate consent for their images to be used. If you believe your image has been used without proper authorization, please contact us immediately.</p>

            <h2>User Content</h2>
            <p>If you submit any content to our website (comments, contact forms, etc.):</p>
            <ul>
                <li>You retain ownership of your content</li>
                <li>You grant us license to use it for website operations</li>
                <li>You are responsible for ensuring content is lawful and appropriate</li>
                <li>We reserve the right to remove any content at our discretion</li>
            </ul>

            <h2>Privacy</h2>
            <p>Your privacy is important to us. Please review our <a href="/privacy.php" style="color: #3498db;">Privacy Policy</a> to understand how we collect, use, and protect your information.</p>

            <h2>Disclaimers</h2>
            <p>This website is provided on an "as is" basis. Dalthaus Photography makes no warranties, expressed or implied, and hereby disclaims all warranties, including without limitation, implied warranties of merchantability, fitness for a particular purpose, or non-infringement.</p>

            <h2>Limitation of Liability</h2>
            <p>Dalthaus Photography shall not be liable for any indirect, incidental, special, or consequential damages resulting from the use or inability to use this website or any content contained herein.</p>

            <h2>Governing Law</h2>
            <p>These terms shall be governed by and construed in accordance with applicable local laws, without regard to conflict of law provisions.</p>

            <h2>Changes to Terms</h2>
            <p>We reserve the right to modify these terms at any time. Changes will be effective immediately upon posting on this page. Your continued use of the website after changes constitutes acceptance of the new terms.</p>

            <h2>Contact Information</h2>
            <p>If you have any questions about these Terms of Service, please contact us:</p>
            <p><strong>Email:</strong> legal@dalthaus.net<br>
            <strong>Website:</strong> <a href="/contact" style="color: #3498db;">Contact Form</a></p>
        </div>
    </main>

    <footer class="footer">
        <div class="container">
            <p>&copy; <?php echo date("Y"); ?> Dalthaus Photography. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>