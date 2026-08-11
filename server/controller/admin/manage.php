<?php
namespace server\controller\admin;
use server\library\tpl;
use server\library\lib;
use server\library\db;
use server\model\admin as model;
class Manage extends model{
	public static function init(){
		$login = parent::login();
		$login or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$tpl = new tpl('admin');
		$tpl->set('login', $login);
		$tpl->set('const', ['name' => parent::NAME, 'path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('manage');
	}
	public static function clean(){
		parent::login() or exit('-1');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		$time = time();
		$online = intval($GLOBALS['param']['shield']['shield_online']);
		$login_catch = intval($GLOBALS['param']['shield']['shield_login_catch']);
		$reg_catch = intval($GLOBALS['param']['shield']['shield_reg_catch']);
		$mail_catch = intval($GLOBALS['param']['shield']['shield_mail_catch']);
		db::delete('stat', ['date' => ['date', '<-6']]);
		db::delete('talk', ['time' => ['<=', $time - 60]]);
		db::delete('room', ['time' => ['<=', $time - 3600]]);
		db::delete('session', ['time' => ['<=', $time - $online * 86400, 'and'], 'key' => 'account']);
		db::delete('session', ['time' => ['<=', $time - $online * 86400, 'and'], 'key' => 'member']);
		db::delete('session', ['time' => ['<=', $time - 300, 'and'], 'key' => 'captcha']);
		db::delete('session', ['time' => ['<=', $time - 1800, 'and'], 'key' => 'mail']);
		db::delete('shield', ['time' => ['<=', $time - $login_catch * 60, 'and'], 'key' => 'account']);
		db::delete('shield', ['time' => ['<=', $time - $login_catch * 60, 'and'], 'key' => 'member']);
		db::delete('shield', ['time' => ['<=', $time - $reg_catch * 60, 'and'], 'key' => 'reg']);
		db::delete('shield', ['time' => ['<=', $time - $mail_catch * 60, 'and'], 'key' => 'mail']);
		lib::clean(parent::ROOT.'upload/temp');
		echo '1';
	}
	public static function logout(){
		parent::login() or exit('1');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		db::delete('session', ['key' => ['=', 'account', 'and'], 'salt' => lib::filter($_COOKIE['account'])]);
		setcookie('account', '', time() - 1, parent::PATH);
		echo '1';
	}
}
?>