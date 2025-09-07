<!-- Static Page Display -->
<div class="max-w-4xl mx-auto">
    <!-- Page Header -->
    <header class="mb-8 text-center">
        <h1 class="text-2xl font-bold text-gray-900 mb-4" style="font-family: Arial, sans-serif;">
            <?= $this->escape($page->getAttribute('title')) ?> (Page Display)
        </h1>
    </header>
    
    <!-- Page Content -->
    <article class="prose max-w-none">
        <div class="content-text leading-relaxed text-gray-900">
            <?php if ($page->getAttribute('content')): ?>
                <?= $page->getAttribute('content') ?>
            <?php else: ?>
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
                    This is a static page that can contain any content. It follows the same clean, simple design as the rest of the site with consistent typography and spacing.
                </p>
            <?php endif; ?>
        </div>
    </article>
</div>
