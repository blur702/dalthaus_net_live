<?php
/**
 * Comprehensive Feature Checklist for Dalthaus.net CMS
 * 
 * Interactive checklist to manually test ALL features
 * Tracks progress and generates completion reports
 */

// Security check
$token = $_GET['token'] ?? '';
if ($token !== 'checklist-' . date('Ymd')) {
    die('Invalid token. Use: checklist-' . date('Ymd'));
}

// Handle AJAX updates
if ($_POST['action'] === 'update_status') {
    $featureId = $_POST['feature_id'] ?? '';
    $status = $_POST['status'] ?? '';
    
    // Load existing progress
    $progressFile = 'logs/checklist-progress.json';
    $progress = file_exists($progressFile) ? json_decode(file_get_contents($progressFile), true) : [];
    
    // Update status
    $progress[$featureId] = [
        'status' => $status,
        'timestamp' => date('Y-m-d H:i:s'),
        'notes' => $_POST['notes'] ?? ''
    ];
    
    // Save progress
    file_put_contents($progressFile, json_encode($progress, JSON_PRETTY_PRINT));
    
    echo json_encode(['success' => true]);
    exit;
}

// Load existing progress
$progressFile = 'logs/checklist-progress.json';
$progress = file_exists($progressFile) ? json_decode(file_get_contents($progressFile), true) : [];

