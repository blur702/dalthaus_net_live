<?php
/**
 * Articles Page - Lists all published articles
 */

// Set page variables
$page_title = 'All Articles';
$site_title = 'Dalthaus Photography';

// Include header
include 'includes/site-header.php';

// Include navigation
include 'includes/navigation.php';
?>

    <!-- Main Content -->
    <main class="main-content">
        <div class="articles-grid">
            <!-- Article 1 -->
            <article class="article-item">
                <div class="article-image"></div>
                <div class="article-content">
                    <a href="/article/ramchargers-conquer-the-automatic" class="article-title">
                        Ramchargers Conquer The Automatic
                    </a>
                    <div class="article-meta">
                        Don Althaus · September 01, 2025
                    </div>
                    <div class="article-teaser">
                        In the early 1960s, a renegade group of Chrysler engineers known as The Ramchargers were rewriting the rules of drag racing. Operating out of Detroit, they fused corporate resources with after-hours passion to dominate the strip...
                    </div>
                    <a href="/article/ramchargers-conquer-the-automatic" class="read-more">Read more →</a>
                </div>
            </article>
            
            <!-- Article 2 -->
            <article class="article-item">
                <div class="article-image"></div>
                <div class="article-content">
                    <a href="/article/welcome" class="article-title">
                        The title is about the dog!
                    </a>
                    <div class="article-meta">
                        Don Althaus · August 29, 2025
                    </div>
                    <div class="article-teaser">
                        The quick brown fox jumped over the lazy dog's back but landed in the snow bank...giving the dog a good laugh as he was warm and cozy in his doghouse...
                    </div>
                    <a href="/article/welcome" class="read-more">Read more →</a>
                </div>
            </article>
            
            <!-- Article 3 (Sample) -->
            <article class="article-item">
                <div class="article-image"></div>
                <div class="article-content">
                    <a href="/article/light-and-shadow" class="article-title">
                        Mastering Light and Shadow
                    </a>
                    <div class="article-meta">
                        Don Althaus · August 15, 2025
                    </div>
                    <div class="article-teaser">
                        Photography is fundamentally about capturing light. Understanding how light interacts with your subjects, creates mood, and defines form is essential to creating compelling images...
                    </div>
                    <a href="/article/light-and-shadow" class="read-more">Read more →</a>
                </div>
            </article>
            
            <!-- Article 4 (Sample) -->
            <article class="article-item">
                <div class="article-image"></div>
                <div class="article-content">
                    <a href="/article/street-photography-ethics" class="article-title">
                        Street Photography and Ethics
                    </a>
                    <div class="article-meta">
                        Don Althaus · August 10, 2025
                    </div>
                    <div class="article-teaser">
                        Navigating the delicate balance between artistic expression and personal privacy in street photography requires both technical skill and ethical consideration...
                    </div>
                    <a href="/article/street-photography-ethics" class="read-more">Read more →</a>
                </div>
            </article>
        </div>
    </main>

<?php
// Include footer
include 'includes/site-footer.php';
?>