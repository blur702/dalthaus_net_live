<!-- Articles Listing Page -->
<div class="max-w-4xl mx-auto">
    
    <?php if (!empty($articles)): ?>
        <!-- Articles List -->
        <div class="space-y-8">
            <?php foreach ($articles as $article): ?>
            <article>
                <!-- Content -->
                <div class="flex-1 content-text">
                    <h3 class="text-xl font-bold mb-2" style="font-family: 'Arimo', Arial, sans-serif;">
                        <a href="<?= $this->escape($article->getUrl()) ?>" 
                           class="text-gray-900 hover:text-gray-700 no-underline">
                            <?= $this->escape($article->getAttribute('title')) ?>
                        </a>
                    </h3>
                    
                    <div class="text-sm text-gray-900 mb-3">
                        <?= $this->escape($article->getAttribute('display_name') ?? $article->getAttribute('username') ?? 'author') ?> / 
                        <?= $article->getFormattedPublishedDate() ?>
                    </div>
                    
                    <?php $teaserText = $article->getTeaserOrExcerpt(250); ?>
                    <?php if (!empty($teaserText)): ?>
                    <div class="text-gray-900 mb-3 leading-relaxed">
                        <p class="mb-3">
                            <?= nl2br($this->escape($teaserText)) ?>
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
