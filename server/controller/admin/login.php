<?php
namespace server\controller\admin;
use server\library\tpl;
use server\library\lib;
use server\library\db;
use server\model\admin as model;
class Login extends model{
	public static function init(){
		parent::login() and exit(header('location:'.parent::PATH.'index.php/admin/manage'));
		$tpl = new tpl('admin');
		$tpl->set('const', ['name' => parent::NAME, 'site' => parent::SITE, 'path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('login');
	}
	public static function captcha(){
		header('content-type:image/png');
		$letter = $random = [];
		for($i = 65; $i < 91; $i++){
			$letter[] = chr($i);
		}
		$mixed = array_merge($letter, [0, 1, 2, 3, 4, 5, 6, 7, 8, 9]);
		for($i = 0; $i < 4; $i++){
			$random[] = $mixed[mt_rand(0, 35)];
		}
		$captcha = strtolower(implode('', $random));
		$time = time();
		$salt = md5(uniqid(lib::ip().'-', true));
		db::insert('session', ['salt' => $salt, 'key' => 'captcha', 'val' => $captcha, 'time' => $time]);
		setcookie('captcha', $salt, $time + 300, parent::PATH);
		$img = imagecreatetruecolor(130, 50);
		$color = imagecolorallocate($img, 243, 251, 254);
		imagefilledrectangle($img, 0, 50, 130, 0, $color);
		for($i = 0; $i < 6; $i++){
			$linecolor = imagecolorallocate($img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
			imageline($img, mt_rand(0, 130), mt_rand(0, 50), mt_rand(0, 130), mt_rand(0, 50), $linecolor);
		}
		for($i = 0; $i < 36; $i++){
			$stringcolor = imagecolorallocate($img, mt_rand(200, 255), mt_rand(200, 255), mt_rand(200, 255));
			imagestring($img, mt_rand(1, 5), mt_rand(0, 130), mt_rand(0, 50), $mixed[$i], $stringcolor);
		}
		for($i = 0; $i < 4; $i++){
			$ttftextcolor = imagecolorallocate($img, mt_rand(0, 156), mt_rand(0, 156), mt_rand(0, 156));
			imagettftext($img, 20, mt_rand(-30, 30), 31 * $i + 9, 36, $ttftextcolor, parent::ROOT.'client/view/admin/js/captcha.ttf', $random[$i]);
		}
		imagepng($img);
		imagedestroy($img);
	}
	public static function sign(){
		lib::request() or exit('Access Denied');
		$name = lib::method('account_name');
		$pw = lib::method('account_pw');
		$captcha = lib::method('captcha');
		!preg_match('/[^\x21-\x7e]/', $name) and lib::length($name, [6, 24]) or exit('Access Denied');
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
		if(db::fetch('shield', 'id', ['key' => ['=', 'account', 'and'], 'val' => ['>=', $login_try, 'and'], 'time' => ['>', $time - $login_catch * 60, 'and'], 'ip' => $ip])){
			echo '-3';
		}elseif($id = db::fetch('account', 'id', ['name' => ['=', $name, 'and'], 'pw' => md5($pw)])){
			$salt = md5(uniqid($id.'-'.$ip.'-', true));
			db::update('account', ['ip' => $ip, 'date' => date('Y-m-d H:i:s')], ['id' => $id]);
			db::insert('session', ['salt' => $salt, 'key' => 'account', 'val' => $id, 'time' => $time]);
			setcookie('account', $salt, $time + $online * 86400, parent::PATH);
			echo '1';
		}else{
			if($shield = db::fetch('shield', '[]', ['key' => ['=', 'account', 'and'], 'ip' => $ip])){
				$val = $shield['time'] > $time - $login_catch * 60 ? ['+', 1] : 1;
				db::update('shield', ['val' => $val, 'time' => $time], ['id' => $shield['id']]);
			}else{
				db::insert('shield', ['ip' => $ip, 'key' => 'account', 'val' => 1, 'time' => $time]);
			}
			db::delete('session', ['key' => ['=', 'captcha', 'and'], 'salt' => $salt]);
			echo '-1';
		}
	}
}
?>