<?php
namespace server\controller\admin;
use server\library\tpl;
use server\library\lib;
use server\library\db;
use server\model\admin as model;
class Member extends model{
	public static function init(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		list($uri, $keyword, $init) = parent::search(32);
		$where = is_bool($keyword) ? [] : ['name' => ['like', $keyword]];
		$lid = preg_match('/lid:(\d+)/i', $uri, $matches) ? $matches[1] : 0;
		$lid = $lid ? $lid > 1 ? '>' : '=' : '>=';
		$where = $where ?: array_merge($where, ['lock' => [$lid, 0]]);
		$lid_link = preg_replace('/\/keyword:[^\s][^\/]*|\/page:\d+|\/lid:\d+/i', '', $uri);
		$lid_link = $lid_link.$init.'lid:';
		$limit = preg_match('/limit:(\d+)/i', $uri, $matches) ? $matches[1] : 0;
		$limit = in_array($limit, [10, 30, 40, 50]) ? $limit : 20;
		$limit_link = preg_replace('/\/page:\d+|\/limit:\d+/i', '', $uri);
		$limit_link = $limit_link.$init.'limit:';
		list($count, $result, $page) = parent::page(['member', '{}', $where, ['login', 'desc'], $limit, $uri]);
		$tpl = new tpl('admin');
		$tpl->set('keyword', $keyword);
		$tpl->set('lid', [$lid, $lid_link]);
		$tpl->set('limit', [$limit, $limit_link]);
		$tpl->set('count', $count);
		$tpl->set('result', $result);
		$tpl->set('page', $page);
		$tpl->set('avatar', function($uid){return parent::avatar($uid);});
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('member');
	}
	public static function add(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$tpl = new tpl('admin');
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('member_add');
	}
	public static function insert(){
		parent::login() or exit('-1');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		$name = lib::method('member_name');
		$pw = lib::method('member_rpw');
		preg_match('/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/', $name) and lib::length($name, 32) or exit('Access Denied');
		!preg_match('/[^\x21-\x7e]/', $pw) and lib::length($pw, [6, 24]) or exit('Access Denied');
		db::fetch('member', 'id', ['name' => $name]) and exit('-2');
		db::insert('member', ['name' => $name, 'pw' => md5($pw), 'ip' => lib::ip(), 'nick' => explode('@', $name)[0], 'gem' => 0, 'pea' => 0, 'lock' => 0, 'reg' => date('Y-m-d H:i:s'), 'login' => time()]);
		echo '1';
	}
	public static function edit(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$slice = explode('/', $_SERVER['PATH_INFO']);
		$id = isset($slice[4]) ? intval($slice[4]) : 0;
		$member = db::fetch('member', '[]', ['id' => $id]);
		$member or exit(header('location:'.parent::PATH.'index.php/admin/member'));
		$tpl = new tpl('admin');
		$tpl->set('member', $member);
		$tpl->set('avatar', parent::avatar($id));
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('member_edit');
	}
	public static function update(){
		parent::login() or exit('-1');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		$id = intval(lib::method('member_id'));
		$avatar = lib::method('member_avatar');
		$pw = lib::method('member_pw');
		$nick = lib::method('member_nick');
		$gem = intval(lib::method('member_gem'));
		$pea = intval(lib::method('member_pea'));
		$lock = lib::method('member_lock');
		!strlen($pw) or !preg_match('/[^\x21-\x7e]/', $pw) and lib::length($pw, [6, 24]) or exit('Access Denied');
		lib::length($nick, [1, 24]) or exit('Access Denied');
		!preg_match('/[^\d]/', $gem) and !preg_match('/^[0][0-9]{1,7}$/', $gem) and lib::length($gem, 8) or exit('Access Denied');
		!preg_match('/[^\d]/', $pea) and !preg_match('/^[0][0-9]{1,7}$/', $pea) and lib::length($pea, 8) or exit('Access Denied');
		$password = strlen($pw) ? md5($pw) : db::fetch('member', 'pw', ['id' => $id]);
		db::update('member', ['pw' => $password, 'nick' => $nick, 'gem' => $gem, 'pea' => $pea, 'lock' => $lock ? 1 : 0], ['id' => $id]);
		$avatar and @unlink(parent::ROOT.'upload/avatar/'.$id.'.png');
		echo '1';
	}
	public static function delete(){
		parent::login() or exit('-1');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		$id = lib::checkbox('checkbox');
		db::delete('member', ['id' => ['in', $id]]);
		foreach(explode(',', $id) as $uid){
			@unlink(parent::ROOT.'upload/avatar/'.$uid.'.png');
		}
		echo '1';
	}
}
?>