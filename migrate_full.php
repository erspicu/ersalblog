<?php
/**
 * Migration Tool (Full)
 * 環境: PHP 8.x
 * 功能: 初始化資料庫結構 + 匯入檔案系統資料 (All-in-One)
 */

// ==========================================
// 1. 設定區域 (Configuration)
// ==========================================
require_once 'config.php';
$db_config = $dbConfig;

$paths = [
    'index_file'   => __DIR__ . '/contents/index_post.txt',
    'category_dir' => __DIR__ . '/category',
    'content_dirs' => [
        __DIR__ . '/contents/post_files',
        __DIR__
    ]
];

// 設定環境以支援即時輸出
set_time_limit(600); // 10分鐘
if (function_exists('apache_setenv')) {
    @apache_setenv('no-gzip', 1);
}
@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

// ==========================================
// 2. 輔助函式
// ==========================================
function output_log($msg, $type = 'info') {
    $color = match ($type) {
        'success' => '#2ecc71', // Green
        'error'   => '#e74c3c', // Red
        'warning' => '#f39c12', // Orange
        'system'  => '#3498db', // Blue
        default   => '#bdc3c7'  // Grey
    };
    $icon = match ($type) {
        'success' => '✅',
        'error'   => '❌',
        'warning' => '⚠️',
        'system'  => '⚙️',
        default   => '📝'
    };
    
    // 輸出 HTML 並強制 Flush
    echo "<div class='log-item' style='border-left: 4px solid $color;'>
            <span class='icon'>$icon</span>
            <span class='msg'>$msg</span>
          </div>";
    
    // 讓瀏覽器捲動到底部
    echo "<script>
        var container = document.querySelector('.log-container');
        container.scrollTop = container.scrollHeight;
    </script>";
    
    flush();
}

function get_post_categories($filename, $category_base_dir) {
    $cats = [];
    if (!is_dir($category_base_dir)) return '';
    $dirs = glob($category_base_dir . '/*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $category_name = basename($dir);
        $target_path = $dir . '/' . $filename; 
        $target_path_no_ext = $dir . '/' . pathinfo($filename, PATHINFO_FILENAME);
        if (file_exists($target_path) || file_exists($target_path_no_ext)) {
            $cats[] = $category_name;
        }
    }
    return implode(',', $cats);
}

