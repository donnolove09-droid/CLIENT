<?php
namespace server\library;
class tpl{
	private $cache;
	private $folder;
	private $var = [];
	private $root = BASE_ROOT;
	public function __construct($dir, $option=[]){
		if($option){
			$temp = $this->root.'upload/temp/cache';
			$this->cache = ['open' => $option['power'], 'dir' => $temp, 'file' => $temp.'/'.md5($option['marker'])];
			if($this->cache['open'] && is_file($this->cache['file']) && filemtime($this->cache['file']) > time() - $option['expire']){
				if($handle = fopen($this->cache['file'], 'r')){
					if(flock($handle, LOCK_SH)){
						$buffer = file_get_contents($this->cache['file']);
						flock($handle, LOCK_UN);
						fclose($handle);
						exit($buffer);
					}else{
						fclose($handle);
					}
				}
			}
		}
		$this->folder = $this->root.'client/view/'.$dir.'/htm/';
	}
	public function set($key, $val){
		$this->var[$key] = $val;
	}
	public function get($file){
		if(isset($this->cache['open']) && $this->cache['open']){
			ob_start();
			require $this->folder.$file.'.htm';
			is_dir($this->cache['dir']) or mkdir($this->cache['dir'], 0777, true);
			if($handle = fopen($this->cache['file'], 'w')){
				if(flock($handle, LOCK_EX)){
					ftruncate($handle, 0);
					fwrite($handle, ob_get_contents());
					flock($handle, LOCK_UN);
				}
				fclose($handle);
			}
			ob_end_flush();
		}else{
			require $this->folder.$file.'.htm';
		}
	}
}
?>