<?php
require_once 'auth.php';
require_once 'data_provider.php';
requireLogin();

// 驗證 CSRF Token
validateCSRFRequest();

$dataManager = new DataManager();

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
if (substr($filename, -5) !== '.html' && substr($filename, -4) !== '.htm') {
    $filename .= '.html';
}


try {
    $saveData = [
        'id' => $id,
        'title' => $title,
        'filename' => $filename,
        'date' => $date,
        'content' => $content,
        'tags' => $tags,
        'categories' => $categoriesStr,
        'desc' => $desc
    ];

    $dataManager->savePost($saveData);
    
    // 判斷是新增還是修改
    $msg = $id ? 'updated' : 'created';

    // 成功後轉導回列表
    header("Location: posts.php?msg=$msg");
    exit;

} catch (Exception $e) {
    die("儲存失敗: " . $e->getMessage());
}
?>
