<?php
// Define constants here
session_name('loginid');
ini_set('session.save_path', $_SERVER['DOCUMENT_ROOT'] . '/acc/users/sessions/');
session_start();
define('DB_SERVER', 'sql209.infinityfree.com');
define('DB_USER', 'if0_36019408');
define('DB_PASSWORD', '5DPSN4dadTu');
define('DB_NAME', 'if0_36019408_membership');
define('DB_NAME2', 'if0_36019408_creations');
define('DB_NAME3', 'if0_36019408_forum');
?>