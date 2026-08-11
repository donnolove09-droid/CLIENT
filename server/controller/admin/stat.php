<?php
namespace server\controller\admin;
use server\library\tpl;
use server\library\db;
use server\model\admin as model;
class Stat extends model{
	public static function init(){
		parent::login() or exit(header('location:'.parent::PATH.'index.php/admin/login'));
		list($uri, $keyword, $init) = parent::search(15);
		$where = is_bool($keyword) ? [] : ['ip' => ['like', $keyword]];
		$date = preg_match('/date:(\d+)/i', $uri, $matches) ? $matches[1] : 0;
		$date = in_array($date, [1, 2, 3, 4, 5, 6]) ? '-'.$date : 0;
		$where = $where ?: array_merge($where, ['date' => ['date', '='.$date]]);
		$pv = db::fetch('stat', ['sum', 'pv'], ['date' => ['date', '='.$date]]);
		$date_link = preg_replace('/\/keyword:[^\s][^\/]*|\/page:\d+|\/date:\d+/i', '', $uri);
		$date_link = $date_link.$init.'date:';
		$limit = preg_match('/limit:(\d+)/i', $uri, $matches) ? $matches[1] : 0;
		$limit = in_array($limit, [10, 30, 40, 50]) ? $limit : 20;
		$limit_link = preg_replace('/\/page:\d+|\/limit:\d+/i', '', $uri);
		$limit_link = $limit_link.$init.'limit:';
		list($count, $result, $page) = parent::page(['stat', '{}', $where, ['date', 'desc'], $limit, $uri]);
		$tpl = new tpl('admin');
		$tpl->set('keyword', $keyword);
		$tpl->set('date', [$date, $date_link, $pv]);
		$tpl->set('limit', [$limit, $limit_link]);
		$tpl->set('count', $count);
		$tpl->set('result', $result);
		$tpl->set('page', $page);
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('stat');
	}
}
?>