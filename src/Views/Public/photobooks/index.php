<!-- Photobooks Listing Page -->
<div class="max-w-4xl mx-auto">
    
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
                <div class="flex-shrink-0 w-64">
                    <div class="teaser-image bg-black text-white flex items-center justify-center text-lg font-bold">
                        TEASER IMAGE
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Content -->
                <div class="flex-1 content-text">
                    <h3 class="text-xl font-bold mb-2" style="font-family: 'Arimo', Arial, sans-serif;">
                        <a href="<?= $this->escape($photobook->getUrl()) ?>" 
                           class="text-gray-900 hover:text-gray-700 no-underline">
                            <?= $this->escape($photobook->getAttribute('title')) ?>
                        </a>
                    </h3>
                    
                    <div class="text-sm text-gray-900 mb-3">
                        <?= $this->escape($photobook->getAttribute('username') ?? 'author') ?> / 
                        <?= $photobook->getFormattedPublishedDate() ?>
                    </div>
                    
                    <?php $teaserText = $photobook->getTeaserOrExcerpt(250); ?>
                    <?php if (!empty($teaserText)): ?>
                    <div class="text-gray-900 mb-3 leading-relaxed">
                        <p class="mb-3">
                            <?= nl2br($this->escape($teaserText)) ?>
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
