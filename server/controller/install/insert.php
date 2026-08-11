<?php
namespace server\controller\install;
use server\library\tpl;
use server\model\install as model;
class Insert extends model{
	public static function init(){
		$tpl = new tpl('install');
		$tpl->set('const', ['name' => parent::NAME, 'site' => parent::SITE, 'path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('insert');
	}
}
?>