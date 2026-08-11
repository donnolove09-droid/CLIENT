<?php
namespace server\controller\index;
use server\library\tpl;
use server\library\lib;
use server\library\db;
use server\model\index as model;
class Index extends model{
	public static function init(){
		parent::login() and exit(header('location:'.parent::PATH.'index.php/hall'));
		$tpl = new tpl('index');
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('index');
	}
	public static function notice(){
		$GLOBALS['param']['basic']['power'] and exit(header('location:'.parent::PATH));
		$tpl = new tpl('index');
		$tpl->set('notice', lib::filter($GLOBALS['param']['basic']['notice'], true));
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('notice');
	}
	public static function sign(){
		lib::request() or exit('Access Denied');
		$name = lib::method('member_name');
		$pw = lib::method('member_pw');
		$captcha = lib::method('captcha');
		preg_match('/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/', $name) and lib::length($name, 32) or exit('Access Denied');
		!preg_match('/[^\x21-\x7e]/', $pw) and lib::length($pw, [6, 24]) or exit('Access Denied');
		ctype_alnum($captcha) and strlen($captcha) == 4 or exit('Access Denied');
		$time = time();
		$salt = isset($_COOKIE['captcha']) ? lib::filter($_COOKIE['captcha']) : '';
		$val = db::fetch('session', 'val', ['key' => ['=', 'captcha', 'and'], 'time' => ['>', $time - 300, 'and'], 'salt' => $salt]);
		$val and $val == strtolower($captcha) or exit('-2');
		$online = intval($GLOBALS['param']['shield']['shield_online']);
		$login_try = intval($GLOBALS['param']['shield']['shield_login_try']);
		$login_catch = intval($GLOBALS['param']['shield']['shield_login_catch']);
		$ip = lib::ip();
		if(db::fetch('shield', 'id', ['key' => ['=', 'member', 'and'], 'val' => ['>=', $login_try, 'and'], 'time' => ['>', $time - $login_catch * 60, 'and'], 'ip' => $ip])){
			echo '-3';
		}elseif($member = db::fetch('member', '[]', ['name' => ['=', $name, 'and'], 'pw' => md5($pw)])){
			$member['lock'] and exit('-4');
			$salt = md5(uniqid($member['id'].'-'.$ip.'-', true));
			db::update('member', ['ip' => $ip, 'login' => $time], ['id' => $member['id']]);
			db::insert('session', ['salt' => $salt, 'key' => 'member', 'val' => $member['id'], 'time' => $time]);
			setcookie('member', $salt, $time + $online * 86400, parent::PATH);
			echo '1';
		}else{
			if($shield = db::fetch('shield', '[]', ['key' => ['=', 'member', 'and'], 'ip' => $ip])){
				$val = $shield['time'] > $time - $login_catch * 60 ? ['+', 1] : 1;
				db::update('shield', ['val' => $val, 'time' => $time], ['id' => $shield['id']]);
			}else{
				db::insert('shield', ['ip' => $ip, 'key' => 'member', 'val' => 1, 'time' => $time]);
			}
			db::delete('session', ['key' => ['=', 'captcha', 'and'], 'salt' => $salt]);
			echo '-1';
		}
	}
	public static function reg(){
		lib::request() or exit('Access Denied');
		$name = lib::method('member_name');
		$pw = lib::method('member_rpw');
		$captcha = lib::method('captcha');
		preg_match('/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/', $name) and lib::length($name, 32) or exit('Access Denied');
		!preg_match('/[^\x21-\x7e]/', $pw) and lib::length($pw, [6, 24]) or exit('Access Denied');
		ctype_alnum($captcha) and strlen($captcha) == 4 or exit('Access Denied');
		$time = time();
		$salt = isset($_COOKIE['captcha']) ? lib::filter($_COOKIE['captcha']) : '';
		$val = db::fetch('session', 'val', ['key' => ['=', 'captcha', 'and'], 'time' => ['>', $time - 300, 'and'], 'salt' => $salt]);
		$val and $val == strtolower($captcha) or exit('-2');
		$reg_try = intval($GLOBALS['param']['shield']['shield_reg_try']);
		$reg_catch = intval($GLOBALS['param']['shield']['shield_reg_catch']);
		$ip = lib::ip();
		if(db::fetch('shield', 'id', ['key' => ['=', 'reg', 'and'], 'val' => ['>=', $reg_try, 'and'], 'time' => ['>', $time - $reg_catch * 60, 'and'], 'ip' => $ip])){
			echo '-3';
		}else{
			db::fetch('member', 'id', ['name' => $name]) and exit('-1');
			db::insert('member', ['name' => $name, 'pw' => md5($pw), 'ip' => $ip, 'nick' => explode('@', $name)[0], 'gem' => 0, 'pea' => 0, 'lock' => 0, 'reg' => date('Y-m-d H:i:s'), 'login' => $time]);
			if($shield = db::fetch('shield', '[]', ['key' => ['=', 'reg', 'and'], 'ip' => $ip])){
				$val = $shield['time'] > $time - $reg_catch * 60 ? ['+', 1] : 1;
				db::update('shield', ['val' => $val, 'time' => $time], ['id' => $shield['id']]);
			}else{
				db::insert('shield', ['ip' => $ip, 'key' => 'reg', 'val' => 1, 'time' => $time]);
			}
			echo '1';
		}
	}
	public static function lost(){
		lib::request() or exit('Access Denied');
		$name = lib::method('member_name');
		$code = lib::method('mail_code');
		$pw = lib::method('member_rpw');
		$captcha = lib::method('captcha');
		preg_match('/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/', $name) and lib::length($name, 32) or exit('Access Denied');
		preg_match('/^[a-z0-9]{32}$/', $code) or exit('Access Denied');
		!preg_match('/[^\x21-\x7e]/', $pw) and lib::length($pw, [6, 24]) or exit('Access Denied');
		ctype_alnum($captcha) and strlen($captcha) == 4 or exit('Access Denied');
		$time = time();
		$salt = isset($_COOKIE['captcha']) ? lib::filter($_COOKIE['captcha']) : '';
		$val = db::fetch('session', 'val', ['key' => ['=', 'captcha', 'and'], 'time' => ['>', $time - 300, 'and'], 'salt' => $salt]);
		$val and $val == strtolower($captcha) or exit('-2');
		$sid = db::fetch('session', 'id', ['key' => ['=', 'mail', 'and'], 'time' => ['>', $time - 1800, 'and'], 'salt' => ['=', $code, 'and'], 'val' => $name]);
		$sid or exit('-1');
		db::fetch('member', 'id', ['name' => $name]) or exit('-3');
		db::update('member', ['pw' => md5($pw)], ['name' => $name]);
		db::delete('session', ['id' => $sid]);
		echo '1';
	}
	public static function send(){
		lib::request() or exit('Access Denied');
		$name = lib::method('member_name');
		preg_match('/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/', $name) and lib::length($name, 32) or exit('Access Denied');
		$GLOBALS['param']['mail']['mail_power'] or exit('-1');
		$mail_try = intval($GLOBALS['param']['shield']['shield_mail_try']);
		$mail_catch = intval($GLOBALS['param']['shield']['shield_mail_catch']);
		$time = time();
		$ip = lib::ip();
		if(db::fetch('shield', 'id', ['key' => ['=', 'mail', 'and'], 'val' => ['>=', $mail_try, 'and'], 'time' => ['>', $time - $mail_catch * 60, 'and'], 'ip' => $ip])){
			echo '-3';
		}else{
			if($shield = db::fetch('shield', '[]', ['key' => ['=', 'mail', 'and'], 'ip' => $ip])){
				$val = $shield['time'] > $time - $mail_catch * 60 ? ['+', 1] : 1;
				db::update('shield', ['val' => $val, 'time' => $time], ['id' => $shield['id']]);
			}else{
				db::insert('shield', ['ip' => $ip, 'key' => 'mail', 'val' => 1, 'time' => $time]);
			}
			db::fetch('member', 'id', ['name' => $name]) or exit('-2');
			$salt = md5(uniqid($name.'-'.$ip.'-', true));
			$secret = lib::filter($GLOBALS['param']['mail']['mail_pw'], true);
			$title = lib::unicode('\u627e\u56de\u5bc6\u7801\u90ae\u4ef6', true);
			$content = lib::unicode('\u60a8\u7684\u90ae\u4ef6\u51ed\u8bc1\u7801\uff1a'.$salt.'\uff0c\u6709\u6548\u671f\u4e3a\u0033\u0030\u5206\u949f\uff01', true);
			require parent::ROOT.'server/pack/smtp/mail.php';
			$mail = new \server\pack\smtp\mail($GLOBALS['param']['mail']['mail_host'], $GLOBALS['param']['mail']['mail_user'], $secret, $GLOBALS['param']['mail']['mail_port'], parent::CHARSET);
			$send = $mail->send($GLOBALS['param']['mail']['mail_user'], $_SERVER['HTTP_HOST'], $title, $name, $ip, $content);
			is_string($send) and exit($send);
			db::insert('session', ['salt' => $salt, 'key' => 'mail', 'val' => $name, 'time' => $time]);
			echo '1';
		}
	}
}
?>