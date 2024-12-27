<?php
// Development environment
if ($_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1') {
    // Site Configuration
    define('SITE_DOMAIN', 'localhost');
    define('SITE_SUBDIR', 'cpms');
}
// Staging environment
elseif ($_SERVER['HTTP_HOST'] === 'staging.yourdomain.com') {
    // Site Configuration
    define('SITE_DOMAIN', 'staging.yourdomain.com');
    define('SITE_SUBDIR', 'cpms');
}
// Production environment
else {
    // Site Configuration
    define('SITE_DOMAIN', 'yourdomain.com');
    define('SITE_SUBDIR', 'cpms');
}

// Optional: Define other environment-specific constants
define('ENVIRONMENT', $_SERVER['HTTP_HOST'] === 'localhost' ? 'development' : 'production'); 