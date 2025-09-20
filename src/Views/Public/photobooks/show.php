<!-- Photobook Display Page -->
<div class="max-w-4xl mx-auto">
    <!-- Photobook Header -->
    <header class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-2" style="font-family: 'Arimo', Arial, sans-serif;">
            <?= $this->escape($photobook->getAttribute('title')) ?>
        </h1>
        
        <div class="text-sm text-gray-900 mb-4">
            <?= $this->escape($author['display_name'] ?? $author['username'] ?? 'author') ?> / <?= $photobook->getFormattedPublishedDate() ?>
        </div>
        
        <?php if ($photobook->getAttribute('teaser_image')): ?>
        <div class="mb-6">
            <img src="<?= $this->escape($photobook->getTeaserImageUrl()) ?>" 
                 alt="<?= $this->escape($photobook->getAttribute('title')) ?>"
                 class="teaser-image mx-auto max-w-md">
        </div>
        <?php endif; ?>
    </header>
    
    
    <!-- Photobook Content -->
    <article class="prose max-w-none">
        
        <div class="content-text leading-relaxed text-gray-900">
            <?php if (!empty($content)): ?>
                <?= $content ?>
            <?php else: ?>
                <p class="mb-4">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad 
                    minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate 
                    velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto
                </p>
                <p class="mb-4">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad 
                    minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate 
                    velit esse molestie consequat, vel illum dolore eu feugiat nulla failisis at vero eros et accumsan et iusto
                </p>
                <p class="mb-4">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad 
                    minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate 
                    velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto
                </p>
                <p class="mb-4">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad 
                    minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate 
                    velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto
                </p>
                <p class="mb-4">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed diam nonummy nibh euismod tincidunt ut laoreet dolore magna aliquam erat volutpat. Ut wisi enim ad 
                    minim veniam, quis nostrud exerci tation ullamcorper suscipit lobortis nisl ut aliquip ex ea commodo consequat. Duis autem vel eum iriure dolor in hendrerit in vulputate 
                    velit esse molestie consequat, vel illum dolore eu feugiat nulla facilisis at vero eros et accumsan et iusto
                </p>
            <?php endif; ?>
        </div>
    </article>
</div>