// Feature checklist definition
$features = [
    'infrastructure' => [
        'title' => '🔧 Infrastructure & Configuration',
        'features' => [
            'db_connection' => [
                'name' => 'Database Connection',
                'description' => 'Verify database connects without errors',
                'test_steps' => [
                    'Check homepage loads without database errors',
                    'Verify admin login page accessible',
                    'Confirm no connection timeouts'
                ]
            ],
            'file_permissions' => [
                'name' => 'File Permissions',
                'description' => 'All directories writable, uploads work',
                'test_steps' => [
                    'Upload an image through admin panel',
                    'Check cache directory is writable',
                    'Verify logs are being created'
                ]
            ],
            'ssl_https' => [
                'name' => 'SSL/HTTPS Security',
                'description' => 'Site forces HTTPS, certificates valid',
                'test_steps' => [
                    'Try accessing http://dalthaus.net (should redirect)',
                    'Check for SSL certificate warnings',
                    'Verify secure cookie settings'
                ]
            ],
            'error_handling' => [
                'name' => 'Error Handling',
                'description' => 'Proper error pages, no PHP warnings visible',
                'test_steps' => [
                    'Visit non-existent page (/test-404)',
                    'Check for any PHP warnings on pages',
                    'Verify error logging is working'
                ]
            ]
        ]
    ],
    'authentication' => [
        'title' => '👤 Authentication & Security',
        'features' => [
            'admin_login' => [
                'name' => 'Admin Login',
                'description' => 'Login with correct credentials works',
                'test_steps' => [
                    'Go to /admin/login.php',
                    'Login with admin credentials',
                    'Verify redirect to dashboard'
                ]
            ],
            'session_management' => [
                'name' => 'Session Management',
                'description' => 'Sessions persist, logout works',
                'test_steps' => [
                    'Stay logged in across page visits',
                    'Test logout functionality',
                    'Verify session expires after inactivity'
                ]
            ],
            'csrf_protection' => [
                'name' => 'CSRF Protection',
                'description' => 'Forms include CSRF tokens',
                'test_steps' => [
                    'View source of admin forms',
                    'Verify csrf_token hidden fields',
                    'Test form submission without token fails'
                ]
            ],
            'unauthorized_access' => [
                'name' => 'Unauthorized Access Prevention',
                'description' => 'Admin pages require login',
                'test_steps' => [
                    'Access /admin/dashboard.php while logged out',
                    'Should redirect to login page',
                    'Try direct access to admin API endpoints'
                ]
            ]
        ]
    ],
    'content_management' => [
        'title' => '📝 Content Management',
        'features' => [
            'article_crud' => [
                'name' => 'Article CRUD Operations',
                'description' => 'Create, read, update, delete articles',
                'test_steps' => [
                    'Create new article via /admin/articles.php',
                    'Edit existing article',
                    'Delete article (soft delete)',
                    'Restore deleted article'
                ]
            ],
            'photobook_crud' => [
                'name' => 'Photobook CRUD Operations', 
                'description' => 'Create, read, update, delete photobooks',
                'test_steps' => [
                    'Create new photobook via /admin/photobooks.php',
                    'Add multiple images to photobook',
                    'Test page break functionality',
                    'Verify navigation between pages'
                ]
            ],
            'page_crud' => [
                'name' => 'Page CRUD Operations',
                'description' => 'Create, read, update, delete pages',
                'test_steps' => [
                    'Create new page via /admin/pages.php',
                    'Edit page content',
                    'Verify page displays on frontend'
                ]
            ],
            'image_upload' => [
                'name' => 'Image Upload',
                'description' => 'Upload images through admin interface',
                'test_steps' => [
                    'Upload image via admin upload page',
                    'Verify image appears in uploads directory',
                    'Check image is accessible via URL'
                ]
            ]
        ]
    ],
    'version_control' => [
        'title' => '🔄 Version Control & Autosave',
        'features' => [
            'autosave' => [
                'name' => 'Autosave Functionality',
                'description' => 'Content automatically saves every 30 seconds',
                'test_steps' => [
                    'Open article editor',
                    'Make changes and wait 30+ seconds',
                    'Check autosave indicator appears',
                    'Verify version created in database'
                ]
            ],
            'version_history' => [
                'name' => 'Version History',
                'description' => 'View and restore previous versions',
                'test_steps' => [
                    'Edit article multiple times',
                    'Go to versions page for article',
                    'Restore previous version',
                    'Verify content reverted correctly'
                ]
            ],
            'manual_save' => [
                'name' => 'Manual Save',
                'description' => 'Manually save creates numbered version',
                'test_steps' => [
                    'Edit article and click Save',
                    'Check version number incremented',
                    'Verify autosave flag is false'
                ]
            ]
        ]
    ],
    'public_interface' => [
        'title' => '🌐 Public Interface',
        'features' => [
            'homepage' => [
                'name' => 'Homepage Display',
                'description' => 'Homepage loads with proper styling',
                'test_steps' => [
                    'Visit https://dalthaus.net/',
                    'Check Arimo/Gelasio fonts load',
                    'Verify responsive design',
                    'Test navigation links'
                ]
            ],
            'articles_list' => [
                'name' => 'Articles List Page',
                'description' => 'Articles display in list format',
                'test_steps' => [
                    'Visit /articles page',
                    'Verify published articles appear',
                    'Check proper meta formatting',
                    'Test article links work'
                ]
            ],
            'individual_articles' => [
                'name' => 'Individual Article Pages',
                'description' => 'Articles display with proper formatting',
                'test_steps' => [
                    'Click on article from list',
                    'Verify content displays correctly',
                    'Check image processing works',
                    'Test meta information display'
                ]
            ],
            'photobooks_list' => [
                'name' => 'Photobooks List Page',
                'description' => 'Photobooks display in grid/list',
                'test_steps' => [
                    'Visit /photobooks page',
                    'Verify published photobooks appear',
                    'Check thumbnail images load',
                    'Test photobook links work'
                ]
            ],
            'photobook_viewer' => [
                'name' => 'Photobook Viewer',
                'description' => 'Multi-page photobook navigation',
                'test_steps' => [
                    'Open photobook with multiple pages',
                    'Test page navigation (next/prev)',
                    'Verify URL updates with page numbers',
                    'Check browser back/forward buttons'
                ]
            ],
            'responsive_design' => [
                'name' => 'Responsive Design',
                'description' => 'Site works on mobile/tablet',
                'test_steps' => [
                    'Test on mobile device or dev tools',
                    'Check navigation menu collapses',
                    'Verify text remains readable',
                    'Test touch interactions'
                ]
            ]
        ]
    ],
    'admin_interface' => [
        'title' => '⚙️ Admin Interface',
        'features' => [
            'dashboard' => [
                'name' => 'Admin Dashboard',
                'description' => 'Dashboard shows overview and stats',
                'test_steps' => [
                    'Login and view dashboard',
                    'Check content statistics display',
                    'Verify recent activity shows',
                    'Test quick action links'
                ]
            ],
            'tinymce_editor' => [
                'name' => 'TinyMCE Editor',
                'description' => 'Rich text editor functions properly',
                'test_steps' => [
                    'Open article/page editor',
                    'Test formatting buttons (bold, italic)',
                    'Insert images and links',
                    'Test page break functionality'
                ]
            ],
            'drag_drop_sorting' => [
                'name' => 'Drag & Drop Sorting',
                'description' => 'Content can be reordered by dragging',
                'test_steps' => [
                    'Go to articles or photobooks admin page',
                    'Drag items to reorder',
                    'Verify order saves automatically',
                    'Check order persists on refresh'
                ]
            ],
            'menu_management' => [
                'name' => 'Menu Management',
                'description' => 'Top/bottom menus can be configured',
                'test_steps' => [
                    'Go to /admin/menus.php',
                    'Add/remove menu items',
                    'Test drag & drop reordering',
                    'Verify changes appear on frontend'
                ]
            ]
        ]
    ],
    'advanced_features' => [
        'title' => '🚀 Advanced Features',
        'features' => [
            'document_import' => [
                'name' => 'Document Import',
                'description' => 'Word/PDF documents can be imported',
                'test_steps' => [
                    'Go to /admin/import.php',
                    'Upload Word document',
                    'Verify HTML conversion works',
                    'Check formatting preservation'
                ]
            ],
            'maintenance_mode' => [
                'name' => 'Maintenance Mode',
                'description' => 'Site can be put in maintenance mode',
                'test_steps' => [
                    'Use auto-deploy.php to enable maintenance',
                    'Verify public pages show maintenance message',
                    'Admin access still works',
                    'Disable maintenance mode'
                ]
            ],
            'caching' => [
                'name' => 'Page Caching',
                'description' => 'Pages are cached for performance',
                'test_steps' => [
                    'Visit page multiple times',
                    'Check cache files created in /cache',
                    'Verify cache cleared on content update',
                    'Test cache TTL expiration'
                ]
            ],
            'search_functionality' => [
                'name' => 'Search Functionality',
                'description' => 'Site search works if implemented',
                'test_steps' => [
                    'Look for search box on site',
                    'Test search queries',
                    'Verify relevant results returned',
                    'Check search result formatting'
                ]
            ]
        ]
    ],
    'performance' => [
        'title' => '⚡ Performance & Optimization',
        'features' => [
            'page_load_speed' => [
                'name' => 'Page Load Speed',
                'description' => 'Pages load quickly (<3 seconds)',
                'test_steps' => [
                    'Use browser dev tools to check load times',
                    'Test from different locations',
                    'Check Core Web Vitals scores',
                    'Verify images are optimized'
                ]
            ],
            'image_optimization' => [
                'name' => 'Image Optimization',
                'description' => 'Images are properly sized and compressed',
                'test_steps' => [
                    'Check image file sizes are reasonable',
                    'Verify responsive images work',
                    'Test lazy loading if implemented',
                    'Check image format support'
                ]
            ],
            'database_performance' => [
                'name' => 'Database Performance',
                'description' => 'Queries execute efficiently',
                'test_steps' => [
                    'Monitor slow query log',
                    'Check database indexes exist',
                    'Test with large content datasets',
                    'Verify no N+1 query problems'
                ]
            ]
        ]
    ]
];

