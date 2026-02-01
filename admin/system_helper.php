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
        $os = 'Windows ' . php_uname('r') . ' (Build ' . explode(' ', php_uname('v'))[1] . ')';
    }
    
    return $os;
}
?>
