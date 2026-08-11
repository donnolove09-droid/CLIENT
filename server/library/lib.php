<?php
namespace server\library;
class lib{
	private static $charset = BASE_CHARSET;
	public static function convert($str, $only=false){
		$order = ['ASCII', 'UTF-8', 'GB2312', 'GBK'];
		$encoding = $only ? $order[1] : strtoupper(self::$charset);
		return mb_convert_encoding($str, $encoding, mb_detect_encoding($str, $order));
	}
	public static function filter($str, $decode=false){
		$entity = ['\\', '&#92;'];
		$encoding = self::$charset == 'gbk' ? 'ISO-8859-1' : 'UTF-8';
		if($decode){
			return str_replace($entity[1], $entity[0], htmlspecialchars_decode($str, ENT_QUOTES));
		}
		return str_replace($entity[0], $entity[1], htmlspecialchars($str, ENT_QUOTES, $encoding));
	}
	public static function unicode($str, $decode=false, $strict=false){
		$part = '';
		if($decode){
			preg_match_all('/\\\u[\w]{4}|[\s\S]*?/', $str, $matches);
			for($i = 0; $i < count($matches[0]); $i++){
				$bulk = $matches[0][$i];
				if(preg_match('/\\\u[\w]{4}/', $bulk)){
					$front = chr(base_convert(substr($bulk, 2, 2), 16, 10));
					$rear = chr(base_convert(substr($bulk, 4), 16, 10));
					$part .= mb_convert_encoding($front.$rear, strtoupper(self::$charset), 'UCS-2');
				}else{
					$part .= $bulk;
				}
			}
		}else{
			$encode = mb_convert_encoding($str, 'UCS-2', strtoupper(self::$charset));
			$array = str_split($encode);
			for($i = 0; $i < strlen($encode) - 1; $i = $i + 2){
				$front = ord($array[$i]);
				$rear = $array[$i + 1];
				if($front > 0){
					$part .= '\u'.base_convert($front, 10, 16).str_pad(base_convert(ord($rear), 10, 16), 2, 0, STR_PAD_LEFT);
				}else{
					$part .= $strict ? '\u'.str_pad(base_convert(ord($rear), 10, 16), 4, 0, STR_PAD_LEFT) : $rear;
				}
			}
		}
		return $part;
	}
	public static function length($str, $size, $cut=false){
		$decode = self::filter($str, true);
		$encoding = strtoupper(self::$charset);
		$width = mb_strlen($decode, $encoding);
		if(is_string($cut)){
			return self::filter(mb_strimwidth($decode, 0, $size, $cut, $encoding));
		}elseif($cut){
			return self::filter($size < $width ? mb_substr($decode, 0, $size, $encoding) : $decode);
		}elseif(is_array($size)){
			return $width < $size[0] || $width > $size[1] ? false : true;
		}
		return $width > $size ? false : true;
	}
	public static function method($key, $keep=false){
		switch($_SERVER['REQUEST_METHOD']){
			case 'POST':
				$str = isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
				break;
			default:
				$str = isset($_GET[$key]) && is_string($_GET[$key]) ? $_GET[$key] : '';
		}
		return trim($keep ? self::convert($str) : self::filter(self::convert($str)));
	}
	public static function request($token=[]){
		if($_SERVER['REQUEST_METHOD'] == 'POST'){
			if(isset($_SERVER['HTTP_REFERER']) && preg_match('/^https?:\/\/'.$_SERVER['HTTP_HOST'].'\//i', $_SERVER['HTTP_REFERER'])){
				return $token ? isset($_POST[$token[0]]) && $_POST[$token[0]] == $token[1] ? true : false : true;
			}
		}
		return false;
	}
	public static function checkbox($key){
		$list = isset($_POST[$key]) ? $_POST[$key] : '';
		if(is_array($list) && $list){
			$push = [];
			foreach($list as $item){
				$push[] = intval($item);
			}
			$column = implode(',', $push);
		}
		return isset($column) ? $column : 0;
	}
	public static function write($file, $data, $mode='w'){
		if($handle = fopen($file, $mode)){
			if($mode == 'r' && flock($handle, LOCK_SH)){
				$buffer = file_get_contents($file);
				flock($handle, LOCK_UN);
				fclose($handle);
				return $buffer;
			}elseif($mode == 'w' && flock($handle, LOCK_EX)){
				ftruncate($handle, 0);
				fwrite($handle, $data);
				flock($handle, LOCK_UN);
				fclose($handle);
				return true;
			}
			fclose($handle);
		}
		return false;
	}
	public static function clean($dir){
		if(is_dir($dir) && $handle = opendir($dir)){
			while($file = readdir($handle)){
				$path = $dir.'/'.$file;
				if(!in_array($file, ['.', '..']) && is_dir($path)){
					self::clean($path);
				}elseif(is_file($path)){
					unlink($path);
				}
			}
			closedir($handle);
			rmdir($dir);
		}
	}
	public static function byte($size){
		$unit = [' B', ' KB', ' MB', ' GB', ' TB'];
		$mult = [pow(1024, 2), pow(1024, 3), pow(1024, 4)];
		if($size < 1024){
			return $size.$unit[0];
		}elseif($size < $mult[0]){
			return round($size / 1024).$unit[1];
		}elseif($size < $mult[1]){
			return round($size / $mult[0], 2).$unit[2];
		}elseif($size < $mult[2]){
			return round($size / $mult[1], 2).$unit[3];
		}
		return round($size / $mult[2], 2).$unit[4];
	}
	public static function ip(){
		$ip = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
		return preg_match('/^(\d{1,3}\.){3}\d{1,3}$/', $ip) ? $ip : '';
	}
}
?>