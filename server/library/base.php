<?php
ini_set('date.timezone', 'PRC');
ini_set('default_charset', 'utf-8');
define('BASE_NAME', '如烟笔记');
define('BASE_SITE', 'blog.iosru.com');
define('BASE_VERSION', 'poker');
define('BASE_BUILD', '20251005');
define('BASE_ROOT', str_replace('\\', '/', substr(__DIR__, 0, -14)));
define('BASE_PATH', str_ireplace($_SERVER['DOCUMENT_ROOT'], '', BASE_ROOT));
define('BASE_CHARSET', ini_get('default_charset'));
define('BASE_DBCHARSET', str_replace('-', '', BASE_CHARSET));
define('BASE_DBHOST', '');
define('BASE_DBPORT', '');
define('BASE_DBUSER', '');
define('BASE_DBPW', '');
define('BASE_DBNAME', '');
define('BASE_DBTABLEPRE', '');
?>