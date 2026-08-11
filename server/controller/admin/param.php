<?php
namespace server\controller\admin;
use server\library\tpl;
use server\library\lib;
use server\model\admin as model;
class Param extends model{
	public static function init(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$tpl = new tpl('admin');
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('param');
	}
	public static function cache(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$tpl = new tpl('admin');
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('param_cache');
	}
	public static function game(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$tpl = new tpl('admin');
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('param_game');
	}
	public static function mail(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$tpl = new tpl('admin');
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('param_mail');
	}
	public static function shield(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$tpl = new tpl('admin');
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('param_shield');
	}
	public static function write(){
		parent::login() or exit('-1');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		$list = isset($_POST['data']) ? $_POST['data'] : '';
		is_array($list) or exit('Access Denied');
		$file = parent::ROOT.'server/library/param.php';
		$io = lib::write($file, '', 'r');
		$param = strval($io);
		foreach($list as $item){
			$key = isset($item['name']) && is_string($item['name']) ? lib::filter(lib::convert($item['name'])) : '';
			$val = isset($item['value']) && is_string($item['value']) ? trim(lib::filter(lib::convert($item['value']))) : '';
			$param = preg_replace("/'$key' => '([\s\S]*?)'/", "'$key' => '$val'", $param);
		}
		!is_bool($io) and lib::write($file, $param) or exit('-2');
		echo '1';
	}
}
?>