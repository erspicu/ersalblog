<?php
/**
 * System Helper Functions
 */

/**
 * 取得更詳細的作業系統資訊 (含發行版或建置號)
 */
function get_detailed_os_info() {
    $os = php_uname('s');
    
    if (strtoupper(substr($os, 0, 3)) === 'LIN') {
        if (file_exists('/etc/os-release')) {
            $os_info = parse_ini_file('/etc/os-release');
            if (isset($os_info['PRETTY_NAME'])) {
                $os = $os_info['PRETTY_NAME'];
            }
        }
        // 檢查是否為 WSL
        if (file_exists('/proc/version')) {
            $ver = file_get_contents('/proc/version');
            if (strpos(strtolower($ver), 'microsoft') !== false) {
                $os .= ' (WSL2)';
            }
        }
    } elseif (strtoupper(substr($os, 0, 3)) === 'WIN') {
        // 優先嘗試使用 COM 元件 (WMI)，這是 Windows PHP 最穩定獲取系統資訊的方式
        if (class_exists('COM')) {
            try {
                $wmi = new COM('winmgmts:{impersonationLevel=impersonate}!\\\\.\\root\\cimv2');
                $os_info = $wmi->ExecQuery("Select Caption, Version, OSArchitecture from Win32_OperatingSystem")->ItemIndex(0);
                if ($os_info) {
                    return $os_info->Caption . ' ' . $os_info->Version . ' (' . $os_info->OSArchitecture . ')';
                }
            } catch (Exception $e) {
                // 如果 COM 失敗，則嘗試接下來的方法
            }
        }

        // 備案 1：使用 PowerShell (適用於 WSL2 呼叫 Windows 或 COM 停用的情況)
        $ps_cmd = '$os = Get-CimInstance Win32_OperatingSystem; echo "$($os.Caption) $($os.Version) ($($os.OSArchitecture))"';
        $cmd = 'powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $ps_cmd . '"';
        $output = @shell_exec($cmd);
        
        // 嘗試路徑回退
        if (!$output) {
            $cmd_full = 'C:\Windows\System32\WindowsPowerShell\v1.0\powershell.exe -NoProfile -ExecutionPolicy Bypass -Command "' . $ps_cmd . '"';
            $output = @shell_exec($cmd_full);
        }

        if ($output) {
            $output = trim($output);
            if (!mb_check_encoding($output, 'UTF-8')) {
                $output = mb_convert_encoding($output, 'UTF-8', 'CP950');
            }
            return $output;
        }

        // 最終回退：基礎 php_uname
        $os = 'Windows ' . php_uname('r') . ' (Build ' . explode(' ', php_uname('v'))[1] . ')';
    }
    
    return $os;
}

/**
 * 修正文章內容中的相簿路徑，使其適用於根目錄 (Root) 環境
 * 文章編輯器插入時是相對於 admin/ 的路徑 (例如 ../album/)
 * 在根目錄顯示時應改為正確的相對路徑 (例如 album/)
 */
function fix_album_paths_for_root($html, $album_path) {
    if (empty($html) || empty($album_path)) return $html;
    
    // 確保 album_path 結尾有斜線
    $album_path = rtrim($album_path, '/') . '/';
    
    // 編輯器插入的是 ../album/ 或 ../../album/ (取決於配置)
    // 我們需要將其轉換為相對於根目錄的 album_path
    $search = 'src="../' . $album_path;
    $replace = 'src="' . $album_path;
    
    return str_replace($search, $replace, $html);
}

/**
 * 針對 PHP 5.x 的 random_bytes 回退方案
 */
if (!function_exists('random_bytes')) {
    function random_bytes($length) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($bytes !== false && $strong === true) {
                return $bytes;
            }
        }
        if (function_exists('mcrypt_create_iv')) {
            $bytes = mcrypt_create_iv($length, MCRYPT_DEV_URANDOM);
            if ($bytes !== false) {
                return $bytes;
            }
        }
        // 最差的回退方案 (安全性較低)
        $bytes = '';
        for ($i = 0; $i < $length; $i++) {
            $bytes .= chr(mt_rand(0, 255));
        }
        return $bytes;
    }
}

/**
 * 保護 Script 標籤，防止在網頁中執行文章內的腳本
 * 改為轉義形式，使 <script> 標籤能以文字形式在技術文章中顯示但不執行
 */
function protect_script_tags($html) {
    if (empty($html)) return "";
    // 尋找所有的 <script ...> 與 </script> 標籤並將其轉義為 HTML 實體
    return preg_replace_callback('/<\/?script\b[^>]*>/i', function($m) {
        return htmlspecialchars($m[0], ENT_QUOTES, 'UTF-8');
    }, $html);
}

/**
 * 獲取主機唯一識別碼 (Fingerprint)
 * 用於密碼雜湊的額外鹽值 (Pepper)
 */
function getSystemFingerprint() {
    $info = '';
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows: 使用電腦名稱與處理器架構
        $info = getenv('COMPUTERNAME') . getenv('PROCESSOR_IDENTIFIER');
    } else {
        // Linux / WSL2: 優先讀取 machine-id
        if (file_exists('/etc/machine-id')) {
            $info = file_get_contents('/etc/machine-id');
        } elseif (file_exists('/var/lib/dbus/machine-id')) {
            $info = file_get_contents('/var/lib/dbus/machine-id');
        } else {
            $info = php_uname('n') . PHP_OS;
        }
    }
    return hash('sha256', 'ersalblog_' . trim($info));
}
?>
