<?php
namespace server\controller\admin;
use server\library\tpl;
use server\library\lib;
use server\library\db;
use server\model\admin as model;
class Home extends model{
	public static function init(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$ip = db::fetch('stat', '()', ['date' => ['date', '=0']]);
		$pv = db::fetch('stat', ['sum', 'pv'], ['date' => ['date', '=0']]);
		$tpl = new tpl('admin');
		$tpl->set('stat', ['ip' => $ip, 'pv' => $pv]);
		$tpl->set('mysql', db::version());
		$tpl->set('const', ['name' => parent::NAME, 'site' => parent::SITE, 'version' => parent::VERSION, 'build' => parent::BUILD, 'path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('home');
	}
	public static function license(){
		parent::login() or exit('-3');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		$temp = parent::ROOT.'upload/temp/update';
		is_dir($temp) or mkdir($temp, 0777, true);
		lib::write($temp.'/api.xml', parent::curl()) or exit('-4');
		$xml = @simplexml_load_file($temp.'/api.xml');
		$xml or exit('-2');
		echo lib::unicode($xml->item['grade'], true);
	}
}
?>