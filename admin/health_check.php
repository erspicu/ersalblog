<?php
// admin/health_check.php
require_once __DIR__ . '/../config.php';

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
                $result['message'] = "連線成功 (文章數: $count)";
            } else {
                $result['message'] = "連線成功，但找不到資料表 'blog_posts'";
            }

        } catch (PDOException $e) {
            $result['message'] = "連線失敗: " . $e->getMessage();
        }

        return $result;
    }

    public static function checkFile() {
        $baseDir = dirname(__DIR__);
        $result = [
            'status' => true,
            'message' => '檔案結構完整'
        ];
        $errors = [];

        if (!file_exists($baseDir . '/contents/index_post.txt')) {
            $errors[] = '缺少 contents/index_post.txt';
        }
        if (!is_dir($baseDir . '/contents/post_files')) {
            $errors[] = '缺少 contents/post_files 目錄';
        }
        if (!is_dir($baseDir . '/category')) {
            $errors[] = '缺少 category 目錄';
        }

        if (!empty($errors)) {
            $result['status'] = false;
            $result['message'] = implode('; ', $errors);
        }

        return $result;
    }
}
?>