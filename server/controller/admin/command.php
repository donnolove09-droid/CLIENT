<?php
namespace server\controller\admin;
use server\library\tpl;
use server\library\lib;
use server\library\db;
use server\model\admin as model;
class Command extends model{
	public static function init(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$tpl = new tpl('admin');
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('command');
	}
	public static function exec(){
		$login = parent::login();
		$login or exit('-1');
		lib::request() or exit('Access Denied');
		$pw = md5(lib::method('pw'));
		$sql = lib::method('sql', true);
		$pw == $login['pw'] or exit('-2');
		try{
			echo db::command($sql);
		}catch(\Exception $e){
			echo $e->getMessage();
		}
	}
}
?>