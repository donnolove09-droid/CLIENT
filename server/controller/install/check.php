<?php
namespace server\controller\install;
use server\library\tpl;
use server\model\install as model;
class Check extends model{
	public static function init(){
		$tpl = new tpl('install');
		$tpl->set('permit', function($path){return parent::permit($path);});
		$tpl->set('const', ['name' => parent::NAME, 'site' => parent::SITE, 'path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('check');
	}
}
?>