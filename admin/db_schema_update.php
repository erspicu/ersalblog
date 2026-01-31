<?php
// admin/db_schema_update.php
require_once 'auth.php';
require_once 'data_provider.php';
requireLogin();

// Only for DB mode
$dataManager = new DataManager();
if ($dataManager->getSource() !== 'db') {
    die("請切換至資料庫模式以執行此更新。");
}

global $pdo;

$log = [];

function addLog($msg) {
    global $log;
    $log[] = $msg;
}

try {
    $pdo->beginTransaction();

    // 1. Create Categories Table
    addLog("正在建立 `blog_categories` 資料表...");
    $sql = "CREATE TABLE IF NOT EXISTS `blog_categories` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `category_name` VARCHAR(190) NOT NULL UNIQUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql);

    // 2. Create Pivot Table
    addLog("正在建立 `blog_post_categories` 關聯表...");
    $sql = "CREATE TABLE IF NOT EXISTS `blog_post_categories` (
        `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        `post_id` INT UNSIGNED NOT NULL,
        `category_id` INT UNSIGNED NOT NULL,
        FOREIGN KEY (`post_id`) REFERENCES `blog_posts`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`category_id`) REFERENCES `blog_categories`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `unique_post_cat` (`post_id`, `category_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    $pdo->exec($sql);

    // 3. Migrate Data
    addLog("正在遷移現有分類資料...");
    $stmt = $pdo->query("SELECT id, post_categories FROM blog_posts");
    while ($row = $stmt->fetch()) {
        $postId = $row['id'];
        $catsStr = $row['post_categories'];
        if (empty(trim($catsStr))) continue;

        $cats = explode(',', $catsStr);
        foreach ($cats as $catName) {
            $catName = trim($catName);
            if ($catName === '') continue;

            // Ensure Category Exists
            $checkCat = $pdo->prepare("SELECT id FROM blog_categories WHERE category_name = ?");
            $checkCat->execute([$catName]);
            $catId = $checkCat->fetchColumn();

            if (!$catId) {
                $insCat = $pdo->prepare("INSERT INTO blog_categories (category_name) VALUES (?)");
                $insCat->execute([$catName]);
                $catId = $pdo->lastInsertId();
            }

            // Create Relation
            // Use INSERT IGNORE to avoid duplicates if re-running
            $insRel = $pdo->prepare("INSERT IGNORE INTO blog_post_categories (post_id, category_id) VALUES (?, ?)");
            $insRel->execute([$postId, $catId]);
        }
    }

    $pdo->commit();
    addLog("資料庫架構更新與資料遷移成功！");

} catch (Exception $e) {
    $pdo->rollBack();
    addLog("錯誤: " . $e->getMessage());
}

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>DB Schema Update</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <h3>資料庫更新日誌</h3>
    <ul class="list-group">
        <?php foreach ($log as $l): ?>
            <li class="list-group-item"><?php echo htmlspecialchars($l); ?></li>
        <?php endforeach; ?>
    </ul>
    <a href="index.php" class="btn btn-primary mt-3">回後台首頁</a>
</body>
</html>