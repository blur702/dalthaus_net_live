<?php
/**
 * Blog Post Detail - Public View
 * Displays a single blog post with full content and metadata
 */
?>

<!-- Structured Data for SEO -->
<?php if (isset($structuredData)): ?>
<script type="application/ld+json">
<?= json_encode($structuredData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) ?>
</script>
<?php endif; ?>

<!-- Main Article -->
<article class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
    <!-- Back Navigation -->
    <div class="mb-8">
        <a href="/blog" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition duration-150">
            <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path>
            </svg>
            Back to Blog
        </a>
    </div>

    <!-- Article Header -->
    <header class="mb-8">
        <!-- Tags -->
        <?php if (!empty($post['tags'])): ?>
        <div class="mb-4">
            <?php 
            $tags = is_string($post['tags']) ? array_filter(array_map('trim', explode(',', $post['tags']))) : [];
            foreach ($tags as $tag): 
            ?>
            <a href="/blog?tags[]=<?= urlencode($tag) ?>" 
               class="inline-block bg-blue-100 hover:bg-blue-200 text-blue-800 rounded-full px-3 py-1 text-sm font-medium mr-2 mb-2 transition duration-150">
                #<?= $this->escape($tag) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        
        <!-- Title -->
        <h1 class="text-4xl font-bold text-gray-900 mb-4 leading-tight">
            <?= $this->escape($post['title']) ?>
        </h1>
        
        <!-- Excerpt -->
        <?php if (!empty($post['excerpt'])): ?>
        <p class="text-xl text-gray-600 mb-6 leading-relaxed">
            <?= $this->escape($post['excerpt']) ?>
        </p>
        <?php endif; ?>
        
        <!-- Meta Information -->
        <div class="flex flex-wrap items-center gap-6 text-gray-500 border-b border-gray-200 pb-6">
            <!-- Published Date -->
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"></path>
                </svg>
                <time datetime="<?= date('Y-m-d', strtotime($post['published_at'])) ?>">
                    <?= date('F j, Y', strtotime($post['published_at'])) ?>
                </time>
            </div>
            
            <!-- Reading Time -->
            <?php if (!empty($post['reading_time'])): ?>
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"></path>
                </svg>
                <?= $this->escape($post['reading_time']) ?> min read
            </div>
            <?php endif; ?>
            
            <!-- Author -->
            <?php if (!empty($post['author_name'])): ?>
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"></path>
                </svg>
                By <?= $this->escape($post['author_name']) ?>
            </div>
            <?php endif; ?>
            
            <!-- Updated Date (if different from published) -->
            <?php if (!empty($post['updated_at']) && $post['updated_at'] !== $post['published_at']): ?>
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"></path>
                </svg>
                <span class="text-sm">
                    Updated <?= date('M j, Y', strtotime($post['updated_at'])) ?>
                </span>
            </div>
            <?php endif; ?>
        </div>
    </header>

    <!-- Featured Image -->
    <?php if (!empty($post['featured_image'])): ?>
    <div class="mb-8">
        <img src="<?= $this->escape($post['featured_image']) ?>" 
             alt="<?= $this->escape($post['title']) ?>"
             class="w-full h-auto rounded-lg shadow-lg">
    </div>
    <?php endif; ?>

    <!-- Article Content -->
    <div class="prose prose-lg prose-blue max-w-none mb-12">
        <?= $post['body'] ?>
    </div>
    
    <!-- Social Sharing -->
    <div class="border-t border-gray-200 pt-8 mb-8">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Share this post</h3>
        <div class="flex space-x-4">
            <!-- Twitter -->
            <a href="https://twitter.com/intent/tweet?url=<?= urlencode($currentUrl) ?>&text=<?= urlencode($post['title']) ?>" 
               target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center px-4 py-2 bg-blue-400 hover:bg-blue-500 text-white rounded-md transition duration-150">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M23.953 4.57a10 10 0 01-2.825.775 4.958 4.958 0 002.163-2.723c-.951.555-2.005.959-3.127 1.184a4.92 4.92 0 00-8.384 4.482C7.69 8.095 4.067 6.13 1.64 3.162a4.822 4.822 0 00-.666 2.475c0 1.71.87 3.213 2.188 4.096a4.904 4.904 0 01-2.228-.616v.06a4.923 4.923 0 003.946 4.827 4.996 4.996 0 01-2.212.085 4.936 4.936 0 004.604 3.417 9.867 9.867 0 01-6.102 2.105c-.39 0-.779-.023-1.17-.067a13.995 13.995 0 007.557 2.209c9.053 0 13.998-7.496 13.998-13.985 0-.21 0-.42-.015-.63A9.935 9.935 0 0024 4.59z"/>
                </svg>
                Twitter
            </a>
            
            <!-- Facebook -->
            <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode($currentUrl) ?>" 
               target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition duration-150">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                </svg>
                Facebook
            </a>
            
            <!-- LinkedIn -->
            <a href="https://www.linkedin.com/sharing/share-offsite/?url=<?= urlencode($currentUrl) ?>" 
               target="_blank" rel="noopener noreferrer"
               class="inline-flex items-center px-4 py-2 bg-blue-700 hover:bg-blue-800 text-white rounded-md transition duration-150">
                <svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                </svg>
                LinkedIn
            </a>
            
            <!-- Copy Link -->
            <button onclick="copyToClipboard('<?= $this->escape($currentUrl) ?>')" 
                    class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md transition duration-150">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                </svg>
                Copy Link
            </button>
        </div>
    </div>

    <!-- Related Content -->
    <?php if (!empty($relatedContent)): ?>
    <div class="border-t border-gray-200 pt-8">
        <h3 class="text-lg font-medium text-gray-900 mb-6">Related Content</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <?php foreach ($relatedContent as $content): ?>
            <div class="bg-gray-50 rounded-lg p-4 hover:bg-gray-100 transition duration-150">
                <h4 class="text-md font-medium text-gray-900 mb-2">
                    <a href="<?= $content['type'] === 'article' ? '/article/' : '/blog/' ?><?= $this->escape($content['url_alias']) ?>" 
                       class="hover:text-blue-600 transition duration-150">
                        <?= $this->escape($content['title']) ?>
                    </a>
                </h4>
                <p class="text-sm text-gray-600">
                    <?= ucfirst($content['type']) ?> • 
                    <?= date('M j, Y', strtotime($content['published_at'] ?? $content['created_at'])) ?>
                </p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Navigation to Previous/Next Posts -->
    <?php if (!empty($prevPost) || !empty($nextPost)): ?>
    <nav class="border-t border-gray-200 pt-8 mt-8">
        <div class="flex justify-between items-center">
            <?php if (!empty($prevPost)): ?>
            <a href="/blog/<?= $this->escape($prevPost['url_alias']) ?>" 
               class="flex-1 mr-4 group text-left">
                <div class="text-sm text-gray-500 group-hover:text-gray-700 transition duration-150">← Previous</div>
                <div class="text-lg font-medium text-gray-900 group-hover:text-blue-600 transition duration-150">
                    <?= $this->escape($prevPost['title']) ?>
                </div>
            </a>
            <?php else: ?>
            <div class="flex-1"></div>
            <?php endif; ?>
            
            <?php if (!empty($nextPost)): ?>
            <a href="/blog/<?= $this->escape($nextPost['url_alias']) ?>" 
               class="flex-1 ml-4 group text-right">
                <div class="text-sm text-gray-500 group-hover:text-gray-700 transition duration-150">Next →</div>
                <div class="text-lg font-medium text-gray-900 group-hover:text-blue-600 transition duration-150">
                    <?= $this->escape($nextPost['title']) ?>
                </div>
            </a>
            <?php else: ?>
            <div class="flex-1"></div>
            <?php endif; ?>
        </div>
    </nav>
    <?php endif; ?>
