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
?>
