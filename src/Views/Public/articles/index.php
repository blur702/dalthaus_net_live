<!-- Articles Listing Page -->
<div class="max-w-4xl mx-auto">
    <!-- Page Title -->
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900 mb-4" style="font-family: Arial, sans-serif;">
            Articles
        </h2>
    </div>
    
    <?php if (!empty($articles)): ?>
        <!-- Articles List -->
        <div class="space-y-8">
            <?php foreach ($articles as $article): ?>
            <article class="flex gap-6">
                <!-- Teaser Image -->
                <?php if ($article->getAttribute('teaser_image')): ?>
                <div class="flex-shrink-0 w-64">
                    <img src="<?= $this->escape($article->getTeaserImageUrl()) ?>" 
                         alt="<?= $this->escape($article->getAttribute('title')) ?>"
                         class="teaser-image w-full">
                </div>
                <?php else: ?>
                <div class="flex-shrink-0 w-64">
                    <div class="teaser-image bg-black text-white flex items-center justify-center text-lg font-bold">
                        TEASER IMAGE
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Content -->
                <div class="flex-1 content-text">
                    <h3 class="text-xl font-bold mb-2" style="font-family: Arial, sans-serif;">
                        <a href="<?= $this->escape($article->getUrl()) ?>" 
                           class="text-gray-900 hover:text-gray-700 no-underline">
                            <?= $this->escape($article->getAttribute('title')) ?>
                        </a>
                    </h3>
                    
                    <div class="text-sm text-gray-900 mb-3">
                        <?= $this->escape($article->getAttribute('username') ?? 'author') ?> / 
                        <?= $article->getFormattedPublishedDate() ?>
                    </div>
                    
                    <?php if ($article->getAttribute('teaser')): ?>
                    <div class="text-gray-900 mb-3 leading-relaxed">
                        <p class="mb-3">
                            <?= nl2br($this->escape($article->getAttribute('teaser'))) ?>
                        </p>
                        <p class="mb-3">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna 
                            aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea 
                            commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu 
                            feugiat nulla facilisis at vero eros et accumsan et iusto &lt;- (articleTEASER TEXT)
                        </p>
                    </div>
                    <?php else: ?>
                    <div class="text-gray-900 mb-3 leading-relaxed">
                        <p class="mb-3">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna 
                            aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea 
                            commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu 
                            feugiat nulla facilisis at vero eros et accumsan et iusto
                        </p>
                        <p class="mb-3">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna 
                            aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea 
                            commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu 
                            feugiat nulla facilisis at vero eros et accumsan et iusto &lt;- (articleTEASER TEXT)
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <a href="<?= $this->escape($article->getUrl()) ?>" class="read-more">
                        Read More
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        
        <?php 
        // Include pagination component
        $base_url = '/articles';
        include __DIR__ . '/../partials/pagination.php'; 
        ?>
        
    <?php else: ?>
        <div class="text-center py-12">
            <p class="text-gray-600 italic">No articles available.</p>
        </div>
    <?php endif; ?>
</div>
