<?php
/**
 * Photobooks Page - Lists all published photobooks
 */

// Set page variables
$page_title = 'All Photobooks';
$site_title = 'Dalthaus Photography';

// Include header
include 'includes/site-header.php';

// Include navigation
include 'includes/navigation.php';
?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="photobooks-grid">
            <!-- Photobook 1 -->
            <article class="photobook-item">
                <div class="photobook-image"></div>
                <div class="photobook-content">
                    <a href="/photobook/storytellers-legacy" class="photobook-title">
                        The Storyteller's Legacy
                    </a>
                    <div class="photobook-meta">
                        Don Althaus · August 29, 2025
                    </div>
                    <div class="photobook-teaser">
                        Once upon a time, in a small village nestled between rolling hills and ancient forests, there lived a young photographer named Elena. She had inherited an old camera from her grandmother, along with a collection of mysterious photographs...
                    </div>
                    <a href="/photobook/storytellers-legacy" class="view-photobook">View photobook →</a>
                </div>
            </article>
            
            <!-- Photobook 2 (Sample) -->
            <article class="photobook-item">
                <div class="photobook-image"></div>
                <div class="photobook-content">
                    <a href="/photobook/urban-perspectives" class="photobook-title">
                        Urban Perspectives
                    </a>
                    <div class="photobook-meta">
                        Don Althaus · July 20, 2025
                    </div>
                    <div class="photobook-teaser">
                        A visual journey through the modern cityscape, exploring the interplay between architecture, light, and human presence in urban environments. This collection captures the rhythm and energy of metropolitan life...
                    </div>
                    <a href="/photobook/urban-perspectives" class="view-photobook">View photobook →</a>
                </div>
            </article>
            
            <!-- Photobook 3 (Sample) -->
            <article class="photobook-item">
                <div class="photobook-image"></div>
                <div class="photobook-content">
                    <a href="/photobook/natural-wonders" class="photobook-title">
                        Natural Wonders
                    </a>
                    <div class="photobook-meta">
                        Don Althaus · June 15, 2025
                    </div>
                    <div class="photobook-teaser">
                        From misty mountain peaks to serene forest paths, this collection celebrates the raw beauty of untouched landscapes. Each image tells a story of nature's grandeur and the delicate balance of ecosystems...
                    </div>
                    <a href="/photobook/natural-wonders" class="view-photobook">View photobook →</a>
                </div>
            </article>
        </div>
    </main>

<?php
// Include footer
include 'includes/site-footer.php';
?>