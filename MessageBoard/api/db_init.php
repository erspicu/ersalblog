<?php
/**
 * MessageBoard Database Init - 獨立脫鉤版
 */
require_once __DIR__ . '/../config/config.php';

try {
    $dbDir = dirname($mb_sqlite_path);
    if (!is_dir($dbDir)) {
        mkdir($dbDir, 0777, true);
    }

    $pdo = new PDO("sqlite:" . $mb_sqlite_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "CREATE TABLE IF NOT EXISTS guestbook_messages (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        page_id TEXT NOT NULL,
        parent_id INTEGER DEFAULT 0,
        name TEXT NOT NULL,
        email TEXT,
        content TEXT NOT NULL,
        created_at DATETIME DEFAULT (datetime('now','localtime')),
        is_admin INTEGER DEFAULT 0,
        status INTEGER DEFAULT 1
    );";

    $pdo->exec($sql);
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_page_id ON guestbook_messages(page_id);");

    echo "MessageBoard database initialized at: " . realpath($mb_sqlite_path);
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