// Calculate progress
function getProgress($features, $progress) {
    $total = 0;
    $completed = 0;
    
    foreach ($features as $category) {
        foreach ($category['features'] as $featureId => $feature) {
            $total++;
            if (isset($progress[$featureId]) && 
                in_array($progress[$featureId]['status'], ['pass', 'pass_with_notes'])) {
                $completed++;
            }
        }
    }
    
    return [
        'total' => $total,
        'completed' => $completed,
        'percentage' => $total > 0 ? round(($completed / $total) * 100, 1) : 0
    ];
}

$progressStats = getProgress($features, $progress);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Feature Checklist - Dalthaus.net</title>
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .progress-bar {
            background: rgba(255,255,255,0.2);
            height: 20px;
            border-radius: 10px;
            margin-top: 20px;
            overflow: hidden;
        }
        .progress-fill {
            background: #00ff88;
            height: 100%;
            transition: width 0.3s ease;
            border-radius: 10px;
        }
        .category {
            border-bottom: 1px solid #eee;
        }
        .category-header {
            background: #f8f9fa;
            padding: 20px;
            font-size: 18px;
            font-weight: bold;
            color: #495057;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .category-header:hover {
            background: #e9ecef;
        }
        .category-content {
            padding: 0;
            max-height: 0;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .category-content.expanded {
            max-height: none;
            padding: 20px;
        }
        .feature {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .feature-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .feature-title {
            font-weight: bold;
            font-size: 16px;
            color: #495057;
        }
        .feature-description {
            font-size: 14px;
            color: #6c757d;
            margin-top: 5px;
        }
        .status-buttons {
            display: flex;
            gap: 10px;
        }
        .status-btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: bold;
            transition: all 0.2s;
        }
        .status-btn.pass {
            background: #28a745;
            color: white;
        }
        .status-btn.fail {
            background: #dc3545;
            color: white;
        }
        .status-btn.skip {
            background: #6c757d;
            color: white;
        }
        .status-btn.active {
            transform: scale(1.1);
            box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        }
        .test-steps {
            padding: 15px 20px;
            background: #f8f9fa;
        }
        .test-steps h4 {
            margin: 0 0 10px 0;
            color: #495057;
        }
        .test-steps ul {
            margin: 0;
            padding-left: 20px;
        }
        .test-steps li {
            margin-bottom: 5px;
            font-size: 14px;
            color: #6c757d;
        }
        .notes-section {
            padding: 15px 20px;
            border-top: 1px solid #dee2e6;
            background: #fff;
        }
        .notes-textarea {
            width: 100%;
            min-height: 60px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            padding: 8px 12px;
            font-family: inherit;
            font-size: 14px;
            resize: vertical;
        }
        .summary {
            text-align: center;
            padding: 30px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
        .completion-stats {
            display: flex;
            justify-content: space-around;
            margin: 20px 0;
        }
        .stat {
            text-align: center;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #495057;
        }
        .stat-label {
            font-size: 12px;
            color: #6c757d;
            text-transform: uppercase;
        }
        .expand-all {
            text-align: right;
            padding: 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }
        .expand-all button {
            background: #007bff;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        .last-updated {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🎯 Comprehensive Feature Checklist</h1>
        <p>Manual testing of ALL Dalthaus.net CMS features</p>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $progressStats['percentage']; ?>%"></div>
        </div>
        <div style="margin-top: 15px;">
            <strong><?php echo $progressStats['completed']; ?> of <?php echo $progressStats['total']; ?> features tested (<?php echo $progressStats['percentage']; ?>%)</strong>
        </div>
    </div>

    <div class="expand-all">
        <button onclick="toggleAllCategories()">Expand All Categories</button>
    </div>

    <?php foreach ($features as $categoryId => $category): ?>
    <div class="category">
        <div class="category-header" onclick="toggleCategory('<?php echo $categoryId; ?>')">
            <span><?php echo $category['title']; ?></span>
            <span id="toggle-<?php echo $categoryId; ?>">▼</span>
        </div>
        <div class="category-content" id="content-<?php echo $categoryId; ?>">
            <?php foreach ($category['features'] as $featureId => $feature): ?>
            <?php 
            $currentStatus = $progress[$featureId]['status'] ?? 'pending';
            $currentNotes = $progress[$featureId]['notes'] ?? '';
            $lastUpdated = $progress[$featureId]['timestamp'] ?? '';
            ?>
            <div class="feature" id="feature-<?php echo $featureId; ?>">
                <div class="feature-header">
                    <div>
                        <div class="feature-title"><?php echo $feature['name']; ?></div>
                        <div class="feature-description"><?php echo $feature['description']; ?></div>
                        <?php if ($lastUpdated): ?>
                        <div class="last-updated">Last updated: <?php echo $lastUpdated; ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="status-buttons">
                        <button class="status-btn pass <?php echo $currentStatus === 'pass' ? 'active' : ''; ?>" 
                                onclick="updateStatus('<?php echo $featureId; ?>', 'pass')">✅ PASS</button>
                        <button class="status-btn fail <?php echo $currentStatus === 'fail' ? 'active' : ''; ?>" 
                                onclick="updateStatus('<?php echo $featureId; ?>', 'fail')">❌ FAIL</button>
                        <button class="status-btn skip <?php echo $currentStatus === 'skip' ? 'active' : ''; ?>" 
                                onclick="updateStatus('<?php echo $featureId; ?>', 'skip')">⏭️ SKIP</button>
                    </div>
                </div>
                
                <div class="test-steps">
                    <h4>📋 Test Steps:</h4>
                    <ul>
                        <?php foreach ($feature['test_steps'] as $step): ?>
                        <li><?php echo $step; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="notes-section">
                    <h4>📝 Notes:</h4>
                    <textarea class="notes-textarea" 
                              id="notes-<?php echo $featureId; ?>"
                              placeholder="Add testing notes, issues found, or additional observations..."><?php echo htmlspecialchars($currentNotes); ?></textarea>
                    <button onclick="updateNotes('<?php echo $featureId; ?>')" 
                            style="margin-top: 10px; padding: 5px 15px; background: #007bff; color: white; border: none; border-radius: 3px; cursor: pointer;">
                        Save Notes
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

    <div class="summary">
        <h2>📊 Testing Progress</h2>
        <div class="completion-stats">
            <div class="stat">
                <div class="stat-number"><?php echo $progressStats['completed']; ?></div>
                <div class="stat-label">Completed</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $progressStats['total'] - $progressStats['completed']; ?></div>
                <div class="stat-label">Remaining</div>
            </div>
            <div class="stat">
                <div class="stat-number"><?php echo $progressStats['percentage']; ?>%</div>
                <div class="stat-label">Complete</div>
            </div>
        </div>
        
        <div style="margin-top: 30px;">
            <button onclick="generateReport()" 
                    style="background: #28a745; color: white; border: none; padding: 15px 30px; border-radius: 5px; cursor: pointer; font-size: 16px;">
                📄 Generate Completion Report
            </button>
            <button onclick="resetProgress()" 
                    style="background: #dc3545; color: white; border: none; padding: 15px 30px; border-radius: 5px; cursor: pointer; font-size: 16px; margin-left: 10px;">
                🔄 Reset All Progress
            </button>
        </div>
    </div>
</div>

<script>
let allExpanded = false;

function toggleCategory(categoryId) {
    const content = document.getElementById('content-' + categoryId);
    const toggle = document.getElementById('toggle-' + categoryId);
    
    if (content.classList.contains('expanded')) {
        content.classList.remove('expanded');
        toggle.textContent = '▼';
    } else {
        content.classList.add('expanded');
        toggle.textContent = '▲';
    }
}

function toggleAllCategories() {
    const categories = document.querySelectorAll('.category-content');
    const toggles = document.querySelectorAll('[id^="toggle-"]');
    const button = document.querySelector('.expand-all button');
    
    allExpanded = !allExpanded;
    
    categories.forEach(category => {
        if (allExpanded) {
            category.classList.add('expanded');
        } else {
            category.classList.remove('expanded');
        }
    });
    
    toggles.forEach(toggle => {
        toggle.textContent = allExpanded ? '▲' : '▼';
    });
    
    button.textContent = allExpanded ? 'Collapse All Categories' : 'Expand All Categories';
}

function updateStatus(featureId, status) {
    const notes = document.getElementById('notes-' + featureId).value;
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_status&feature_id=${featureId}&status=${status}&notes=${encodeURIComponent(notes)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button states
            const feature = document.getElementById('feature-' + featureId);
            const buttons = feature.querySelectorAll('.status-btn');
            buttons.forEach(btn => btn.classList.remove('active'));
            
            const activeBtn = feature.querySelector(`.status-btn.${status}`);
            if (activeBtn) activeBtn.classList.add('active');
            
            // Update progress
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to update status');
    });
}

function updateNotes(featureId) {
    const notes = document.getElementById('notes-' + featureId).value;
    const currentStatus = document.querySelector(`#feature-${featureId} .status-btn.active`);
    const status = currentStatus ? currentStatus.classList[1] : 'pending';
    
    fetch('', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `action=update_status&feature_id=${featureId}&status=${status}&notes=${encodeURIComponent(notes)}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Notes saved successfully');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Failed to save notes');
    });
}

function generateReport() {
    window.open('feature-report.php?token=report-<?php echo date("Ymd"); ?>', '_blank');
}

function resetProgress() {
    if (confirm('Are you sure you want to reset ALL progress? This cannot be undone.')) {
        fetch('', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'action=reset_progress'
        })
        .then(() => {
            location.reload();
        });
    }
}

// Auto-expand first category on load
document.addEventListener('DOMContentLoaded', function() {
    const firstCategory = document.querySelector('.category-content');
    if (firstCategory) {
        firstCategory.classList.add('expanded');
        document.querySelector('[id^="toggle-"]').textContent = '▲';
    }
});
</script>

</body>
</html>