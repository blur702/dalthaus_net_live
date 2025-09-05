<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

$page_title = 'About - ' . getSetting('site_title', 'Dalthaus Photography');

// Get the content for the about page
$about_content = getPageContent('about');

// Start output buffering to capture the page content
ob_start();
?>

<div class="container">
    <h1 class="page-title">About Don Althaus</h1>
    <div class="about-content">
        <?php if (!empty($about_content)): ?>
            <?= $about_content ?>
        <?php else: ?>
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
        <?php endif; ?>
    </div>
</div>

<?php
// Get the captured content and assign it to a variable
$page_content = ob_get_clean();

// Include the template file
require_once __DIR__ . '/template.php';
?>