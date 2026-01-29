<?php
require_once 'auth.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid Request');
}

$id = $_POST['id'] ?? '';
$title = $_POST['post_title'] ?? '未命名文章';
$filename = trim($_POST['post_filename'] ?? '');
$date = $_POST['post_date'] ?? date('Y-m-d H:i:s');
$content = $_POST['post_content'] ?? '';
$tags = $_POST['post_tags'] ?? '';
$desc = $_POST['post_description'] ?? '';

// --- 處理分類 ---
$cats = $_POST['cats_check'] ?? []; // 陣列
$newCat = trim($_POST['new_category'] ?? '');
if ($newCat) {
    $cats[] = $newCat;
}
// 去重複並轉為逗號分隔字串
$cats = array_unique($cats);
$categoriesStr = implode(',', $cats);

// --- 處理檔名 ---
// 如果沒填檔名，自動生成: YYYYMMDDHHMMSS.html
if (empty($filename)) {
    // 嘗試從日期解析
    $ts = strtotime($date);
    if (!$ts) $ts = time();
    $filename = date('YmdHis', $ts) . '.html';
}
// 確保有 .html 結尾 (若使用者忘了打)
if (!str_ends_with($filename, '.html') && !str_ends_with($filename, '.htm')) {
    $filename .= '.html';
}


try {
    if ($id) {
        // --- 更新模式 ---
        $sql = "UPDATE blog_posts SET 
                post_title = ?, 
                post_filename = ?, 
                post_date = ?, 
                post_content = ?, 
                post_tags = ?, 
                post_categories = ?, 
                post_description = ?,
                updated_at = NOW()
                WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $filename, $date, $content, $tags, $categoriesStr, $desc, $id]);
        $msg = "updated";
    } else {
        // --- 新增模式 ---
        // 檢查檔名是否重複
        $check = $pdo->prepare("SELECT id FROM blog_posts WHERE post_filename = ?");
        $check->execute([$filename]);
        if ($check->rowCount() > 0) {
            // 若重複，加個亂數後綴
            $filename = str_replace('.html', '-' . rand(100,999) . '.html', $filename);
        }

        $sql = "INSERT INTO blog_posts 
                (post_title, post_filename, post_date, post_content, post_tags, post_categories, post_description, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$title, $filename, $date, $content, $tags, $categoriesStr, $desc]);
        $msg = "created";
    }

    // 成功後轉導回列表
    header("Location: posts.php?msg=$msg");
    exit;

} catch (PDOException $e) {
    die("儲存失敗: " . $e->getMessage());
}
?>
