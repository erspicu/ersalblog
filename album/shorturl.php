<?php
/**
 * Baxermux Album ShortURL Service
 * 處理雙次 Base62 混淆後的短網址導向與檔案回傳
 */

/**
 * Base62 解碼 (支援大數)
 */
function base62_decode($str) {
    $charset = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $base = 62;
    $val = 0; // PHP 在 64 位元系統下整數足夠大
    $len = strlen($str);
    for ($i = 0; $i < $len; $i++) {
        $pos = strpos($charset, $str[$i]);
        if ($pos === false) return false;
        $val = ($val * $base) + $pos;
    }
    return $val;
}

/**
 * 模乘雜湊還原邏輯
 * Base62 -> Integer -> XOR -> (Int * InvPrime) % Mod -> ID
 */
function getDeobfuscatedId($slug) {
    // 參數設定 (必須與 JS 端完全一致)
    $MOD = 2147483648; // 2^31
    // $PRIME = 1580030173;
    $INV_PRIME = 59260789; // Prime 的模反元素
    $MASK = 87369521;

    // 1. Base62 解碼
    $n = base62_decode($slug);
    if ($n === false) return false;

    // 2. 解除混淆: XOR Mask
    $n = $n ^ $MASK;

    // 3. 解除擴散: (N * InvPrime) % Mod
    // 使用 bcmath 或 float 確保運算過程中不溢位 (雖然 2^31 在 64bit int 沒問題，但保險起見)
    // 這裡我們假設 PHP 運行在 64-bit 環境，或者數值在 32-bit 有符號整數範圍內會自動轉 float
    // 為了精確的模運算，我們使用 fmod
    
    $res = fmod((float)$n * $INV_PRIME, $MOD);
    return (int)$res;
}

// 獲取參數
$i = isset($_GET['i']) ? $_GET['i'] : '';
if (empty($i)) {
    header("HTTP/1.1 400 Bad Request");
    die("Missing ID");
}

// 執行還原
$targetId = getDeobfuscatedId($i);
if ($targetId === false) {
    header("HTTP/1.1 400 Bad Request");
    die("Invalid ID format");
}

// 反查 shorturl.txt
$txtFile = __DIR__ . '/shorturl.txt';
if (!file_exists($txtFile)) {
    header("HTTP/1.1 500 Internal Server Error");
    die("Database file not found");
}

$foundRelativePath = null;
$handle = fopen($txtFile, "r");
if ($handle) {
    while (($line = fgets($handle)) !== false) {
        $parts = explode('|', trim($line));
        if (count($parts) >= 2) {
            if ((int)$parts[0] === (int)$targetId) {
                $foundRelativePath = $parts[1];
                break;
            }
        }
    }
    fclose($handle);
}

// 檔案回傳
if ($foundRelativePath) {
    $fullPath = __DIR__ . '/Collection/' . $foundRelativePath;
    
    if (file_exists($fullPath) && is_file($fullPath)) {
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $fullPath);
            finfo_close($finfo);
        } else {
            $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
            $mimes = array('jpg'=>'image/jpeg','jpeg'=>'image/jpeg','png'=>'image/png','gif'=>'image/gif');
            $mimeType = isset($mimes[$ext]) ? $mimes[$ext] : 'application/octet-stream';
        }

        header("Cache-Control: public, max-age=86400");
        header("Content-Type: " . $mimeType);
        header("Content-Length: " . filesize($fullPath));
        readfile($fullPath);
        exit;
    }
}

header("HTTP/1.1 404 Not Found");
die("File not found");