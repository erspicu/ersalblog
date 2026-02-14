<?php
/**
 * Album Admin System Helper
 */

/**
 * 獲取主機唯一識別碼 (Fingerprint)
 */
function getSystemFingerprint() {
    $info = '';
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $info = getenv('COMPUTERNAME') . getenv('PROCESSOR_IDENTIFIER');
    } else {
        if (file_exists('/etc/machine-id')) {
            $info = file_get_contents('/etc/machine-id');
        } elseif (file_exists('/var/lib/dbus/machine-id')) {
            $info = file_get_contents('/var/lib/dbus/machine-id');
        } else {
            $info = php_uname('n') . PHP_OS;
        }
    }
    return hash('sha256', 'ersalalbum_' . trim($info));
}

/**
 * 針對 PHP 5.x 的 random_bytes 回退方案
 */
if (!function_exists('random_bytes')) {
    function random_bytes($length) {
        if (function_exists('openssl_random_pseudo_bytes')) {
            $bytes = openssl_random_pseudo_bytes($length, $strong);
            if ($bytes !== false && $strong === true) return $bytes;
        }
        $bytes = '';
        for ($i = 0; $i < $length; $i++) {
            $bytes .= chr(mt_rand(0, 255));
        }
        return $bytes;
    }
}
?>