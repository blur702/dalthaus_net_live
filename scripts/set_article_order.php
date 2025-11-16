<?php
/**
 * Set Article Order Script
 *
 * Updates the sort_order for articles to match the desired sequence.
 * This preserves the UI reordering functionality while setting a specific initial order.
 */

declare(strict_types=1);

// Include config
$config = require __DIR__ . '/../config/config.php';

// Database connection
try {
    $dsn = "mysql:host={$config['database']['host']};dbname={$config['database']['dbname']};charset={$config['database']['charset']}";
    $pdo = new PDO($dsn, $config['database']['username'], $config['database']['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);

    echo "✅ Database connected successfully\n\n";
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage() . "\n");
}

// Desired article order (title => position)
$desiredOrder = [
    "The Future of Photography: Between Image and Language" => 1,
    "Telling the Subject's Story As Completely as Possible" => 2,
    "The Key Is Writing Stories People Want To Read" => 3,
    "The Organizing Questions - What My Story Is About" => 4,
    "Getting To The Practical Details Of Creating Your Story" => 5,
    "The Case for \"Pure Photography\"" => 6,
    "The Joy Of Getting It Right In the Camera" => 7,
    "You Really Don't Need To Master Manual Mode" => 8,
    "How I Learned To Stop Worrying And Love JPEG" => 9,
    "Making Adjustment Easier On You And Your Sanity" => 10,
    "The Smartphone Camera is Perfect For Storytelling" => 11,
    "Can I Shoot (fill in the blank) With My Phone?" => 12,
    "Photography's New Paradigm" => 13,
    "How Smartphone Cameras Are Redefining Photography" => 14,
    "Backgrounder – Smartphone Camera Image Signal Processing / Neural Engine Processing – Apple iPhone 15/16 Pro /Pro Max, Google 8 / 9 Pro / Pro XL" => 15,
    "How I Learned To Stop Worrying – Understanding AI Imaging" => 16,
    "How I Learned To Stop Worrying – Protecting Your Work From AI" => 17
];

echo "Fetching all articles from database...\n";

// Get all articles
$stmt = $pdo->prepare("
    SELECT content_id, title, sort_order
    FROM content
    WHERE content_type = 'article'
    ORDER BY sort_order ASC
");
$stmt->execute();
$articles = $stmt->fetchAll();

echo "Found " . count($articles) . " articles\n\n";

// Show current order
echo "CURRENT ORDER:\n";
echo str_repeat("=", 80) . "\n";
foreach ($articles as $article) {
    echo sprintf("%3d. [ID:%3d] %s\n",
        $article['sort_order'],
        $article['content_id'],
        $article['title']
    );
}
echo "\n";

// Match articles to desired order
$updates = [];
$notFound = [];

foreach ($desiredOrder as $title => $position) {
    $found = false;
    foreach ($articles as $article) {
        // Try exact match first
        if ($article['title'] === $title) {
            $updates[] = [
                'id' => $article['content_id'],
                'title' => $article['title'],
                'old_position' => $article['sort_order'],
                'new_position' => $position
            ];
            $found = true;
            break;
        }

        // Try fuzzy match (without special characters)
        $cleanDesired = preg_replace('/[^\w\s]/u', '', $title);
        $cleanActual = preg_replace('/[^\w\s]/u', '', $article['title']);

        if ($cleanDesired === $cleanActual) {
            $updates[] = [
                'id' => $article['content_id'],
                'title' => $article['title'],
                'old_position' => $article['sort_order'],
                'new_position' => $position
            ];
            $found = true;
            break;
        }
    }

    if (!$found) {
        $notFound[] = $title;
    }
}

if (!empty($notFound)) {
    echo "⚠️  WARNING: Could not find these articles:\n";
    foreach ($notFound as $title) {
        echo "  - $title\n";
    }
    echo "\n";
}

echo "PROPOSED CHANGES:\n";
echo str_repeat("=", 80) . "\n";
foreach ($updates as $update) {
    $arrow = $update['old_position'] === $update['new_position'] ? '=' : '->';
    echo sprintf("%3d %2s %3d [ID:%3d] %s\n",
        $update['old_position'],
        $arrow,
        $update['new_position'],
        $update['id'],
        $update['title']
    );
}
echo "\n";

// Confirm before executing
echo "Do you want to apply these changes? (yes/no): ";
$handle = fopen("php://stdin", "r");
$confirmation = trim(fgets($handle));
fclose($handle);

if (strtolower($confirmation) !== 'yes') {
    echo "❌ Aborted. No changes made.\n";
    exit(0);
}

// Execute updates
echo "\nApplying changes...\n";

$pdo->beginTransaction();

try {
    $updateStmt = $pdo->prepare("UPDATE content SET sort_order = ? WHERE content_id = ?");

    foreach ($updates as $update) {
        $updateStmt->execute([$update['new_position'], $update['id']]);
        echo "✅ Updated ID {$update['id']}: position {$update['old_position']} -> {$update['new_position']}\n";
    }

    $pdo->commit();
    echo "\n✅ All changes committed successfully!\n\n";

    // Show new order
    echo "NEW ORDER:\n";
    echo str_repeat("=", 80) . "\n";

    $stmt = $pdo->prepare("
        SELECT content_id, title, sort_order
        FROM content
        WHERE content_type = 'article'
        ORDER BY sort_order ASC
    ");
    $stmt->execute();
    $newArticles = $stmt->fetchAll();

    foreach ($newArticles as $article) {
        echo sprintf("%3d. [ID:%3d] %s\n",
            $article['sort_order'],
            $article['content_id'],
            $article['title']
        );
    }

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "❌ Error updating articles: " . $e->getMessage() . "\n";
    exit(1);
}
