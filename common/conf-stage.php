<?php
# ステージング環境 (conf-stage.php)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', 'root');
define('DB_NAME', 'nagoyacrown');
define('DB_CHARSET', 'utf8');
define('DSN', 'mysql:dbname='.DB_NAME.';host='.DB_HOST.';charset='.DB_CHARSET);
define('SITE_URL', 'http://172.20.11.215/admin');
define('PASSWORD_KEY', 'nagoyacrown');
define('DATA_DIR', $_SERVER['DOCUMENT_ROOT'].'/data/');
?>