<?php
require_once 'auth.php';
require_once 'data_provider.php';
requireLogin();

// 驗證 CSRF Token
validateCSRFRequest();

$dataManager = new DataManager();

$id = isset($_POST['id']) ? $_POST['id'] : '';
$title = isset($_POST['post_title']) ? $_POST['post_title'] : '未命名文章';
$filename = trim(isset($_POST['post_filename']) ? $_POST['post_filename'] : '');
$date = isset($_POST['post_date']) ? $_POST['post_date'] : date('Y-m-d H:i:s');
$content = isset($_POST['post_content']) ? $_POST['post_content'] : '';
$tags = isset($_POST['post_tags']) ? $_POST['post_tags'] : '';
$desc = isset($_POST['post_description']) ? $_POST['post_description'] : '';

// --- 處理分類 ---
$cats = isset($_POST['cats_check']) ? $_POST['cats_check'] : array(); // 陣列
$newCat = trim(isset($_POST['new_category']) ? $_POST['new_category'] : '');
if ($newCat) {
    $cats[] = $newCat;
}
// 去重複並轉為逗號分隔字串
$cats = array_unique($cats);
$categoriesStr = implode(',', $cats);

// --- 處理檔名 ---
$filename = trim($filename);

// 1. 移除可能的副檔名 (為了重新標準化)
$filename = str_ireplace(['.html.tmp', '.html', '.htm'], '', $filename);

// 2. 檢查並處理前綴
// 情況 A: 使用者輸入了 YYYY-MM-DD- 格式 -> 轉為 YYYYMMDD-
if (preg_match('/^(\d{4})-(\d{2})-(\d{2})-(.*)$/', $filename, $matches)) {
    $filename = $matches[1] . $matches[2] . $matches[3] . '-' . $matches[4];
} 
// 情況 B: 使用者已經輸入了 YYYYMMDD- 格式 -> 保持原樣 (不做事)
elseif (preg_match('/^\d{8}-/', $filename)) {
    // Do nothing, already has valid prefix
}
// 情況 C: 沒有日期前綴 -> 自動補上
else {
    // 嘗試從 post_date 解析，如果沒有則用今天
    $ts = strtotime($date);
    if (!$ts) $ts = time();
    
    // 如果使用者完全沒填檔名，就只用時間
    if (empty($filename)) {
        $filename = date('Ymd-His', $ts); 
    } else {
        // 使用者有填，補上前綴
        $filename = date('Ymd-', $ts) . $filename;
    }
}

// 3. 強制補回 .html 作為系統內部的標準參照 (Reference Filename)
// DataManager 會根據 is_draft 決定是否加上 .tmp
$filename .= '.html';

$isDraft = isset($_POST['is_draft']) && $_POST['is_draft'] == '1';

try {
    $saveData = array(
        'id' => $id,
        'title' => $title,
        'filename' => $filename,
        'date' => $date,
        'content' => $content,
        'tags' => $tags,
        'categories' => $categoriesStr,
        'desc' => $desc,
        'is_draft' => $isDraft
    );

    $dataManager->savePost($saveData);
    
    // --- Auto Build Static Pages ---
    if (isset($_POST['auto_build']) && $_POST['auto_build'] == '1') {
        // Load Global Config & Lang for Generator
        // Note: auth.php already includes config.php
        global $blog_lang;
        if (!isset($blog_lang)) $blog_lang = 'zh_TW'; 
        
        $baseDir = dirname(__DIR__);
        $langFile = $baseDir . "/langs/template-{$blog_lang}.php";
        if (!file_exists($langFile)) $langFile = $baseDir . "/langs/template-zh_TW.php";
        $langData = file_exists($langFile) ? require $langFile : array();
        
        $langVars = array();
        foreach ($langData as $k => $v) {
            $langVars["lang_{$k}"] = $v;
        }

        $genConfig = array(
            'blog_title' => $GLOBALS['blog_title'],
            'blog_description' => $GLOBALS['blog_description'],
            'blog_introduce' => $GLOBALS['blog_introduce'],
            'site_url' => $GLOBALS['site_url'],
            'blog_preview' => $GLOBALS['blog_preview']
        );

        require_once $baseDir . '/PHP_LIB/StaticGenerator.php';
        $generator = new \PHPLib\StaticGenerator($baseDir, $langVars, $genConfig, false); // False = Silent mode
        
        // Check if JSON API is enabled in config.js
        $configJs = file_get_contents($baseDir . '/config.js');
        $isJsonMode = (strpos($configJs, "api_type: 'json'") !== false);

        // Run Build (Force Global to ensure list pages update)
        $generator->build(false, $isJsonMode, true); 
    }

    // 判斷是新增還是修改
    $msg = $id ? 'updated' : 'created';

    // 成功後轉導回列表
    header("Location: posts.php?msg=$msg");
    exit;

} catch (Exception $e) {
    die("儲存失敗: " . $e->getMessage());
}
?>
