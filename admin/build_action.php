<?php
require_once 'auth.php';
require_once 'data_provider.php';
requireLogin();

// 驗證 CSRF (暫略，AJAX 請求可後續補強)

$baseDir = dirname(__DIR__);
require_once $baseDir . '/PHP_LIB/StaticGenerator.php';

// 獲取設定
$forceAll = isset($_POST['forceAll']) && $_POST['forceAll'] === 'on';
$updateJson = isset($_POST['updateJson']) && $_POST['updateJson'] === 'on';
$forceGlobal = isset($_POST['forceGlobal']) && $_POST['forceGlobal'] === 'on';
$selectedPostsRaw = isset($_POST['selected_posts']) ? $_POST['selected_posts'] : null;
$selectedPosts = $selectedPostsRaw ? json_decode($selectedPostsRaw, true) : array();

// 準備語系與設定
global $blog_lang;
if (!isset($blog_lang)) $blog_lang = 'zh_TW'; 
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

$generator = new \PHPLib\StaticGenerator($baseDir, $langVars, $genConfig, true); // Verbose mode for AJAX log

echo "[INFO] Start Building...<br>
";

try {
    if (!empty($selectedPosts)) {
        echo "[INFO] Building selected " . count($selectedPosts) . " posts...<br>
";
        
        $count = 0;
        foreach ($selectedPosts as $filename) {
            // 對於特定文章建置，我們通常希望它強制重生
            // build($force, $jsonMode, $forceGlobal, $forceIndex, $langFile, $targetFilename)
            // 第一篇更新 JSON，後續如果不一定要每篇都更也可以優化，但這裡先每篇都跑一次完整邏輯確保正確性
            // 為了效能，我們讓最後一篇才更新 JSON API，或者只在第一篇更新
            $isLast = ($count === count($selectedPosts) - 1);
            $generator->build($forceAll, ($updateJson && $isLast), $forceGlobal, false, '', $filename);
            $count++;
        }
        
        // 如果選了文章但沒選 JSON，或是中間過程沒跑過 JSON，這裡補跑一次全域頁面確保列表正確
        if ($forceGlobal || $updateJson) {
             echo "[INFO] Finalizing global pages...<br>
";
             // build($force, $jsonMode, $forceGlobal, $forceIndex, $langFile, $targetFilename)
             $generator->build(false, $updateJson, $forceGlobal, false, '', null);
        }

    } else {
        echo "[INFO] Full scanning build (forceAll=" . ($forceAll ? 'true' : 'false') . ")...<br>
";
        // build($force, $jsonMode, $forceGlobal, $forceIndex, $langFile, $targetFilename)
        $generator->build($forceAll, $updateJson, $forceGlobal, false, '', null);
    }
    
    echo "<br>
[SUCCESS] " . __('build_complete');

} catch (Exception $e) {
    echo "<br>
[ERROR] " . $e->getMessage();
} catch (Throwable $e) {
    echo "<br>
[FATAL ERROR] " . $e->getMessage();
}
?>
