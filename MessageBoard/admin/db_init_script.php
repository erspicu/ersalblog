<?php
/**
 * Internal DB Init Script for Admin Setup
 */
// 這裡不需要 require config.php，因為 setup.php 已經處理好變數了
if (!isset($mb_sqlite_path)) {
    require_once __DIR__ . '/../config/config.php';
}

$dbDir = dirname($mb_sqlite_path);
if (!is_dir($dbDir)) mkdir($dbDir, 0777, true);

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
