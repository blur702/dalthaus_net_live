<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'Contact - ' . getSetting('site_title', 'Dalthaus Photography');

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

    if (empty($name)) $form_errors[] = 'Name is required.';
    if (empty($email)) $form_errors[] = 'Email is required.';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $form_errors[] = 'Please enter a valid email address.';
    if (empty($subject)) $form_errors[] = 'Subject is required.';
    if (empty($message)) $form_errors[] = 'Message is required.';

    if (empty($form_errors)) {
        $honeypot = trim($_POST['website'] ?? '');
        if (empty($honeypot)) {
            $form_success = true;
            // In a real application, you would send an email here.
        }
    }
}

// Start output buffering to capture the page content
ob_start();
?>

<div class="container">
    <h1 class="page-title">Get In Touch</h1>
    <div class="contact-intro">
        <p>I'd love to hear about your photography needs. Whether you're looking for portrait sessions, event photography, or custom photobook projects, let's discuss how we can bring your vision to life.</p>
    </div>

    <?php if ($form_submitted): ?>
        <?php if ($form_success): ?>
            <div class="alert alert-success">
                <strong>Thank you for your message!</strong> I'll get back to you within 24 hours.
            </div>
        <?php elseif (!empty($form_errors)): ?>
            <div class="alert alert-error">
                <strong>Please correct the following errors:</strong>
                <ul>
                    <?php foreach ($form_errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <div class="contact-layout">
        <div class="contact-form">
            <form method="POST" action="/contact">
                <div class="form-group">
                    <label for="name">Name *</label>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                    <label for="subject">Subject *</label>
                    <select id="subject" name="subject" required>
                        <option value="">Select a topic...</option>
                        <option value="Portrait Photography" <?= ($_POST['subject'] ?? '') === 'Portrait Photography' ? 'selected' : '' ?>>Portrait Photography</option>
                        <option value="Event Photography" <?= ($_POST['subject'] ?? '') === 'Event Photography' ? 'selected' : '' ?>>Event Photography</option>
                        <option value="Automotive Photography" <?= ($_POST['subject'] ?? '') === 'Automotive Photography' ? 'selected' : '' ?>>Automotive Photography</option>
                        <option value="General Inquiry" <?= ($_POST['subject'] ?? '') === 'General Inquiry' ? 'selected' : '' ?>>General Inquiry</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="message">Message *</label>
                    <textarea id="message" name="message" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
                </div>
                <div class="honeypot">
                    <label for="website">Website</label>
                    <input type="text" id="website" name="website">
                </div>
                <button type="submit" class="form-submit">Send Message</button>
            </form>
        </div>
        <div class="contact-info">
            <h3>Contact Information</h3>
            <div class="info-item">
                <h4>Email</h4>
                <p><a href="mailto:don@dalthaus.net">don@dalthaus.net</a></p>
            </div>
            <div class="info-item">
                <h4>Response Time</h4>
                <p>I typically respond within 24 hours.</p>
            </div>
        </div>
    </div>
</div>

<?php
// Get the captured content and assign it to a variable
$page_content = ob_get_clean();

// Include the template file
require_once __DIR__ . '/template.php';
?>