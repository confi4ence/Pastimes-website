<?php
// config.php — central database credentials.
//
// EDIT THIS FILE if your local MySQL/MariaDB needs different credentials
// than the XAMPP default. Both DBConn.php and setup.php read from here,
// so you only ever need to change it in ONE place.
//
// Most fresh XAMPP installs use root with NO password (DB_PASS = '').
// If you previously set a MySQL root password yourself (e.g. through
// phpMyAdmin, or the XAMPP "Security" page), put that password below.
define('DB_HOST', '127.0.0.1');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ClothingStore');
define('DB_PORT', 3307);