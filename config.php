<?php
define('DB_HOST', 'monocle-aalst.be.mysql');
define('DB_NAME', 'monocle_aalst_bemisenplace');
define('DB_USER', 'monocle_aalst_bemisenplace');
define('DB_PASS', 'Stinkik123');

// Blokkeer directe toegang via browser
if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    http_response_code(403);
    exit('Access denied.');
}
?>
