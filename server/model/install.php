<?php
namespace server\model;
use \PDO,\PDOException,\ReflectionMethod;
class install{
	const NAME = BASE_NAME;
	const SITE = BASE_SITE;
	const VERSION = BASE_VERSION;
	const BUILD = BASE_BUILD;
	const ROOT = BASE_ROOT;
	const PATH = BASE_PATH;
	const CHARSET = BASE_CHARSET;
	const DBCHARSET = BASE_DBCHARSET;
	const DBHOST = BASE_DBHOST;
	const DBPORT = BASE_DBPORT;
	const DBUSER = BASE_DBUSER;
	const DBPW = BASE_DBPW;
	const DBNAME = BASE_DBNAME;
	const DBTABLEPRE = BASE_DBTABLEPRE;
	public static function router($slice){
		$lock = self::ROOT.'upload/install.lock';
		$controller = !is_file($lock) && isset($slice[2]) && ctype_alpha($slice[2]) ? $slice[2] : 'read';
		$function = !is_file($lock) ? isset($slice[3]) ? $slice[3] : 'init' : 'warn';
		$loader = self::ROOT.'server/controller/install/'.$controller.'.php';
		if(is_file($loader)){
			require $loader;
			$object = 'server\controller\install\\'.ucfirst($controller);
			$let = function() use($object, $function){
				$method = new ReflectionMethod($object, $function);
				return $method->isPublic() && $method->class != __CLASS__;
			};
			method_exists($object, $function) and $let() and $object::$function();
		}
	}
	protected static function pdo($option=[]){
		try{
			if($option){
				return new PDO('mysql:host='.$option['dbhost'].';port='.$option['dbport'], $option['dbuser'], $option['dbpw']);
			}
			return new PDO('mysql:host='.self::DBHOST.';port='.self::DBPORT.';charset='.self::DBCHARSET.';dbname='.self::DBNAME, self::DBUSER, self::DBPW);
		}catch(PDOException $e){
			return $e->getMessage();
		}
	}
	protected static function permit($path, $dir='/^', $ext='.$'){
		if(is_file($path)){
			if($handle = fopen($path, 'r+')){
				fclose($handle);
				return true;
			}
		}elseif($handle = fopen($path.$dir.$ext, 'w+')){
			fwrite($handle, time());
			fclose($handle);
			if(!unlink($path.$dir.$ext)){
				return false;
			}elseif(is_dir($path.$dir) && !rmdir($path.$dir)){
				return false;
			}elseif(!mkdir($path.$dir)){
				return false;
			}elseif(!rmdir($path.$dir)){
				return false;
			}
			return true;
		}
		return false;
	}
}
?>