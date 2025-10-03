<!-- Photobooks Listing Page -->
<div class="max-w-4xl mx-auto px-3 sm:px-6 lg:px-8">

    <?php if (isset($debug_message)): ?>
        <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
            <strong>Debug:</strong> <?= $this->escape($debug_message) ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($photobooks)): ?>
        <!-- Photobooks List -->
        <div class="space-y-6 sm:space-y-8">
            <?php foreach ($photobooks as $photobook): ?>
            <article class="flex flex-col sm:flex-row gap-4 sm:gap-6">
                <!-- Teaser Image -->
                <?php if ($photobook->getAttribute('teaser_image')): ?>
                <div class="flex-shrink-0 w-full sm:w-64">
                    <div class="h-48 sm:h-48 overflow-hidden">
                        <img src="<?= $this->escape($photobook->getTeaserImageUrl()) ?>"
                             alt="<?= $this->escape($photobook->getAttribute('title')) ?>"
                             class="teaser-image w-full h-full object-cover">
                    </div>
                </div>
                <?php else: ?>
                <div class="flex-shrink-0 w-full sm:w-64">
                    <div class="teaser-image h-48 sm:h-48 bg-black text-white flex items-center justify-center text-base sm:text-lg font-bold">
                        TEASER IMAGE
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Content -->
                <div class="flex-1 content-text px-1 sm:px-0">
                    <h3 class="text-lg sm:text-xl font-bold mb-2 leading-tight" style="font-family: 'Arimo', Arial, sans-serif;">
                        <a href="<?= $this->escape($photobook->getUrl()) ?>"
                           class="text-gray-900 hover:text-gray-700 no-underline">
                            <?= $this->escape($photobook->getAttribute('title')) ?>
                        </a>
                    </h3>

                    <div class="text-xs sm:text-sm text-gray-900 mb-2 sm:mb-3">
                        <?= $this->escape($photobook->getAttribute('display_name') ?? $photobook->getAttribute('username') ?? 'author') ?> /
                        <?= $photobook->getFormattedPublishedDate() ?>
                    </div>
                    
                    <?php $teaserText = $photobook->getTeaserOrExcerpt(200); ?>
                    <?php if (!empty($teaserText)): ?>
                    <div class="text-gray-900 mb-3 leading-relaxed text-sm sm:text-base">
                        <p class="mb-2 sm:mb-3">
                            <?= nl2br($this->escape($teaserText)) ?>
                        </p>
                    </div>
                    <?php endif; ?>

                    <a href="<?= $this->escape($photobook->getUrl()) ?>" class="read-more text-sm sm:text-base">
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
        <div class="text-center py-8 sm:py-12 px-4">
            <p class="text-gray-600 italic text-sm sm:text-base">No photobooks available.</p>
        </div>
    <?php endif; ?>
</div>
