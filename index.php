<?php
version_compare(PHP_VERSION, '5.4.0', '<') and exit('PHP 5.4+ is required');
require __DIR__.'/server/library/param.php';
require __DIR__.'/server/library/base.php';
require __DIR__.'/server/library/tpl.php';
require __DIR__.'/server/library/lib.php';
require __DIR__.'/server/library/db.php';
$slice = explode('/', isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '');
switch(isset($slice[1]) ? $slice[1] : ''){
	case 'install':
		require __DIR__.'/server/model/install.php';
		server\model\install::router($slice);
		break;
	case 'admin':
		require __DIR__.'/server/model/admin.php';
		server\model\admin::router($slice);
		break;
	default:
		require __DIR__.'/server/model/index.php';
		server\model\index::router($slice);
}
?>