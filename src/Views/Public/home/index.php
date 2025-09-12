<!-- Homepage Content -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Articles Section (Left - 2/3 width) -->
    <div class="lg:col-span-2">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b border-gray-300 pb-2">
                Articles
            </h2>
            
            <?php if (!empty($articles)): ?>
                <div class="space-y-8">
                    <?php foreach ($articles as $article): ?>
                    <article class="flex gap-6">
                        <!-- Teaser Image -->
                        <?php if ($article->getAttribute('teaser_image')): ?>
                        <div class="flex-shrink-0 w-48">
                            <img src="<?= $this->escape($article->getTeaserImageUrl()) ?>" 
                                 alt="<?= $this->escape($article->getAttribute('title')) ?>"
                                 class="teaser-image w-full">
                        </div>
                        <?php else: ?>
                        <div class="flex-shrink-0 w-48">
                            <div class="teaser-image bg-black text-white flex items-center justify-center text-lg font-bold">
                                TEASER IMAGE
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Content -->
                        <div class="flex-1 content-text">
                            <h3 class="text-xl font-bold mb-2">
                                <a href="<?= $this->escape($article->getUrl()) ?>" 
                                   class="text-gray-900 hover:text-gray-700">
                                    <?= $this->escape($article->getAttribute('title')) ?>
                                </a>
                            </h3>
                            
                            <div class="text-sm text-gray-600 mb-3">
                                <?= $this->escape($article->getAttribute('username') ?? 'Unknown') ?> / 
                                <?= $article->getFormattedPublishedDate() ?>
                            </div>
                            
                            <?php if ($article->getAttribute('teaser')): ?>
                            <p class="text-gray-800 mb-3 leading-relaxed">
                                <?= nl2br($this->escape($article->getAttribute('teaser'))) ?>
                            </p>
                            <?php endif; ?>
                            
                            <a href="<?= $this->escape($article->getUrl()) ?>" class="read-more">
                                Read More
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- View All Articles Link -->
                <div class="mt-8 text-center">
                    <a href="/articles" class="read-more">
                        View All Articles
                    </a>
                </div>
            <?php else: ?>
                <p class="text-gray-600 italic">No articles available.</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Photobooks Section (Right - 1/3 width) -->
    <div class="lg:col-span-1">
        <div class="mb-8">
            <h2 class="text-2xl font-bold text-gray-900 mb-6 border-b border-gray-300 pb-2">
                Photobooks
            </h2>
            
            <?php if (!empty($photobooks)): ?>
                <div class="space-y-6">
                    <?php foreach ($photobooks as $photobook): ?>
                    <article>
                        <!-- Teaser Image -->
                        <?php if ($photobook->getAttribute('teaser_image')): ?>
                        <div class="mb-3">
                            <img src="<?= $this->escape($photobook->getTeaserImageUrl()) ?>" 
                                 alt="<?= $this->escape($photobook->getAttribute('title')) ?>"
                                 class="teaser-image w-full">
                        </div>
                        <?php else: ?>
                        <div class="mb-3 w-full">
                            <div class="teaser-image bg-black text-white flex items-center justify-center text-lg font-bold">
                                TEASER IMAGE
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Content -->
                        <div class="content-text">
                            <h3 class="text-lg font-bold mb-2">
                                <a href="<?= $this->escape($photobook->getUrl()) ?>" 
                                   class="text-gray-900 hover:text-gray-700">
                                    <?= $this->escape($photobook->getAttribute('title')) ?>
                                </a>
                            </h3>
                            
                            <div class="text-sm text-gray-600 mb-3">
                                <?= $this->escape($photobook->getAttribute('username') ?? 'Unknown') ?> / 
                                <?= $photobook->getFormattedPublishedDate() ?>
                            </div>
                            
                            <?php if ($photobook->getAttribute('teaser')): ?>
                            <p class="text-gray-800 mb-3 text-sm leading-relaxed">
                                <?= $this->truncate($photobook->getAttribute('teaser'), 120) ?>
                            </p>
                            <?php endif; ?>
                            
                            <a href="<?= $this->escape($photobook->getUrl()) ?>" class="read-more text-sm">
                                Read More
                            </a>
                        </div>
                    </article>
                    <?php endforeach; ?>
                </div>
                
                <!-- View All Photobooks Link -->
                <div class="mt-8 text-center">
                    <a href="/photobooks" class="read-more">
                        View All Photobooks
                    </a>
                </div>
            <?php else: ?>
                <p class="text-gray-600 italic">No photobooks available.</p>
            <?php endif; ?>
        </div>
    </div>
</div>
