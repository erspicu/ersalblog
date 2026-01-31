<?php
// admin/health_check.php
require_once __DIR__ . '/../config.php';

// health_check.php 可能在沒有語系環境下執行 (例如直接存取測試)，
// 但通常透過 login.php 或 tool_migrate.php (都已載入 auth -> lang_init)。
// 為了防呆，若沒有定義 __() 函式，提供一個 fallback。
if (!function_exists('__')) {
    function __($key) { return $key; }
}

class SystemHealth {
    
    public static function checkDB() {
        global $dbConfig;
        $result = [
            'status' => false,
            'message' => ''
        ];

        try {
            $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
            $pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
            
            // Check table existence
            $stmt = $pdo->query("SHOW TABLES LIKE 'blog_posts'");
            if ($stmt->rowCount() > 0) {
                // Check content
                $countStmt = $pdo->query("SELECT COUNT(*) FROM blog_posts");
                $count = $countStmt->fetchColumn();
                $result['status'] = true;
                $result['message'] = sprintf(__('health_db_connected'), $count);
            } else {
                $result['message'] = __('health_db_table_missing');
            }

        } catch (PDOException $e) {
            $result['message'] = sprintf(__('health_db_conn_fail'), $e->getMessage());
        }

        return $result;
    }

    public static function checkFile() {
        $baseDir = dirname(__DIR__);
        $result = [
            'status' => true,
            'message' => __('health_file_ok')
        ];
        $errors = [];

        if (!file_exists($baseDir . '/contents/index_post.txt')) {
            $errors[] = __('health_file_missing_index');
        }
        if (!is_dir($baseDir . '/contents/post_files')) {
            $errors[] = __('health_file_missing_posts');
        }
        if (!is_dir($baseDir . '/category')) {
            $errors[] = __('health_file_missing_cat');
        }

        if (!empty($errors)) {
            $result['status'] = false;
            $result['message'] = implode('; ', $errors);
        }

        return $result;
    }
}
?>