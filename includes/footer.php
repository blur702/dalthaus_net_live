<?php
// Get site title for footer
$footer_site_title = 'Dalthaus Photography';
if (function_exists('getSetting')) {
    $footer_site_title = getSetting('site_title', 'Dalthaus Photography');
}
?>
<footer class="footer">
    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($footer_site_title) ?>. All rights reserved.</p>
    <div class="footer-links">
        <a href="/privacy">Privacy Policy</a>
        <span class="separator">|</span>
        <a href="/terms">Terms of Service</a>
        <span class="separator">|</span>
        <a href="/contact">Contact</a>
    </div>
</footer>

<style>
.footer {
    background: transparent;
    color: #7f8c8d;
    text-align: center;
    padding: 40px 20px;
    margin-top: auto;
    border-top: 1px solid #e0e0e0;
}

.footer p {
    margin-bottom: 10px;
    color: #7f8c8d;
}

.footer-links {
    margin-top: 15px;
}

.footer-links a {
    color: #3498db;
    text-decoration: none;
    font-size: 0.95rem;
    transition: color 0.3s ease;
    padding: 0 10px;
}

.footer-links a:hover {
    color: #2c3e50;
    text-decoration: underline;
}

.footer-links .separator {
    color: #7f8c8d;
    margin: 0 5px;
    font-size: 0.9rem;
}
</style>