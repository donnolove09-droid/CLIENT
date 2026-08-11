<?php
namespace server\controller\admin;
use server\library\tpl;
use server\library\lib;
use server\model\admin as model;
class Update extends model{
	public static function init(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$tpl = new tpl('admin');
		$tpl->set('const', ['version' => parent::VERSION, 'build' => parent::BUILD, 'path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('update');
	}
	public static function check(){
		parent::login() or exit('{"state":-3}');
		lib::request(['token', $_COOKIE['account']]) or exit('{"state":"Access Denied"}');
		$temp = parent::ROOT.'upload/temp/update';
		is_dir($temp) or mkdir($temp, 0777, true);
		lib::write($temp.'/api.xml', parent::curl()) or exit('{"state":-4}');
		$xml = @simplexml_load_file($temp.'/api.xml');
		$xml or exit('{"state":-2}');
		lib::unicode($xml->item['build'], true) == parent::BUILD and exit('{"state":-1}');
		echo '{"state":1,"msg":"'.$xml->log.'"}';
	}
	public static function down(){
		parent::login() or exit('logout');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		$temp = parent::ROOT.'upload/temp/update';
		is_file($temp.'/api.xml') or exit('Access Denied');
		$xml = @simplexml_load_file($temp.'/api.xml');
		$xml or exit('busy');
		@set_time_limit(0);
		stream_context_set_default(['ssl' => ['verify_peer' => false]]);
		$patch = lib::unicode($xml->patch['zip'], true);
		$read = @fopen($patch, 'rb');
		$read or exit('busy');
		$slice = explode('/', $_SERVER['PATH_INFO']);
		if(isset($slice[4]) && $slice[4] == 'get'){
			$header = @get_headers($patch, true);
			echo isset($header['Content-Length']) ? is_array($header['Content-Length']) ? $header['Content-Length'][1] : $header['Content-Length'] : 0;
		}else{
			$write = fopen($temp.'/patch.zip', 'wb');
			$write or exit('io');
			flock($write, LOCK_EX) or exit('io');
			ftruncate($write, 0);
			while(!feof($read)){
				fwrite($write, fread($read, 8192));
			}
			flock($write, LOCK_UN);
			fclose($write);
			echo 'end';
		}
		fclose($read);
	}
	public static function flush(){
		parent::login() or exit('{"state":-1}');
		lib::request(['token', $_COOKIE['account']]) or exit('{"state":"Access Denied"}');
		$patch = parent::ROOT.'upload/temp/update/patch.zip';
		$size = is_file($patch) ? filesize($patch) : 0;
		echo '{"state":'.$size.',"msg":"'.lib::byte($size).'"}';
	}
	public static function plant(){
		parent::login() or exit('-1');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		require parent::ROOT.'server/pack/archive/zip.php';
		$zip = new \server\pack\archive\zip(parent::ROOT.'upload/temp/update/patch.zip');
		$export = $zip->export(parent::ROOT);
		echo is_string($export) ? $export : 1;
	}
	public static function done(){
		parent::login() or exit('-1');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		$temp = parent::ROOT.'upload/temp/update';
		is_file($temp.'/api.xml') or exit('Access Denied');
		$xml = @simplexml_load_file($temp.'/api.xml');
		$xml or exit('-2');
		$build = lib::unicode($xml->item['build'], true);
		$file = parent::ROOT.'server/library/base.php';
		$io = lib::write($file, '', 'r');
		$base = strval($io);
		$base = preg_replace("/'BASE_BUILD', '(.*?)'/", "'BASE_BUILD', '$build'", $base);
		!is_bool($io) and lib::write($file, $base) or exit('-3');
		echo '1';
	}
}
?>