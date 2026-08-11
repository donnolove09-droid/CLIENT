<?php
namespace server\controller\install;
use server\library\tpl;
use server\library\lib;
use server\model\install as model;
class Create extends model{
	public static function init(){
		$tpl = new tpl('install');
		$tpl->set('const', ['name' => parent::NAME, 'site' => parent::SITE, 'path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('create');
	}
	public static function setup(){
		$tpl = new tpl('install');
		$tpl->set('const', ['name' => parent::NAME, 'site' => parent::SITE, 'path' => parent::PATH, 'charset' => parent::CHARSET]);
		if(lib::request()){
			$file = parent::ROOT.'server/library/base.php';
			$io = lib::write($file, '', 'r');
			$base = strval($io);
			foreach(array_keys($_POST) as $chunk){
				$$chunk = lib::method($chunk);
				$pattern = 'BASE_'.strtoupper($chunk);
				$base = preg_replace("/'$pattern', '(.*?)'/", "'$pattern', '{$$chunk}'", $base);
			}
			$pdo = parent::pdo(['dbhost' => $dbhost, 'dbport' => $dbport, 'dbuser' => $dbuser, 'dbpw' => $dbpw]);
			if(!is_string($pdo)){
				is_bool($io) or lib::write($file, $base);
				$database = $pdo->query('SHOW DATABASES')->fetchAll(\PDO::FETCH_ASSOC);
				$push = [];
				foreach($database as $key => $val){
					$push[] = $val['Database'];
				}
				if(in_array($dbname, $push)){
					$table = $pdo->query("SHOW TABLES FROM `$dbname`")->fetchAll(\PDO::FETCH_ASSOC);
					$push = [];
					foreach($table as $key => $val){
						$push[] = $val['Tables_in_'.$dbname];
					}
					if(in_array($dbtablepre.'account', $push)){
						$tpl->get('info_setup_exist');
					}else{
						$tpl->get('info_setup');
					}
				}else{
					try{
						$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
						$pdo->exec("CREATE DATABASE `$dbname` DEFAULT CHARACTER SET ".parent::DBCHARSET);
						$tpl->get('info_setup');
					}catch(\PDOException $e){
						$tpl->set('error', $e->getMessage());
						$tpl->get('info_connect');
					}
				}
			}else{
				$tpl->set('error', $pdo);
				$tpl->get('info_connect');
			}
		}else{
			$tpl->get('info_submit');
		}
	}
	public static function step(){
		$pdo = parent::pdo();
		is_string($pdo) and exit('{"state":-1,"msg":"'.lib::filter($pdo).'"}');
		$create = file_get_contents(parent::ROOT.'client/view/install/js/create.sql');
		$create = str_replace(['{tablepre}', '{charset}'], [parent::DBTABLEPRE, parent::DBCHARSET], $create);
		$create_array = explode(';', $create);
		$detail_array = explode('`;', $create);
		$detail_push = [];
		for($i = 0; $i < count($create_array) - 1; $i++){
			$pdo->exec($create_array[$i]);
		}
		for($i = 0; $i < count($detail_array) - 1; $i++){
			$detail_slice = explode('DROP TABLE IF EXISTS `', $detail_array[$i]);
			$detail_push[] = $detail_slice[1];
		}
		$detail = implode(',', $detail_push);
		echo '{"state":1,"msg":"'.$detail.'"}';
	}
}
?>