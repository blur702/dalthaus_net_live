<!-- Photobooks Listing Page -->
<div class="max-w-4xl mx-auto">
    <!-- Page Title -->
    <div class="mb-8 text-center">
        <h2 class="text-2xl font-bold text-gray-900 mb-4" style="font-family: Arial, sans-serif;">
            Photobooks (Listings)
        </h2>
    </div>
    
    <?php if (!empty($photobooks)): ?>
        <!-- Photobooks List -->
        <div class="space-y-8">
            <?php foreach ($photobooks as $photobook): ?>
            <article class="flex gap-6">
                <!-- Teaser Image -->
                <?php if ($photobook->getAttribute('teaser_image')): ?>
                <div class="flex-shrink-0 w-64">
                    <img src="<?= $this->escape($photobook->getTeaserImageUrl()) ?>" 
                         alt="<?= $this->escape($photobook->getAttribute('title')) ?>"
                         class="teaser-image w-full">
                </div>
                <?php else: ?>
                <div class="teaser-image flex-shrink-0 w-64">
                    TEASER IMAGE
                </div>
                <?php endif; ?>
                
                <!-- Content -->
                <div class="flex-1 content-text">
                    <h3 class="text-xl font-bold mb-2" style="font-family: Arial, sans-serif;">
                        <a href="<?= $this->escape($photobook->getUrl()) ?>" 
                           class="text-gray-900 hover:text-gray-700 no-underline">
                            <?= $this->escape($photobook->getAttribute('title')) ?>
                        </a>
                    </h3>
                    
                    <div class="text-sm text-gray-900 mb-3">
                        <?= $this->escape($photobook->getAttribute('username') ?? 'author') ?> / 
                        <?= $photobook->getFormattedPublishedDate() ?>
                    </div>
                    
                    <?php if ($photobook->getAttribute('teaser')): ?>
                    <div class="text-gray-900 mb-3 leading-relaxed">
                        <p class="mb-3">
                            <?= nl2br($this->escape($photobook->getAttribute('teaser'))) ?>
                        </p>
                        <p class="mb-3">
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna 
                            aliquam erat volutpat. Ut wisi enim ad minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea 
                            commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate velit esse molestie consequat, vel illum dolore eu 
                            feugiat nulla facilisis at vero eros et accumsan et iusto &lt;- (PHOTOBOOK TEASER TEXT)
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
                            feugiat nulla facilisis at vero eros et accumsan et iusto &lt;- (PHOTOBOOK TEASER TEXT)
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <a href="<?= $this->escape($photobook->getUrl()) ?>" class="read-more">
                        Read More
                    </a>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
        
        <?php 
        // Include pagination component
        $base_url = '/photobooks';
        include __DIR__ . '/../partials/pagination.php'; 
        ?>
        
    <?php else: ?>
        <div class="text-center py-12">
            <p class="text-gray-600 italic">No photobooks available.</p>
        </div>
    <?php endif; ?>
</div>
