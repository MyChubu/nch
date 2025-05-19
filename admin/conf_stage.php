<?php
# DB開発環境
  define('DB_HOST', 'localhost');
  define('DB_USER', 'root');
  define('DB_PASS', '');
  define('DB_NAME', 'nagoyacrown');
  define('DB_CHARSET', 'utf8');
  define('DSN', 'mysql:dbname='.DB_NAME.';host='.DB_HOST.';charset='.DB_CHARSET);
  define('SITE_URL', 'http://localhost/nagouyacrown/admin');
  define('PASSWORD_KEY', 'nagoyacrown');
  define('DATA_DIR', $_SERVER['DOCUMENT_ROOT'].'/nagoyacrown/data/');



?>
