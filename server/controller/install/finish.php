<?php
namespace server\controller\install;
use server\library\tpl;
use server\library\lib;
use server\model\install as model;
class Finish extends model{
	public static function init(){
		$tpl = new tpl('install');
		$tpl->set('const', ['name' => parent::NAME, 'site' => parent::SITE, 'path' => parent::PATH, 'charset' => parent::CHARSET]);
		if(lib::request()){
			$pdo = parent::pdo();
			if(!is_string($pdo)){
				$name = lib::method('account_name');
				$pw = md5(lib::method('account_rpw'));
				$date = date('Y-m-d H:i:s');
				$pdo->exec('INSERT INTO `'.parent::DBTABLEPRE."account` (`name`,`pw`,`ip`,`date`) VALUES ('$name','$pw','".lib::ip()."','$date')");
				lib::write(parent::ROOT.'upload/install.lock', $date);
				$tpl->get('finish');
			}else{
				$tpl->set('error', $pdo);
				$tpl->get('info_connect');
			}
		}else{
			$tpl->get('info_submit');
		}
	}
}
?>