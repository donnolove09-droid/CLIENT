<?php
namespace server\controller\install;
use server\library\tpl;
use server\model\install as model;
class Read extends model{
	public static function init(){
		$tpl = new tpl('install');
		$tpl->set('const', ['name' => parent::NAME, 'site' => parent::SITE, 'version' => parent::VERSION, 'build' => parent::BUILD, 'path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('read');
	}
	public static function warn(){
		is_file(parent::ROOT.'upload/install.lock') or exit(header('location:'.parent::PATH.'index.php/install'));
		$tpl = new tpl('install');
		$tpl->set('const', ['name' => parent::NAME, 'site' => parent::SITE, 'path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('warn');
	}
}
?>