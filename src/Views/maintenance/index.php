<div class="maintenance-container">
    <?php if (!empty($settings['site_logo'])): ?>
        <img src="<?= $this->escape('/uploads/settings/' . $settings['site_logo']) ?>" 
             alt="<?= $this->escape($settings['site_title'] ?? 'Site Logo') ?>" 
             class="site-logo">
    <?php endif; ?>
    
    <div class="maintenance-icon">🔧</div>
    
    <h1 class="maintenance-title">
        Site Under Maintenance
    </h1>
    
    <div class="maintenance-message">
        <?= nl2br($this->escape($maintenance_message)) ?>
    </div>
    
    
    <div class="retry-info">
        <strong>For visitors:</strong> This page will automatically refresh every 5 minutes to check if maintenance is complete.
    </div>
</div>