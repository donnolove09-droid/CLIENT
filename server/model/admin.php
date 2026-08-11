<?php
namespace server\model;
use server\library\lib;
use server\library\db;
use \ReflectionMethod;
class admin{
	const NAME = BASE_NAME;
	const SITE = BASE_SITE;
	const VERSION = BASE_VERSION;
	const BUILD = BASE_BUILD;
	const ROOT = BASE_ROOT;
	const PATH = BASE_PATH;
	const CHARSET = BASE_CHARSET;
	public static function router($slice){
		$controller = isset($slice[2]) && ctype_alpha($slice[2]) ? $slice[2] : 'login';
		$function = isset($slice[3]) ? $slice[3] : 'init';
		$loader = self::ROOT.'server/controller/admin/'.$controller.'.php';
		if(!is_file(self::ROOT.'upload/install.lock')){
			header('location:'.self::PATH.'index.php/install');
		}elseif(is_file($loader)){
			require $loader;
			$object = 'server\controller\admin\\'.ucfirst($controller);
			$let = function() use($object, $function){
				$method = new ReflectionMethod($object, $function);
				return $method->isPublic() && $method->class != __CLASS__;
			};
			method_exists($object, $function) and $let() and $object::$function();
		}
	}
	protected static function login(){
		$online = intval($GLOBALS['param']['shield']['shield_online']);
		$salt = isset($_COOKIE['account']) ? lib::filter($_COOKIE['account']) : '';
		$id = db::fetch('session', 'val', ['key' => ['=', 'account', 'and'], 'time' => ['>', time() - $online * 86400, 'and'], 'salt' => $salt]);
		return db::fetch('account', '[]', ['id' => $id]);
	}
	protected static function page($array){
		list($table, $field, $where, $order, $limit, $uri) = $array;
		$slice = explode('/', $_SERVER['PATH_INFO']);
		$init = isset($slice[3]) ? '/' : '/init/';
		$match = preg_match('/page:(\d+)/i', $uri, $matches) ? $matches[1] : 0;
		$replace = preg_replace('/\/page:\d+/i', '', $uri);
		$link = $replace.$init.'page:';
		$count = db::fetch($table, '()', $where);
		$total = ceil(($count ?: 1) / $limit);
		$now = $match ? $match > $total ? $total : $match : 1;
		$result = db::fetch($table, $field, $where, $order, [$limit * ($now - 1), $limit]);
		$page = '<ul class="pagination"><li><a href="'.$link.'1"><i class="fa fa-step-backward"></i></a></li>';
		if($now > 1){
			$page .= '<li><a href="'.$link.($now - 1).'"><i class="fa fa-backward"></i></a></li>';
		}
		if($total < 11){
			for($i = 1; $i <= $total; $i++){
				if($i == $now){
					$page .= '<li class="active"><span>'.$i.'</span></li>';
				}else{
					$page .= '<li><a href="'.$link.$i.'">'.$i.'</a></li>';
				}
			}
		}else{
			if($now > ($total - 10)){
				$page .= '<li><span class="ellipsis"><i class="fa fa-ellipsis-h"></i></span></li>';
				for($i = $total - 9; $i <= $total; $i++){
					if($i == $now){
						$page .= '<li class="active"><span>'.$i.'</span></li>';
					}else{
						$page .= '<li><a href="'.$link.$i.'">'.$i.'</a></li>';
					}
				}
			}else{
				for($i = $now; $i < $now + 5; $i++){
					if($i == $now){
						$page .= '<li class="active"><span>'.$i.'</span></li>';
					}else{
						$page .= '<li><a href="'.$link.$i.'">'.$i.'</a></li>';
					}
				}
				$page .= '<li><span class="ellipsis"><i class="fa fa-ellipsis-h"></i></span></li>';
				for($i = $total - 4; $i <= $total; $i++){
					$page .= '<li><a href="'.$link.$i.'">'.$i.'</a></li>';
				}
			}
		}
		if($now < $total){
			$page .= '<li><a href="'.$link.($now + 1).'"><i class="fa fa-forward"></i></a></li>';
		}
		$page .= '<li><a href="'.$link.$total.'"><i class="fa fa-step-forward"></i></a></li></ul>';
		return [$count, $result, $page];
	}
	protected static function search($size){
		$uri = str_replace('\'', '%27', $_SERVER['REQUEST_URI']);
		$keyword = preg_match('/keyword:([^\s][^\/]*)/i', $uri, $matches) ? rawurldecode($matches[1]) : false;
		$keyword = is_bool($keyword) ? false : lib::length(lib::filter(lib::convert($keyword)), $size, true);
		$slice = explode('/', $_SERVER['PATH_INFO']);
		$init = isset($slice[3]) ? '/' : '/init/';
		return [$uri, $keyword, $init];
	}
	protected static function curl(){
		$url = 'http://'.self::SITE.'/index.php/admin/login/api';
		$data = ['version' => self::VERSION, 'build' => self::BUILD, 'charset' => self::CHARSET];
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url.'?'.http_build_query($data));
		curl_setopt($ch, CURLOPT_REFERER, $_SERVER['HTTP_REFERER']);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$res = curl_exec($ch);
		curl_close($ch);
		return $res;
	}
	protected static function avatar($uid){
		$file = 'upload/avatar/'.$uid.'.png';
		$default = self::PATH.'client/view/index/css/avatar.png';
		return is_file(self::ROOT.$file) ? self::PATH.$file.'?'.time() : $default;
	}
}
?>