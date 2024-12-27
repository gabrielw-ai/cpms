<?php
require_once __DIR__ . '/site_config.php';

function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = SITE_DOMAIN ?: $_SERVER['HTTP_HOST'];
    
    if ($host === 'localhost' || $host === '127.0.0.1') {
        return $protocol . $host . '/' . SITE_SUBDIR;
    }
    
    if (SITE_SUBDIR) {
        return $protocol . $host . '/' . SITE_SUBDIR;
    }
    
    return $protocol . $host;
}

function getAssetUrl($path = '') {
    $path = ltrim($path, '/');
    return getBaseUrl() . '/adminlte/' . $path;
}
function setBaseUrl($url) {
    // This function is kept for compatibility
    return getBaseUrl();
}

require_once __DIR__ . '/controller/conn.php';