</article>

<script>
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(function() {
        // Show a temporary success message
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<svg class="w-4 h-4 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>Copied!';
        
        setTimeout(() => {
            button.innerHTML = originalText;
        }, 2000);
    }).catch(function(err) {
        console.error('Failed to copy: ', err);
        alert('Failed to copy link to clipboard');
    });
}
</script>

<style>
/* Enhanced prose styling for blog content */
.prose {
    color: #374151;
    max-width: none;
}

.prose h1, .prose h2, .prose h3, .prose h4, .prose h5, .prose h6 {
    color: #111827;
    font-weight: 700;
    line-height: 1.25;
}

.prose h2 {
    font-size: 1.875rem;
    margin-top: 2rem;
    margin-bottom: 1rem;
}

.prose h3 {
    font-size: 1.5rem;
    margin-top: 1.5rem;
    margin-bottom: 0.75rem;
}

.prose h4 {
    font-size: 1.25rem;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
}

.prose p {
    margin-bottom: 1.25rem;
    line-height: 1.75;
}

.prose ul, .prose ol {
    margin-top: 1.25rem;
    margin-bottom: 1.25rem;
    padding-left: 1.5rem;
}

.prose li {
    margin-bottom: 0.5rem;
}

.prose a {
    color: #3B82F6;
    text-decoration: underline;
}

.prose a:hover {
    color: #1D4ED8;
}

.prose blockquote {
    border-left: 4px solid #E5E7EB;
    margin: 1.5rem 0;
    padding-left: 1.5rem;
    font-style: italic;
    color: #6B7280;
}

.prose pre {
    background: #F3F4F6;
    border-radius: 0.5rem;
    padding: 1rem;
    overflow-x: auto;
    margin: 1.5rem 0;
}

.prose code {
    background: #F3F4F6;
    padding: 0.25rem 0.5rem;
    border-radius: 0.25rem;
    font-size: 0.875em;
}

.prose pre code {
    background: none;
    padding: 0;
}

.prose img {
    border-radius: 0.5rem;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}

.prose table {
    width: 100%;
    margin: 1.5rem 0;
    border-collapse: collapse;
}

.prose th, .prose td {
    border: 1px solid #E5E7EB;
    padding: 0.75rem;
    text-align: left;
}

.prose th {
    background: #F9FAFB;
    font-weight: 600;
}
</style>