function get_post_content($filename, $search_dirs) {
    foreach ($search_dirs as $dir) {
        $path = $dir . '/' . $filename;
        if (file_exists($path)) {
            return file_get_contents($path);
        }
    }
    return false;
}
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blog Database Migration Tool</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #2c3e50; color: #ecf0f1; margin: 0; padding: 20px; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { border-bottom: 2px solid #3498db; padding-bottom: 15px; margin-bottom: 30px; }
        .card { background: #34495e; padding: 20px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.3); margin-bottom: 20px; }
        .log-container { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; height: 400px; overflow-y: auto; font-family: 'Consolas', monospace; font-size: 14px; line-height: 1.5; border: 1px solid #444; }
        .log-item { padding: 5px 10px; margin-bottom: 5px; background: #2d2d2d; border-radius: 3px; display: flex; align-items: center; }
        .log-item .icon { margin-right: 12px; min-width: 25px; text-align: center; }
        .progress-bar-container { width: 100%; background-color: #7f8c8d; border-radius: 4px; overflow: hidden; height: 25px; margin-bottom: 20px; }
        .progress-fill { height: 100%; background-color: #2ecc71; width: 0%; transition: width 0.3s; text-align: center; color: white; font-weight: bold; font-size: 14px; line-height: 25px; text-shadow: 1px 1px 2px rgba(0,0,0,0.5); }
        .stats { display: flex; justify-content: space-around; margin-top: 20px; }
        .stat-box { text-align: center; background: #2c3e50; padding: 10px; border-radius: 5px; min-width: 100px; }
        .stat-num { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
        .btn { display: inline-block; padding: 12px 30px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; margin-top: 20px; font-weight: bold; transition: background 0.2s; }
        .btn:hover { background: #2980b9; }
        .hidden { display: none; }
    </style>
</head>
<body>

<div class="container">
    <h1>🚀 Blog 資料庫一鍵遷移工具</h1>

    <div class="card">
        <h3>📊 執行進度</h3>
        <div class="progress-bar-container">
            <div id="p-bar" class="progress-fill">準備中...</div>
        </div>
        
        <div class="stats">
            <div class="stat-box">
                <div id="s-total" class="stat-num" style="color: #ecf0f1;">0</div>
                <small>總任務數</small>
            </div>
            <div class="stat-box">
                <div id="s-success" class="stat-num" style="color: #2ecc71;">0</div>
                <small>成功</small>
            </div>
            <div class="stat-box">
                <div id="s-fail" class="stat-num" style="color: #e74c3c;">0</div>
                <small>失敗/警告</small>
            </div>
        </div>
    </div>

    <div class="card">
        <h3>📜 系統日誌</h3>
        <div class="log-container">
            <?php
            // ==========================================
            // 3. 執行邏輯開始
            // ==========================================
            
            $pdo = null;

            // --- Stage 1: 初始化資料庫 ---
            output_log("--- 階段 1: 初始化資料庫結構 ---", 'system');

            try {
                // 1.1 連線到 Server
                output_log("正在連線到 MySQL Server...", 'system');
                $dsn_no_db = "mysql:host={$db_config['host']};charset={$db_config['charset']}";
                $pdo = new PDO($dsn_no_db, $db_config['username'], $db_config['password'], [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]);
                output_log("MySQL 連線成功!", 'success');

                // 1.2 建立資料庫
                output_log("檢查資料庫 '{$db_config['dbname']}'...", 'system');
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$db_config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $pdo->exec("USE `{$db_config['dbname']}`");
                output_log("資料庫準備就緒。", 'success');

                // 1.3 建立資料表
                output_log("檢查/建立資料表 'blog_posts'...", 'system');
                $table_sql = "
                CREATE TABLE IF NOT EXISTS `blog_posts` (
                    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    `post_filename` VARCHAR(190) NOT NULL COMMENT '唯一檔名',
                    `post_title` VARCHAR(255) NOT NULL,
                    `post_date` DATETIME NOT NULL,
                    `post_tags` TEXT DEFAULT NULL,
                    `post_categories` TEXT DEFAULT NULL,
                    `post_description` TEXT DEFAULT NULL,
                    `post_content` LONGTEXT DEFAULT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY `unique_filename` (`post_filename`),
                    INDEX `idx_post_date` (`post_date`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
                
                $pdo->exec($table_sql);
                output_log("資料表結構驗證完成。", 'success');

            } catch (PDOException $e) {
                output_log("初始化失敗: " . $e->getMessage(), 'error');
                echo "</div></div></body></html>";
                exit; // 終止程式
            }

            // --- Stage 2: 資料匯入 ---
            echo "<br>";
            output_log("--- 階段 2: 開始資料匯入 ---", 'system');

            if (!file_exists($paths['index_file'])) {
                output_log("找不到索引檔: " . $paths['index_file'], 'error');
                exit;
            }

            $lines = file($paths['index_file'], FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $total_lines = count($lines);
            
            // 更新 UI 總數
            echo "<script>document.getElementById('s-total').innerText = '$total_lines';</script>";

            $success_count = 0;
            $fail_count = 0;

            // 準備 SQL
            $stmt = $pdo->prepare("
                INSERT INTO blog_posts 
                (post_filename, post_title, post_date, post_tags, post_categories, post_description, post_content, updated_at) 
                VALUES 
                (:filename, :title, :date, :tags, :cats, :desc, :content, NOW())
                ON DUPLICATE KEY UPDATE 
                post_title = VALUES(post_title),
                post_date = VALUES(post_date),
                post_tags = VALUES(post_tags),
                post_categories = VALUES(post_categories),
                post_description = VALUES(post_description),
                post_content = VALUES(post_content),
                updated_at = NOW()
            ");

            foreach ($lines as $index => $line) {
                $parts = explode('|', $line);
                
                if (count($parts) < 3) {
                    output_log("格式略過: $line", 'warning');
                    $fail_count++;
                    continue;
                }

                $post_date = trim($parts[0]);
                $post_filename = trim($parts[1]);
                $post_title = trim($parts[2]);
                $post_tags = isset($parts[3]) ? trim($parts[3]) : '';
                $post_desc = isset($parts[4]) ? trim($parts[4]) : '';

                // 取得分類與內容
                $post_cats = get_post_categories($post_filename, $paths['category_dir']);
                $content = get_post_content($post_filename, $paths['content_dirs']);
                
                if ($content === false) {
                    output_log("[$post_filename] 警告: 找不到 HTML 檔，內容將為空", 'warning');
                    $content = ''; 
                    // 這裡不算失敗，只是警告
                }

                // 執行 SQL
                try {
                    $stmt->execute([
                        ':filename' => $post_filename,
                        ':title'    => $post_title,
                        ':date'     => $post_date,
                        ':tags'     => $post_tags,
                        ':cats'     => $post_cats,
                        ':desc'     => $post_desc,
                        ':content'  => $content
                    ]);
                    
                    output_log("匯入: $post_title ($post_cats)", 'success');
                    $success_count++;

                } catch (PDOException $e) {
                    output_log("[$post_filename] DB 寫入錯誤: " . $e->getMessage(), 'error');
                    $fail_count++;
                }

                // 更新進度條
                $percent = round((($index + 1) / $total_lines) * 100);
                echo "<script>
                    document.getElementById('p-bar').style.width = '$percent%';
                    document.getElementById('p-bar').innerText = '$percent%';
                    document.getElementById('s-success').innerText = '$success_count';
                    document.getElementById('s-fail').innerText = '$fail_count';
                </script>";
                flush();
                
                // 稍微停頓讓 UI 比較平滑 (可選)
                // usleep(50000); 
            }
            
            output_log("--- 全部作業完成 ---", 'system');
            ?>
        </div>
        <div style="text-align: center;">
            <a href="blog.html" class="btn">🏠 返回首頁</a>
        </div>
    </div>
</div>

</body>
</html>
