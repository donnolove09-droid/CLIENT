<?php
namespace server\library;
use \PDO,\PDOException,\Exception;
class db{
	private static $dbcharset = BASE_DBCHARSET;
	private static $dbhost = BASE_DBHOST;
	private static $dbport = BASE_DBPORT;
	private static $dbuser = BASE_DBUSER;
	private static $dbpw = BASE_DBPW;
	private static $dbname = BASE_DBNAME;
	private static $dbtablepre = BASE_DBTABLEPRE;
	private static function pdo(){
		try{
			return new PDO('mysql:host='.self::$dbhost.';port='.self::$dbport.';charset='.self::$dbcharset.';dbname='.self::$dbname, self::$dbuser, self::$dbpw);
		}catch(PDOException $e){
			exit($e->getMessage());
		}
	}
	private static function alpha($val){
		$letter = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'W', 'X', 'Y', 'Z'];
		$begin = [45217, 45253, 45761, 46318, 46826, 47010, 47297, 47614, 48119, 49062, 49324, 49896, 50371, 50614, 50622, 50906, 51387, 51446, 52218, 52698, 52980, 53689, 54481];
		$end = [45252, 45760, 46317, 46825, 47009, 47296, 47613, 48118, 49061, 49323, 49895, 50370, 50613, 50621, 50905, 51386, 51445, 52217, 52697, 52979, 53640, 54480, 55289];
		$key = array_keys($letter, $val)[0];
		return $begin[$key].' AND '.$end[$key];
	}
	private static function where($array){
		$where = $split = '';
		foreach($array as $k => $v){
			$k = preg_replace('/\d+:(\w+)/', '$1', $k);
			if(!is_array($v)){
				$where .= $split.'`'.$k.'`=\''.$v.'\'';
			}elseif(in_array($v[0], ['<', '>', '<>', '<=', '>='])){
				$where .= $split.'`'.$k.'`'.$v[0].$v[1];
			}elseif($v[0] == 'in'){
				$where .= $split.'`'.$k.'` IN ('.$v[1].')';
			}elseif($v[0] == 'not'){
				$where .= $split.'`'.$k.'` NOT IN ('.$v[1].')';
			}elseif($v[0] == 'like'){
				$where .= $split.'`'.$k.'` LIKE \'%'.str_replace(['%', '_'], ['\%', '\_'], $v[1]).'%\'';
			}elseif($v[0] == 'alpha'){
				$where .= $split.'CONV(HEX(LEFT(CONVERT(`'.$k.'` USING gbk),1)),16,10) BETWEEN '.self::alpha($v[1]);
			}elseif($v[0] == 'date'){
				$where .= $split.'DATEDIFF(DATE(`'.$k.'`),\''.date('Y-m-d').'\')'.$v[1];
			}elseif($v[0] == 'time'){
				$where .= $split.'DATEDIFF(DATE(FROM_UNIXTIME(`'.$k.'`)),\''.date('Y-m-d').'\')'.$v[1];
			}else{
				$where .= $split.'`'.$k.'`=\''.$v[1].'\'';
			}
			if(is_array($v) && isset($v[2])){
				$split = ' '.strtoupper($v[2]).' ';
			}
		}
		return ' WHERE '.$where;
	}
	public static function version(){
		return self::pdo()->getAttribute(PDO::ATTR_SERVER_VERSION);
	}
	public static function command($sql){
		$pdo = self::pdo();
		if(is_int($row = $pdo->exec($sql))){
			return $row;
		}
		throw new Exception($pdo->errorInfo()[2]);
	}
	public static function fetch($table, $field, $where=[], $order=[], $limit=[], $fail=0){
		if(is_array($field)){
			if($field[0] == 'sum'){
				$shift = ['SUM(`'.$field[1].'`)', 'both'];
			}else{
				$push = [];
				foreach(explode(',', $field[1]) as $chunk){
					$push[] = '`'.$chunk.'`';
				}
				$shift = [implode(',', $push), 'all'];
			}
		}elseif(in_array($field, ['{}', '[]', '()'])){
			$shift = [$field == '()' ? 'COUNT(*)' : '*', str_replace(['{}', '[]', '()'], ['all', 'assoc', 'both'], $field)];
		}else{
			$shift = ['`'.$field.'`', 'num'];
		}
		$where = $where ? self::where($where) : '';
		$order = $order ? ' ORDER BY `'.$order[0].'` '.strtoupper($order[1]) : '';
		$limit = $limit ? ' LIMIT '.$limit[0].','.$limit[1] : '';
		$query = self::pdo()->query('SELECT '.$shift[0].' FROM `'.self::$dbtablepre.$table.'`'.$where.$order.$limit);
		if($shift[1] == 'both'){
			$both = $query->fetch(PDO::FETCH_BOTH);
			return $both[0] ?: 0;
		}elseif($shift[1] == 'num'){
			$num = $query->fetch(PDO::FETCH_NUM);
			return $num ? $num[0] : $fail;
		}
		return $shift[1] == 'assoc' ? $query->fetch(PDO::FETCH_ASSOC) : $query->fetchAll(PDO::FETCH_CLASS);
	}
	public static function delete($table, $where=[]){
		$where = $where ? self::where($where) : '';
		self::pdo()->exec('DELETE FROM `'.self::$dbtablepre.$table.'`'.$where);
	}
	public static function update($table, $array, $where=[]){
		$set = $split = '';
		foreach($array as $k => $v){
			if(is_array($v)){
				$set .= $split.'`'.$k.'`=`'.$k.'`'.$v[0].$v[1];
			}else{
				$set .= $split.'`'.$k.'`=\''.$v.'\'';
			}
			$split = ',';
		}
		$where = $where ? self::where($where) : '';
		self::pdo()->exec('UPDATE `'.self::$dbtablepre.$table.'` SET '.$set.$where);
	}
	public static function insert($table, $array){
		$key = $val = $split = '';
		foreach($array as $k => $v){
			$key .= $split.'`'.$k.'`';
			$val .= $split.'\''.$v.'\'';
			$split = ',';
		}
		$pdo = self::pdo();
		$pdo->exec('INSERT INTO `'.self::$dbtablepre.$table.'` ('.$key.') VALUES ('.$val.')');
		return $pdo->lastInsertId();
	}
}
?>