<?php
namespace server\controller\admin;
use server\library\tpl;
use server\library\lib;
use server\library\db;
use server\model\admin as model;
class Buy extends model{
	public static function init(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		list($uri, $keyword, $init) = parent::search(32);
		$where = is_bool($keyword) ? [] : ['secret' => ['like', $keyword]];
		$mid = preg_match('/mid:(\d+)/i', $uri, $matches) ? $matches[1] : 0;
		$mid = $mid ? $mid > 1 ? '>' : '=' : '>=';
		$where = $where ?: array_merge($where, ['mid' => [$mid, 0]]);
		$mid_link = preg_replace('/\/keyword:[^\s][^\/]*|\/page:\d+|\/mid:\d+/i', '', $uri);
		$mid_link = $mid_link.$init.'mid:';
		$limit = preg_match('/limit:(\d+)/i', $uri, $matches) ? $matches[1] : 0;
		$limit = in_array($limit, [10, 30, 40, 50]) ? $limit : 20;
		$limit_link = preg_replace('/\/page:\d+|\/limit:\d+/i', '', $uri);
		$limit_link = $limit_link.$init.'limit:';
		list($count, $result, $page) = parent::page(['buy', '{}', $where, ['date', 'desc'], $limit, $uri]);
		$tpl = new tpl('admin');
		$tpl->set('keyword', $keyword);
		$tpl->set('mid', [$mid, $mid_link]);
		$tpl->set('limit', [$limit, $limit_link]);
		$tpl->set('count', $count);
		$tpl->set('result', $result);
		$tpl->set('page', $page);
		$tpl->set('name', function($mid){return db::fetch('member', 'name', ['id' => $mid], [], [], '-');});
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('buy');
	}
	public static function add(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		$tpl = new tpl('admin');
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('buy_add');
	}
	public static function insert(){
		parent::login() or exit('-1');
		lib::request(['token', $_COOKIE['account']]) or exit('Access Denied');
		$type = intval(lib::method('buy_type'));
		$point = intval(lib::method('buy_point'));
		preg_match('/^[1-9][0-9]{0,7}$/', $point) or exit('Access Denied');
		db::insert('buy', ['mid' => 0, 'secret' => md5(uniqid($type.'-'.$point.'-'.lib::ip().'-', true)), 'type' => $type < 2 ? 1 : 2, 'point' => $point, 'date' => date('Y-m-d H:i:s')]);
		echo '1';
	}
}
?>