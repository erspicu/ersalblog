<?php
// admin/db_drop_column.php
require_once 'auth.php';
require_once 'data_provider.php';
requireLogin();

// Only for DB mode
$dataManager = new DataManager();
if ($dataManager->getSource() !== 'db') {
    die("請切換至資料庫模式以執行此更新。");
}

global $pdo;

$log = array();

function addLog($msg) {
    global $log;
    $log[] = $msg;
}

if (isset($_POST['confirm_drop'])) {
    try {
        $pdo->beginTransaction();

        // Check if column exists
        addLog("檢查 `blog_posts` 資料表欄位...");
        $stmt = $pdo->query("SHOW COLUMNS FROM `blog_posts` LIKE 'post_categories'");
        if ($stmt->rowCount() > 0) {
            addLog("發現 `post_categories` 欄位，正在執行刪除...");
            $pdo->exec("ALTER TABLE `blog_posts` DROP COLUMN `post_categories`");
            addLog("欄位刪除成功！");
        } else {
            addLog("欄位 `post_categories` 已不存在。");
        }

        $pdo->commit();

    } catch (Exception $e) {
        $pdo->rollBack();
        addLog("錯誤: " . $e->getMessage());
    }
}

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <title>DB Cleanup</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-5">
    <h3>資料庫清理工具</h3>
    <div class="alert alert-warning">
        此操作將從 <code>blog_posts</code> 資料表中永久刪除 <code>post_categories</code> 欄位。<br>
        請確認您已經執行過 <code>db_schema_update.php</code> 並且資料已正確遷移至新的分類資料表。
    </div>

    <?php if (!empty($log)): ?>
        <ul class="list-group mb-3">
            <?php foreach ($log as $l): ?>
                <li class="list-group-item"><?php echo htmlspecialchars($l); ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="confirm_drop" value="1">
        <button type="submit" class="btn btn-danger" onclick="return confirm('確定要刪除嗎？此動作無法復原！');">
            確認刪除 post_categories 欄位
        </button>
        <a href="index.php" class="btn btn-secondary">回後台首頁</a>
    </form>
</body>
</html>