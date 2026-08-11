<?php
namespace server\controller\admin;
use server\library\tpl;
use server\library\lib;
use server\library\db;
use server\model\admin as model;
class Account extends model{
	public static function init(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		list($uri, $keyword, $init) = parent::search(24);
		$where = is_bool($keyword) ? [] : ['name' => ['like', $keyword]];
		$limit = preg_match('/limit:(\d+)/i', $uri, $matches) ? $matches[1] : 0;
		$limit = in_array($limit, [10, 30, 40, 50]) ? $limit : 20;
		$limit_link = preg_replace('/\/page:\d+|\/limit:\d+/i', '', $uri);
		$limit_link = $limit_link.$init.'limit:';
		list($count, $result, $page) = parent::page(['account', '{}', $where, ['date', 'desc'], $limit, $uri]);
		$tpl = new tpl('admin');
		$tpl->set('keyword', $keyword);
		$tpl->set('limit', [$limit, $limit_link]);
		$tpl->set('count', $count);
		$tpl->set('result', $result);
		$tpl->set('page', $page);
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('account');
	}
	public static function add(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$tpl = new tpl('admin');
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('account_add');
	}
	public static function insert(){
		parent::login() or exit('-1');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		$name = lib::method('account_name');
		$pw = lib::method('account_rpw');
		!preg_match('/[^\x21-\x7e]/', $name) and lib::length($name, [6, 24]) or exit('Access Denied');
		!preg_match('/[^\x21-\x7e]/', $pw) and lib::length($pw, [6, 24]) or exit('Access Denied');
		db::fetch('account', 'id', ['name' => $name]) and exit('-2');
		db::insert('account', ['name' => $name, 'pw' => md5($pw), 'ip' => lib::ip(), 'date' => date('Y-m-d H:i:s')]);
		echo '1';
	}
	public static function edit(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$slice = explode('/', $_SERVER['PATH_INFO']);
		$id = isset($slice[4]) ? intval($slice[4]) : 0;
		$account = db::fetch('account', '[]', ['id' => $id]);
		$account or exit(header('location:'.parent::PATH.'index.php/admin/account'));
		$tpl = new tpl('admin');
		$tpl->set('account', $account);
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('account_edit');
	}
	public static function update(){
		parent::login() or exit('-1');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		$id = intval(lib::method('account_id'));
		$pw = lib::method('account_rpw');
		!preg_match('/[^\x21-\x7e]/', $pw) and lib::length($pw, [6, 24]) or exit('Access Denied');
		db::update('account', ['pw' => md5($pw)], ['id' => $id]);
		echo '1';
	}
	public static function delete(){
		parent::login() or exit('-1');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		$id = lib::checkbox('checkbox');
		db::delete('account', ['id' => ['in', $id]]);
		echo '1';
	}
}
?>