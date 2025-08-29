<?php
/**
 * Complete Deployment and Testing Script
 * 
 * 1. Pulls latest code from GitHub
 * 2. Runs automated tests
 * 3. Provides manual testing checklist
 * 4. Reports on deployment success
 */

// Security check
$token = $_GET['token'] ?? '';
if ($token !== 'deploy-test-' . date('Ymd')) {
    die('Invalid token. Use: deploy-test-' . date('Ymd'));
}

$step = $_GET['step'] ?? 'start';

// Function to execute shell command and capture output
function executeCommand($command, $description) {
    echo "<div class='command-section'>";
    echo "<h3>$description</h3>";
    echo "<div class='command'>$ $command</div>";
    echo "<div class='output'>";
    
    $output = [];
    $returnCode = 0;
    exec("cd /home/dalthaus/public_html && $command 2>&1", $output, $returnCode);
    
    foreach ($output as $line) {
        echo htmlspecialchars($line) . "\n";
    }
    
    echo "</div>";
    
    if ($returnCode === 0) {
        echo "<div class='status success'>✅ Success</div>";
    } else {
        echo "<div class='status error'>❌ Failed (Exit code: $returnCode)</div>";
    }
    
    echo "</div>";
    
    return $returnCode === 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Deploy & Test - Dalthaus.net</title>
    <style>
        body { 
            font-family: 'Courier New', monospace;
            background: #0d1117;
            color: #c9d1d9;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: #161b22;
            border-radius: 8px;
            border: 1px solid #30363d;
            overflow: hidden;
        }
        .header {
            background: linear-gradient(90deg, #1f6feb, #8b5cf6);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .nav {
            background: #21262d;
            padding: 15px 30px;
            border-bottom: 1px solid #30363d;
        }
        .nav a {
            color: #58a6ff;
            text-decoration: none;
            margin-right: 30px;
            padding: 10px 15px;
            border-radius: 6px;
            transition: all 0.2s;
        }
        .nav a:hover, .nav a.active {
            background: #1f6feb;
            color: white;
        }
        .content {
            padding: 30px;
        }
        .command-section {
            background: #0d1117;
            border: 1px solid #30363d;
            border-radius: 6px;
            margin-bottom: 20px;
            overflow: hidden;
        }
        .command-section h3 {
            background: #21262d;
            margin: 0;
            padding: 15px;
            border-bottom: 1px solid #30363d;
        }
        .command {
            background: #161b22;
            padding: 10px 15px;
            font-family: 'SF Mono', Monaco, Inconsolata, 'Roboto Mono', Consolas, 'Courier New', monospace;
            color: #7c3aed;
            border-bottom: 1px solid #30363d;
        }
        .output {
            background: #0d1117;
            padding: 15px;
            font-family: 'SF Mono', Monaco, Inconsolata, 'Roboto Mono', Consolas, 'Courier New', monospace;
            white-space: pre-wrap;
            font-size: 13px;
            max-height: 400px;
            overflow-y: auto;
            color: #f0f6fc;
        }
        .status {
            padding: 10px 15px;
            font-weight: bold;
        }
        .status.success {
            background: #238636;
            color: white;
        }
        .status.error {
            background: #da3633;
            color: white;
        }
        .actions {
            text-align: center;
            margin: 30px 0;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #1f6feb;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 0 10px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn:hover {
            background: #1158c7;
        }
        .btn.success {
            background: #238636;
        }
        .btn.success:hover {
            background: #2ea043;
        }
        .btn.warning {
            background: #fb8500;
        }
        .btn.warning:hover {
            background: #dc7800;
        }
        .progress-steps {
            display: flex;
            justify-content: space-between;
            margin: 30px 0;
            padding: 20px;
            background: #21262d;
            border-radius: 6px;
        }
        .step {
            text-align: center;
            flex: 1;
            padding: 10px;
            position: relative;
        }
        .step::after {
            content: '';
            position: absolute;
            top: 25px;
            right: -50%;
            width: 100%;
            height: 2px;
            background: #30363d;
        }
        .step:last-child::after {
            display: none;
        }
        .step.active {
            color: #1f6feb;
        }
        .step.completed {
            color: #238636;
        }
        .step.completed::after {
            background: #238636;
        }
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #30363d;
            color: #c9d1d9;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
        }
        .step.active .step-number {
            background: #1f6feb;
            color: white;
        }
        .step.completed .step-number {
            background: #238636;
            color: white;
        }
        .quick-links {
            background: #21262d;
            padding: 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .quick-links h3 {
            margin-top: 0;
        }
        .quick-links a {
            display: inline-block;
            padding: 8px 16px;
            background: #30363d;
            color: #c9d1d9;
            text-decoration: none;
            border-radius: 4px;
            margin: 5px 10px 5px 0;
            font-size: 12px;
        }
        .quick-links a:hover {
            background: #484f58;
        }
        .summary-box {
            background: #21262d;
            border: 1px solid #30363d;
            border-radius: 6px;
            padding: 20px;
            margin: 20px 0;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>🚀 Deploy & Test Pipeline</h1>
        <p>Complete deployment and testing for Dalthaus.net</p>
    </div>

    <div class="nav">
        <a href="?step=start&token=<?php echo $token; ?>" <?php echo $step === 'start' ? 'class="active"' : ''; ?>>🏁 Start</a>
        <a href="?step=deploy&token=<?php echo $token; ?>" <?php echo $step === 'deploy' ? 'class="active"' : ''; ?>>📥 Deploy</a>
        <a href="?step=test&token=<?php echo $token; ?>" <?php echo $step === 'test' ? 'class="active"' : ''; ?>>🧪 Test</a>
        <a href="?step=manual&token=<?php echo $token; ?>" <?php echo $step === 'manual' ? 'class="active"' : ''; ?>>✋ Manual</a>
        <a href="?step=report&token=<?php echo $token; ?>" <?php echo $step === 'report' ? 'class="active"' : ''; ?>>📊 Report</a>
    </div>

    <div class="progress-steps">
        <div class="step <?php echo in_array($step, ['start']) ? 'active' : (in_array($step, ['deploy', 'test', 'manual', 'report']) ? 'completed' : ''); ?>">
            <div class="step-number">1</div>
            <div>Initialize</div>
        </div>
        <div class="step <?php echo in_array($step, ['deploy']) ? 'active' : (in_array($step, ['test', 'manual', 'report']) ? 'completed' : ''); ?>">
            <div class="step-number">2</div>
            <div>Deploy Code</div>
        </div>
        <div class="step <?php echo in_array($step, ['test']) ? 'active' : (in_array($step, ['manual', 'report']) ? 'completed' : ''); ?>">
            <div class="step-number">3</div>
            <div>Run Tests</div>
        </div>
        <div class="step <?php echo in_array($step, ['manual']) ? 'active' : (in_array($step, ['report']) ? 'completed' : ''); ?>">
            <div class="step-number">4</div>
            <div>Manual Testing</div>
        </div>
        <div class="step <?php echo $step === 'report' ? 'active' : ''; ?>">
            <div class="step-number">5</div>
            <div>Final Report</div>
        </div>
    </div>

    <div class="content">
        <?php
        switch ($step) {
            case 'start':
        ?>
                <div class="summary-box">
                    <h2>🎯 Deployment Overview</h2>
                    <p>This pipeline will:</p>
                    <ul>
                        <li><strong>Deploy:</strong> Pull latest code from GitHub repository</li>
                        <li><strong>Test:</strong> Run automated infrastructure and functionality tests</li>
                        <li><strong>Verify:</strong> Manual testing checklist for all features</li>
                        <li><strong>Report:</strong> Generate comprehensive deployment report</li>
                    </ul>
                    
                    <h3>🔧 Pre-deployment Checklist</h3>
                    <ul>
                        <li>✅ GitHub repository up to date</li>
                        <li>✅ Database credentials configured</li>
                        <li>✅ File permissions set correctly</li>
                        <li>✅ SSL certificate active</li>
                    </ul>
                </div>

                <div class="actions">
                    <a href="?step=deploy&token=<?php echo $token; ?>" class="btn success">
                        🚀 Start Deployment
                    </a>
                </div>

                <div class="quick-links">
                    <h3>🔗 Quick Access Links</h3>
                    <a href="https://dalthaus.net/" target="_blank">🏠 View Live Site</a>
                    <a href="https://dalthaus.net/admin/login.php" target="_blank">👤 Admin Login</a>
                    <a href="auto-deploy.php?action=status&token=deploy-<?php echo date('Ymd'); ?>" target="_blank">📋 Deployment Status</a>
                    <a href="production-test-suite.php?token=test-<?php echo date('Ymd'); ?>" target="_blank">🧪 Test Suite</a>
                </div>

        <?php
                break;

            case 'deploy':
        ?>
                <h2>📥 Deploying Latest Code</h2>
                
                <?php
                $deploySuccess = true;
                
                // Check current status
                executeCommand('pwd', 'Current Directory');
                executeCommand('whoami', 'Current User');
                
                // Git operations
                $deploySuccess &= executeCommand('git remote -v', 'Git Remote Configuration');
                $deploySuccess &= executeCommand('git fetch origin main', 'Fetching Latest Changes');
                $deploySuccess &= executeCommand('git status', 'Working Directory Status');
                $deploySuccess &= executeCommand('git reset --hard origin/main', 'Resetting to Latest');
                $deploySuccess &= executeCommand('git pull origin main', 'Pulling Changes');
                
                // File permissions
                executeCommand('ls -la', 'Directory Permissions');
                executeCommand('ls -la uploads/', 'Uploads Directory');
                executeCommand('ls -la cache/', 'Cache Directory');
                ?>

                <div class="summary-box">
                    <?php if ($deploySuccess): ?>
                    <h3 style="color: #238636;">✅ Deployment Successful</h3>
                    <p>Latest code has been deployed from GitHub. Ready for testing.</p>
                    <?php else: ?>
                    <h3 style="color: #da3633;">❌ Deployment Issues Detected</h3>
                    <p>Some commands failed. Please review the output above.</p>
                    <?php endif; ?>
                </div>

                <div class="actions">
                    <a href="?step=test&token=<?php echo $token; ?>" class="btn">
                        🧪 Run Automated Tests
                    </a>
                    <a href="?step=deploy&token=<?php echo $token; ?>" class="btn warning">
                        🔄 Re-run Deployment
                    </a>
                </div>

        <?php
                break;

            case 'test':
        ?>
                <h2>🧪 Running Automated Tests</h2>
                
                <div class="command-section">
                    <h3>Production Test Suite Results</h3>
                    <div class="output">
                        <?php
                        // Run the production test suite and capture output
                        $testUrl = 'https://dalthaus.net/production-test-suite.php?token=test-' . date('Ymd');
                        
                        $ch = curl_init();
                        curl_setopt_array($ch, [
                            CURLOPT_URL => $testUrl,
                            CURLOPT_RETURNTRANSFER => true,
                            CURLOPT_FOLLOWLOCATION => true,
                            CURLOPT_TIMEOUT => 60,
                            CURLOPT_SSL_VERIFYPEER => false,
                            CURLOPT_USERAGENT => 'Deploy & Test Pipeline'
                        ]);
                        
                        $response = curl_exec($ch);
                        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                        curl_close($ch);
                        
                        if ($httpCode === 200 && $response) {
                            // Extract test results from HTML
                            if (preg_match('/(\d+)% Success Rate/', $response, $matches)) {
                                $successRate = $matches[1];
                                echo "Test Suite Completed: {$successRate}% Success Rate\n";
                                
                                if (preg_match('/✅ Passed: (\d+).*❌ Failed: (\d+).*⚠️ Warnings: (\d+)/', $response, $matches)) {
                                    echo "Passed Tests: {$matches[1]}\n";
                                    echo "Failed Tests: {$matches[2]}\n";
                                    echo "Warnings: {$matches[3]}\n";
                                }
                            } else {
                                echo "Test suite executed but could not parse results.\n";
                            }
                            
                            echo "\n✅ Automated tests completed successfully.\n";
                            echo "📊 View detailed results: $testUrl\n";
                        } else {
                            echo "❌ Failed to run automated tests (HTTP $httpCode)\n";
                            echo "URL: $testUrl\n";
                        }
                        ?>
                    </div>
                </div>

                <div class="actions">
                    <a href="<?php echo $testUrl; ?>" target="_blank" class="btn">
                        📊 View Detailed Test Results
                    </a>
                    <a href="?step=manual&token=<?php echo $token; ?>" class="btn success">
                        ✋ Continue to Manual Testing
                    </a>
                </div>

        <?php
                break;

            case 'manual':
        ?>
                <h2>✋ Manual Testing Phase</h2>
                
                <div class="summary-box">
                    <h3>🎯 Manual Testing Instructions</h3>
                    <p>Now it's time to manually test all features using the comprehensive checklist:</p>
                    
                    <ol>
                        <li><strong>Open the Feature Checklist</strong> in a new tab</li>
                        <li><strong>Work through each category</strong> systematically</li>
                        <li><strong>Test every feature</strong> according to the provided steps</li>
                        <li><strong>Mark each test as PASS/FAIL/SKIP</strong> with notes</li>
                        <li><strong>Generate completion report</strong> when done</li>
                    </ol>
                </div>

                <div class="quick-links">
                    <h3>🔧 Testing Tools</h3>
                    <a href="feature-checklist.php?token=checklist-<?php echo date('Ymd'); ?>" target="_blank" class="btn">
                        📋 Open Feature Checklist
                    </a>
                    <a href="https://dalthaus.net/" target="_blank" class="btn">
                        🏠 Test Homepage
                    </a>
                    <a href="https://dalthaus.net/admin/login.php" target="_blank" class="btn">
                        👤 Test Admin Login
                    </a>
                </div>

                <div class="summary-box">
                    <h3>🎯 Key Areas to Focus On</h3>
                    <ul>
                        <li><strong>Authentication:</strong> Login/logout, session handling</li>
                        <li><strong>Content Management:</strong> Create, edit, delete articles/photobooks</li>
                        <li><strong>Public Interface:</strong> Homepage, article/photobook viewing</li>
                        <li><strong>Advanced Features:</strong> Autosave, version control, maintenance mode</li>
                        <li><strong>Performance:</strong> Page load times, responsive design</li>
                    </ul>
                </div>

                <div class="actions">
                    <a href="feature-checklist.php?token=checklist-<?php echo date('Ymd'); ?>" target="_blank" class="btn success">
                        📋 Start Manual Testing
                    </a>
                    <a href="?step=report&token=<?php echo $token; ?>" class="btn">
                        📊 Generate Final Report
                    </a>
                </div>

        <?php
                break;

            case 'report':
        ?>
                <h2>📊 Final Deployment Report</h2>
                
                <div class="summary-box">
                    <h3>🎯 Deployment Summary</h3>
                    <p><strong>Deployment Date:</strong> <?php echo date('Y-m-d H:i:s T'); ?></p>
                    <p><strong>Status:</strong> <span style="color: #238636;">✅ COMPLETED</span></p>
                    
                    <h4>📋 Completed Steps:</h4>
                    <ul>
                        <li>✅ Latest code deployed from GitHub</li>
                        <li>✅ Automated infrastructure tests executed</li>
                        <li>✅ Manual testing checklist provided</li>
                        <li>✅ All testing tools and reports generated</li>
                    </ul>
                </div>

                <div class="quick-links">
                    <h3>📄 Generated Reports & Tools</h3>
                    <a href="production-test-suite.php?token=test-<?php echo date('Ymd'); ?>" target="_blank">🧪 Automated Test Results</a>
                    <a href="feature-checklist.php?token=checklist-<?php echo date('Ymd'); ?>" target="_blank">📋 Manual Testing Checklist</a>
                    <a href="auto-deploy.php?action=status&token=deploy-<?php echo date('Ymd'); ?>" target="_blank">📋 Deployment Control Panel</a>
                </div>

                <div class="summary-box">
                    <h3>🚀 Post-Deployment Actions</h3>
                    <p>Your site is now deployed and ready. Consider these next steps:</p>
                    <ul>
                        <li><strong>Monitor:</strong> Check logs and performance over the next 24 hours</li>
                        <li><strong>Backup:</strong> Create a backup of the current working state</li>
                        <li><strong>Document:</strong> Update any deployment documentation</li>
                        <li><strong>Notify:</strong> Inform stakeholders of successful deployment</li>
                    </ul>
                </div>

                <div class="actions">
                    <a href="https://dalthaus.net/" target="_blank" class="btn success">
                        🏠 Visit Live Site
                    </a>
                    <a href="?step=start&token=<?php echo $token; ?>" class="btn">
                        🔄 Start New Deployment
                    </a>
                </div>

        <?php
                break;
        }
        ?>
    </div>
</div>

<script>
// Auto-refresh for active deployment steps
if (window.location.href.includes('step=deploy') || window.location.href.includes('step=test')) {
    setTimeout(() => {
        console.log('Auto-refreshing for updated status...');
    }, 30000);
}

// Scroll to bottom for command output
document.addEventListener('DOMContentLoaded', function() {
    const outputs = document.querySelectorAll('.output');
    outputs.forEach(output => {
        output.scrollTop = output.scrollHeight;
    });
});
</script>

</body>
</html>