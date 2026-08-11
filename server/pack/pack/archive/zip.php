<?php
namespace server\pack\archive;
class zip{
	private $file;
	private $errno;
	private $errstr;
	private $handle = 0;
	public function __construct($p_file){
		if(!function_exists('gzopen')){
			exit('Abort '.basename(__FILE__).' : Missing zlib extensions');
		}
		$this->file = $p_file;
	}
	public function import($p_dir){
		if($this->create($p_dir, 77003, $p_dir) == 0){
			return '('.$this->errno.') : '.$this->errstr;
		}
		return true;
	}
	public function export($p_dir, $p_regexp=[]){
		if($this->listContent() == 0){
			return '('.$this->errno.') : '.$this->errstr;
		}elseif($p_regexp){
			foreach($p_regexp as $v_pattern){
				$this->extract(77001, $p_dir, 77011, $v_pattern);
			}
		}else{
			$this->extract(77001, $p_dir, 77016);
		}
		return true;
	}
	private function create($p_filelist){
		$v_result = 1;
		$v_options = [];
		$v_options[77007] = false;
		$v_size = func_num_args();
		if($v_size > 1){
			$v_arg_list = func_get_args();
			array_shift($v_arg_list);
			$v_size--;
			if(is_int($v_arg_list[0]) && $v_arg_list[0] > 77000){
				$v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options, [77003 => 'optional', 77004 => 'optional', 77002 => 'optional', 78003 => 'optional', 78004 => 'optional', 77007 => 'optional', 77012 => 'optional', 77020 => 'optional', 77021 => 'optional', 77022 => 'optional']);
				if($v_result != 1){
					return 0;
				}
			}else{
				$v_options[77002] = $v_arg_list[0];
				if($v_size == 2){
					$v_options[77003] = $v_arg_list[1];
				}elseif($v_size > 2){
					$this->privErrorLog(-3, 'Invalid number / type of arguments');
					return 0;
				}
			}
		}
		$this->privOptionDefaultThreshold($v_options);
		$v_string_list = [];
		$v_att_list = [];
		$v_filedescr_list = [];
		$p_result_list = [];
		if(is_array($p_filelist)){
			if(isset($p_filelist[0]) && is_array($p_filelist[0])){
				$v_att_list = $p_filelist;
			}else{
				$v_string_list = $p_filelist;
			}
		}elseif(is_string($p_filelist)){
			$v_string_list = explode(',', $p_filelist);
		}else{
			$this->privErrorLog(-3, 'Invalid variable type p_filelist');
			return 0;
		}
		if(sizeof($v_string_list) != 0){
			foreach($v_string_list as $v_string){
				if($v_string != ''){
					$v_att_list[][79001] = $v_string;
				}
			}
		}
		$v_supported_attributes = [79001 => 'mandatory', 79002 => 'optional', 79003 => 'optional', 79004 => 'optional', 79005 => 'optional', 79006 => 'optional'];
		foreach($v_att_list as $v_entry){
			$v_result = $this->privFileDescrParseAtt($v_entry, $v_filedescr_list[], $v_options, $v_supported_attributes);
			if($v_result != 1){
				return 0;
			}
		}
		$v_result = $this->privFileDescrExpand($v_filedescr_list, $v_options);
		if($v_result != 1){
			return 0;
		}
		$v_result = $this->privCreate($v_filedescr_list, $p_result_list, $v_options);
		if($v_result != 1){
			return 0;
		}
		return $p_result_list;
	}
	private function listContent(){
		$v_result = 1;
		if(!$this->privCheckFormat()){
			return 0;
		}
		$p_list = [];
		if(($v_result = $this->privList($p_list)) != 1){
			unset($p_list);
			return 0;
		}
		return $p_list;
	}
	private function extract(){
		$v_result = 1;
		if(!$this->privCheckFormat()){
			return 0;
		}
		$v_options = [];
		$v_path = '';
		$v_remove_path = '';
		$v_remove_all_path = false;
		$v_size = func_num_args();
		$v_options[77006] = false;
		if($v_size > 0){
			$v_arg_list = func_get_args();
			if(is_int($v_arg_list[0]) && $v_arg_list[0] > 77000){
				$v_result = $this->privParseOptions($v_arg_list, $v_size, $v_options, [77001 => 'optional', 77003 => 'optional', 77004 => 'optional', 77002 => 'optional', 78001 => 'optional', 78002 => 'optional', 77005 => 'optional', 77008 => 'optional', 77010 => 'optional', 77011 => 'optional', 77009 => 'optional', 77006 => 'optional', 77015 => 'optional', 77016 => 'optional', 77017 => 'optional', 77019 => 'optional', 77020 => 'optional', 77021 => 'optional', 77022 => 'optional']);
				if($v_result != 1){
					return 0;
				}
				if(isset($v_options[77001])){
					$v_path = $v_options[77001];
				}
				if(isset($v_options[77003])){
					$v_remove_path = $v_options[77003];
				}
				if(isset($v_options[77004])){
					$v_remove_all_path = $v_options[77004];
				}
				if(isset($v_options[77002])){
					if(strlen($v_path) > 0 && substr($v_path, -1) != '/'){
						$v_path .= '/';
					}
					$v_path .= $v_options[77002];
				}
			}else{
				$v_path = $v_arg_list[0];
				if($v_size == 2){
					$v_remove_path = $v_arg_list[1];
				}elseif($v_size > 2){
					$this->privErrorLog(-3, 'Invalid number / type of arguments');
					return 0;
				}
			}
		}
		$this->privOptionDefaultThreshold($v_options);
		$p_list = [];
		$v_result = $this->privExtractByRule($p_list, $v_path, $v_remove_path, $v_remove_all_path, $v_options);
		if($v_result < 1){
			unset($p_list);
			return 0;
		}
		return $p_list;
	}
	private function privCheckFormat($p_level=0){
		$v_result = true;
		clearstatcache();
		if(!is_file($this->file)){
			$this->privErrorLog(-4, "Missing archive file '".$this->file."'");
			return false;
		}
		if(!is_readable($this->file)){
			$this->privErrorLog(-2, "Unable to read archive '".$this->file."'");
			return false;
		}
		return $v_result;
	}
	private function privParseOptions(&$p_options_list, $p_size, &$v_result_list, $v_requested_options=false){
		$v_result = 1;
		$i = 0;
		while($i < $p_size){
			if(!isset($v_requested_options[$p_options_list[$i]])){
				$this->privErrorLog(-3, "Invalid optional parameter '".$p_options_list[$i]."' for this method");
				return $this->errno;
			}
			switch($p_options_list[$i]){
				case 77001:
				case 77003:
				case 77002:
					if($i + 1 >= $p_size){
						$this->privErrorLog(-15, 'Missing parameter value for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					$v_result_list[$p_options_list[$i]] = $this->UtilTranslateWinPath($p_options_list[$i + 1], false);
					$i++;
					break;
				case 77020:
					if($i + 1 >= $p_size){
						$this->privErrorLog(-15, 'Missing parameter value for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					if(isset($v_result_list[77022])){
						$this->privErrorLog(-3, 'Option ('.$p_options_list[$i].") can not be used with option '77022'");
						return $this->errno;
					}
					$v_value = $p_options_list[$i + 1];
					if(!is_int($v_value) || $v_value < 0){
						$this->privErrorLog(-16, 'Integer expected for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					$v_result_list[$p_options_list[$i]] = $v_value * 1048576;
					$i++;
					break;
				case 77021:
					if(isset($v_result_list[77022])){
						$this->privErrorLog(-3, 'Option ('.$p_options_list[$i].") can not be used with option '77022'");
						return $this->errno;
					}
					$v_result_list[$p_options_list[$i]] = true;
					break;
				case 77022:
					if(isset($v_result_list[77021])){
						$this->privErrorLog(-3, 'Option ('.$p_options_list[$i].") can not be used with option '77021'");
						return $this->errno;
					}
					if(isset($v_result_list[77020])){
						$this->privErrorLog(-3, 'Option ('.$p_options_list[$i].") can not be used with option '77020'");
						return $this->errno;
					}
					$v_result_list[$p_options_list[$i]] = true;
					break;
				case 77019:
					if($i + 1 >= $p_size){
						$this->privErrorLog(-15, 'Missing parameter value for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					if(is_string($p_options_list[$i + 1]) && $p_options_list[$i + 1] != ''){
						$v_result_list[$p_options_list[$i]] = $this->UtilTranslateWinPath($p_options_list[$i + 1], false);
						$i++;
					}
					break;
				case 77008:
					if($i + 1 >= $p_size){
						$this->privErrorLog(-15, 'Missing parameter value for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					if(is_string($p_options_list[$i + 1])){
						$v_result_list[$p_options_list[$i]][0] = $p_options_list[$i + 1];
					}elseif(is_array($p_options_list[$i + 1])){
						$v_result_list[$p_options_list[$i]] = $p_options_list[$i + 1];
					}else{
						$this->privErrorLog(-16, 'Wrong parameter value for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					$i++;
					break;
				case 77010:
					$p_options_list[$i] = 77011;
				case 77011:
					if($i + 1 >= $p_size){
						$this->privErrorLog(-15, 'Missing parameter value for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					if(is_string($p_options_list[$i + 1])){
						$v_result_list[$p_options_list[$i]] = $p_options_list[$i + 1];
					}else{
						$this->privErrorLog(-16, 'Wrong parameter value for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					$i++;
					break;
				case 77012:
				case 77013:
				case 77014:
					if($i + 1 >= $p_size){
						$this->privErrorLog(-15, 'Missing parameter value for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					if(is_string($p_options_list[$i + 1])){
						$v_result_list[$p_options_list[$i]] = $p_options_list[$i + 1];
					}else{
						$this->privErrorLog(-16, 'Wrong parameter value for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					$i++;
					break;
				case 77009:
					if($i + 1 >= $p_size){
						$this->privErrorLog(-15, 'Missing parameter value for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					$v_work_list = [];
					if(is_string($p_options_list[$i + 1])){
						$p_options_list[$i + 1] = strtr($p_options_list[$i + 1], ' ', '');
						$v_work_list = explode(',', $p_options_list[$i + 1]);
					}elseif(is_int($p_options_list[$i + 1])){
						$v_work_list[0] = $p_options_list[$i + 1].'-'.$p_options_list[$i + 1];
					}elseif(is_array($p_options_list[$i + 1])){
						$v_work_list = $p_options_list[$i + 1];
					}else{
						$this->privErrorLog(-16, 'Value must be integer, string or array for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					$v_sort_value = 0;
					for($j = 0; $j < sizeof($v_work_list); $j++){
						$v_item_list = explode('-', $v_work_list[$j]);
						$v_size_item_list = sizeof($v_item_list);
						if($v_size_item_list == 1){
							$v_result_list[$p_options_list[$i]][$j]['start'] = $v_item_list[0];
							$v_result_list[$p_options_list[$i]][$j]['end'] = $v_item_list[0];
						}elseif($v_size_item_list == 2){
							$v_result_list[$p_options_list[$i]][$j]['start'] = $v_item_list[0];
							$v_result_list[$p_options_list[$i]][$j]['end'] = $v_item_list[1];
						}else{
							$this->privErrorLog(-16, 'Too many values in index range for option ('.$p_options_list[$i].')');
							return $this->errno;
						}
						if($v_result_list[$p_options_list[$i]][$j]['start'] < $v_sort_value){
							$this->privErrorLog(-16, 'Invalid order of index range for option ('.$p_options_list[$i].')');
							return $this->errno;
						}
						$v_sort_value = $v_result_list[$p_options_list[$i]][$j]['start'];
					}
					$i++;
					break;
				case 77004:
				case 77006:
				case 77007:
				case 77015:
				case 77016:
				case 77017:
					$v_result_list[$p_options_list[$i]] = true;
					break;
				case 77005:
					if($i + 1 >= $p_size){
						$this->privErrorLog(-15, 'Missing parameter value for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					$v_result_list[$p_options_list[$i]] = $p_options_list[$i + 1];
					$i++;
					break;
				case 78001:
				case 78002:
				case 78003:
				case 78004:
					if($i + 1 >= $p_size){
						$this->privErrorLog(-15, 'Missing parameter value for option ('.$p_options_list[$i].')');
						return $this->errno;
					}
					$v_function_name = $p_options_list[$i + 1];
					if(!function_exists($v_function_name)){
						$this->privErrorLog(-16, "Function '".$v_function_name."()' is not an existing function for option (".$p_options_list[$i].')');
						return $this->errno;
					}
					$v_result_list[$p_options_list[$i]] = $v_function_name;
					$i++;
					break;
				default:
					$this->privErrorLog(-3, "Unknown parameter '".$p_options_list[$i]."'");
					return $this->errno;
			}
			$i++;
		}
		if($v_requested_options !== false){
			for($key = reset($v_requested_options); $key = key($v_requested_options); $key = next($v_requested_options)){
				if($v_requested_options[$key] == 'mandatory'){
					if(!isset($v_result_list[$key])){
						$this->privErrorLog(-3, 'Missing mandatory parameter ('.$key.')');
						return $this->errno;
					}
				}
			}
		}
		return $v_result;
	}
	private function privOptionDefaultThreshold(&$p_options){
		$v_result = 1;
		if(isset($p_options[77020]) || isset($p_options[77022])){
			return $v_result;
		}
		$v_memory_limit = ini_get('memory_limit');
		$v_memory_limit = trim($v_memory_limit);
		$last = strtolower(substr($v_memory_limit, -1));
		if($last == 'g'){
			$v_memory_limit = intval($v_memory_limit) * 1073741824;
		}
		if($last == 'm'){
			$v_memory_limit = intval($v_memory_limit) * 1048576;
		}
		if($last == 'k'){
			$v_memory_limit = intval($v_memory_limit) * 1024;
		}
		$p_options[77020] = floor(intval($v_memory_limit) * 0.47);
		if($p_options[77020] < 1048576){
			unset($p_options[77020]);
		}
		return $v_result;
	}
	private function privFileDescrParseAtt(&$p_file_list, &$p_filedescr, $v_options, $v_requested_options=false){
		$v_result = 1;
		foreach($p_file_list as $v_key => $v_value){
			if(!isset($v_requested_options[$v_key])){
				$this->privErrorLog(-3, "Invalid file attribute '".$v_key."' for this file");
				return $this->errno;
			}
			switch($v_key){
				case 79001:
					if(!is_string($v_value)){
						$this->privErrorLog(-20, 'Invalid type '.gettype($v_value).'. String expected for attribute ('.$v_key.')');
						return $this->errno;
					}
					$p_filedescr['filename'] = $this->UtilPathReduction($v_value);
					if($p_filedescr['filename'] == ''){
						$this->privErrorLog(-20, 'Invalid empty filename for attribute ('.$v_key.')');
						return $this->errno;
					}
					break;
				case 79002:
					if(!is_string($v_value)){
						$this->privErrorLog(-20, 'Invalid type '.gettype($v_value).'. String expected for attribute ('.$v_key.')');
						return $this->errno;
					}
					$p_filedescr['new_short_name'] = $this->UtilPathReduction($v_value);
					if($p_filedescr['new_short_name'] == ''){
						$this->privErrorLog(-20, 'Invalid empty short filename for attribute ('.$v_key.')');
						return $this->errno;
					}
					break;
				case 79003:
					if(!is_string($v_value)){
						$this->privErrorLog(-20, 'Invalid type '.gettype($v_value).'. String expected for attribute ('.$v_key.')');
						return $this->errno;
					}
					$p_filedescr['new_full_name'] = $this->UtilPathReduction($v_value);
					if($p_filedescr['new_full_name'] == ''){
						$this->privErrorLog(-20, 'Invalid empty full filename for attribute ('.$v_key.')');
						return $this->errno;
					}
					break;
				case 79006:
					if(!is_string($v_value)){
						$this->privErrorLog(-20, 'Invalid type '.gettype($v_value).'. String expected for attribute ('.$v_key.')');
						return $this->errno;
					}
					$p_filedescr['comment'] = $v_value;
					break;
				case 79004:
					if(!is_int($v_value)){
						$this->privErrorLog(-20, 'Invalid type '.gettype($v_value).'. Integer expected for attribute ('.$v_key.')');
						return $this->errno;
					}
					$p_filedescr['mtime'] = $v_value;
					break;
				case 79005:
					$p_filedescr['content'] = $v_value;
					break;
				default:
					$this->privErrorLog(-3, "Unknown parameter '".$v_key."'");
					return $this->errno;
			}
			if($v_requested_options !== false){
				for($key = reset($v_requested_options); $key = key($v_requested_options); $key = next($v_requested_options)){
					if($v_requested_options[$key] == 'mandatory'){
						if(!isset($p_file_list[$key])){
							$this->privErrorLog(-3, 'Missing mandatory parameter ('.$key.')');
							return $this->errno;
						}
					}
				}
			}
		}
		return $v_result;
	}
	private function privFileDescrExpand(&$p_filedescr_list, &$p_options){
		$v_result = 1;
		$v_result_list = [];
		for($i = 0; $i < sizeof($p_filedescr_list); $i++){
			$v_descr = $p_filedescr_list[$i];
			$v_descr['filename'] = $this->UtilTranslateWinPath($v_descr['filename'], false);
			$v_descr['filename'] = $this->UtilPathReduction($v_descr['filename']);
			if(file_exists($v_descr['filename'])){
				if(is_file($v_descr['filename'])){
					$v_descr['type'] = 'file';
				}elseif(is_dir($v_descr['filename'])){
					$v_descr['type'] = 'folder';
				}elseif(is_link($v_descr['filename'])){
					continue;
				}else{
					continue;
				}
			}elseif(isset($v_descr['content'])){
				$v_descr['type'] = 'virtual_file';
			}else{
				$this->privErrorLog(-4, "File '".$v_descr['filename']."' does not exist");
				return $this->errno;
			}
			$this->privCalculateStoredFilename($v_descr, $p_options);
			$v_result_list[sizeof($v_result_list)] = $v_descr;
			if($v_descr['type'] == 'folder'){
				$v_dirlist_descr = [];
				$v_dirlist_nb = 0;
				if($v_folder_handler = @opendir($v_descr['filename'])){
					while(($v_item_handler = @readdir($v_folder_handler)) !== false){
						if($v_item_handler == '.' || $v_item_handler == '..'){
							continue;
						}
						$v_dirlist_descr[$v_dirlist_nb]['filename'] = $v_descr['filename'].'/'.$v_item_handler;
						if($v_descr['stored_filename'] != $v_descr['filename'] && !isset($p_options[77004])){
							if($v_descr['stored_filename'] != ''){
								$v_dirlist_descr[$v_dirlist_nb]['new_full_name'] = $v_descr['stored_filename'].'/'.$v_item_handler;
							}else{
								$v_dirlist_descr[$v_dirlist_nb]['new_full_name'] = $v_item_handler;
							}
						}
						$v_dirlist_nb++;
					}
					@closedir($v_folder_handler);
				}
				if($v_dirlist_nb != 0){
					if(($v_result = $this->privFileDescrExpand($v_dirlist_descr, $p_options)) != 1){
						return $v_result;
					}
					$v_result_list = array_merge($v_result_list, $v_dirlist_descr);
				}
				unset($v_dirlist_descr);
			}
		}
		$p_filedescr_list = $v_result_list;
		return $v_result;
	}
	private function privCreate($p_filedescr_list, &$p_result_list, &$p_options){
		$v_result = 1;
		$v_list_detail = [];
		if(($v_result = $this->privOpenFd('wb')) != 1){
			return $v_result;
		}
		$v_result = $this->privAddList($p_filedescr_list, $p_result_list, $p_options);
		$this->privCloseFd();
		return $v_result;
	}
	private function privOpenFd($p_mode){
		$v_result = 1;
		if($this->handle != 0){
			$this->privErrorLog(-2, "Zip file '".$this->file."' already open");
			return $this->errno;
		}
		if(($this->handle = @fopen($this->file, $p_mode)) == 0){
			$this->privErrorLog(-2, "Unable to open archive '".$this->file."' in ".$p_mode.' mode');
			return $this->errno;
		}
		return $v_result;
	}
	private function privCloseFd(){
		$v_result = 1;
		if($this->handle != 0){
			@fclose($this->handle);
		}
		$this->handle = 0;
		return $v_result;
	}
	private function privAddList($p_filedescr_list, &$p_result_list, &$p_options){
		$v_result = 1;
		$v_header_list = [];
		if(($v_result = $this->privAddFileList($p_filedescr_list, $v_header_list, $p_options)) != 1){
			return $v_result;
		}
		$v_offset = @ftell($this->handle);
		for($i = 0, $v_count = 0; $i < sizeof($v_header_list); $i++){
			if($v_header_list[$i]['status'] == 'ok'){
				if(($v_result = $this->privWriteCentralFileHeader($v_header_list[$i])) != 1){
					return $v_result;
				}
				$v_count++;
			}
			$this->privConvertHeader2FileInfo($v_header_list[$i], $p_result_list[$i]);
		}
		$v_comment = '';
		if(isset($p_options[77012])){
			$v_comment = $p_options[77012];
		}
		$v_size = @ftell($this->handle) - $v_offset;
		if(($v_result = $this->privWriteCentralHeader($v_count, $v_size, $v_offset, $v_comment)) != 1){
			unset($v_header_list);
			return $v_result;
		}
		return $v_result;
	}
	private function privAddFileList($p_filedescr_list, &$p_result_list, &$p_options){
		$v_result = 1;
		$v_header = [];
		$v_nb = sizeof($p_result_list);
		for($j = 0; $j < sizeof($p_filedescr_list) && $v_result == 1; $j++){
			$p_filedescr_list[$j]['filename'] = $this->UtilTranslateWinPath($p_filedescr_list[$j]['filename'], false);
			if($p_filedescr_list[$j]['filename'] == ''){
				continue;
			}
			if($p_filedescr_list[$j]['type'] != 'virtual_file' && !file_exists($p_filedescr_list[$j]['filename'])){
				$this->privErrorLog(-4, "File '".$p_filedescr_list[$j]['filename']."' does not exist");
				return $this->errno;
			}
			if($p_filedescr_list[$j]['type'] == 'file' || $p_filedescr_list[$j]['type'] == 'virtual_file' || $p_filedescr_list[$j]['type'] == 'folder' && (!isset($p_options[77004]) || !$p_options[77004])){
				$v_result = $this->privAddFile($p_filedescr_list[$j], $v_header, $p_options);
				if($v_result != 1){
					return $v_result;
				}
				$p_result_list[$v_nb++] = $v_header;
			}
		}
		return $v_result;
	}
	private function privAddFile($p_filedescr, &$p_header, &$p_options){
		$v_result = 1;
		$p_filename = $p_filedescr['filename'];
		if($p_filename == ''){
			$this->privErrorLog(-3, 'Invalid file list parameter (invalid or empty list)');
			return $this->errno;
		}
		clearstatcache();
		$p_header['version'] = 20;
		$p_header['version_extracted'] = 10;
		$p_header['flag'] = 0;
		$p_header['compression'] = 0;
		$p_header['crc'] = 0;
		$p_header['compressed_size'] = 0;
		$p_header['filename_len'] = strlen($p_filename);
		$p_header['extra_len'] = 0;
		$p_header['disk'] = 0;
		$p_header['internal'] = 0;
		$p_header['offset'] = 0;
		$p_header['filename'] = $p_filename;
		$p_header['stored_filename'] = $p_filedescr['stored_filename'];
		$p_header['extra'] = '';
		$p_header['status'] = 'ok';
		$p_header['index'] = -1;
		if($p_filedescr['type'] == 'file'){
			$p_header['external'] = 0x0;
			$p_header['size'] = filesize($p_filename);
		}elseif($p_filedescr['type'] == 'folder'){
			$p_header['external'] = 0x10;
			$p_header['mtime'] = filemtime($p_filename);
			$p_header['size'] = filesize($p_filename);
		}elseif($p_filedescr['type'] == 'virtual_file'){
			$p_header['external'] = 0x0;
			$p_header['size'] = strlen($p_filedescr['content']);
		}
		if(isset($p_filedescr['mtime'])){
			$p_header['mtime'] = $p_filedescr['mtime'];
		}elseif($p_filedescr['type'] == 'virtual_file'){
			$p_header['mtime'] = time();
		}else{
			$p_header['mtime'] = filemtime($p_filename);
		}
		if(isset($p_filedescr['comment'])){
			$p_header['comment_len'] = strlen($p_filedescr['comment']);
			$p_header['comment'] = $p_filedescr['comment'];
		}else{
			$p_header['comment_len'] = 0;
			$p_header['comment'] = '';
		}
		if(isset($p_options[78003])){
			$v_local_header = [];
			$this->privConvertHeader2FileInfo($p_header, $v_local_header);
			$v_result = $p_options[78003](78003, $v_local_header);
			if($v_result == 0){
				$p_header['status'] = 'skipped';
				$v_result = 1;
			}
			if($p_header['stored_filename'] != $v_local_header['stored_filename']){
				$p_header['stored_filename'] = $this->UtilPathReduction($v_local_header['stored_filename']);
			}
		}
		if($p_header['stored_filename'] == ''){
			$p_header['status'] = 'filtered';
		}
		if(strlen($p_header['stored_filename']) > 0xff){
			$p_header['status'] = 'filename_too_long';
		}
		if($p_header['status'] == 'ok'){
			if($p_filedescr['type'] == 'file'){
				if(!isset($p_options[77022]) && (isset($p_options[77021]) || isset($p_options[77020]) && $p_options[77020] <= $p_header['size'])){
					$v_result = $this->privAddFileUsingTempFile($p_filedescr, $p_header, $p_options);
					if($v_result < 0){
						return $v_result;
					}
				}else{
					if(($v_file = @fopen($p_filename, 'rb')) == 0){
						$this->privErrorLog(-2, "Unable to open file '".$p_filename."' in binary read mode");
						return $this->errno;
					}
					$v_content = @fread($v_file, $p_header['size']);
					@fclose($v_file);
					$p_header['crc'] = @crc32($v_content);
					if($p_options[77007]){
						$p_header['compressed_size'] = $p_header['size'];
						$p_header['compression'] = 0;
					}else{
						$v_content = @gzdeflate($v_content);
						$p_header['compressed_size'] = strlen($v_content);
						$p_header['compression'] = 8;
					}
					if(($v_result = $this->privWriteFileHeader($p_header)) != 1){
						@fclose($v_file);
						return $v_result;
					}
					@fwrite($this->handle, $v_content, $p_header['compressed_size']);
				}
			}else{
				if($p_filedescr['type'] == 'virtual_file'){
					$v_content = $p_filedescr['content'];
					$p_header['crc'] = @crc32($v_content);
					if($p_options[77007]){
						$p_header['compressed_size'] = $p_header['size'];
						$p_header['compression'] = 0;
					}else{
						$v_content = @gzdeflate($v_content);
						$p_header['compressed_size'] = strlen($v_content);
						$p_header['compression'] = 8;
					}
					if(($v_result = $this->privWriteFileHeader($p_header)) != 1){
						@fclose($v_file);
						return $v_result;
					}
					@fwrite($this->handle, $v_content, $p_header['compressed_size']);
				}else{
					if($p_filedescr['type'] == 'folder'){
						if(substr($p_header['stored_filename'], -1) != '/'){
							$p_header['stored_filename'] .= '/';
						}
						$p_header['size'] = 0;
						$p_header['external'] = 0x10;
						if(($v_result = $this->privWriteFileHeader($p_header)) != 1){
							return $v_result;
						}
					}
				}
			}
		}
		if(isset($p_options[78004])){
			$v_local_header = [];
			$this->privConvertHeader2FileInfo($p_header, $v_local_header);
			$v_result = $p_options[78004](78004, $v_local_header);
			if($v_result == 0){
				$v_result = 1;
			}
		}
		return $v_result;
	}
	private function privAddFileUsingTempFile($p_filedescr, &$p_header, &$p_options){
		$v_result = 0;
		$p_filename = $p_filedescr['filename'];
		if(($v_file = @fopen($p_filename, 'rb')) == 0){
			$this->privErrorLog(-2, "Unable to open file '".$p_filename."' in binary read mode");
			return $this->errno;
		}
		$v_gzip_temp_name = uniqid('zip-', true).'.gz';
		if(($v_file_compressed = @gzopen($v_gzip_temp_name, 'wb')) == 0){
			fclose($v_file);
			$this->privErrorLog(-1, "Unable to open temporary file '".$v_gzip_temp_name."' in binary write mode");
			return $this->errno;
		}
		$v_size = filesize($p_filename);
		while($v_size != 0){
			$v_read_size = $v_size < 2048 ? $v_size : 2048;
			$v_buffer = @fread($v_file, $v_read_size);
			@gzwrite($v_file_compressed, $v_buffer, $v_read_size);
			$v_size -= $v_read_size;
		}
		@fclose($v_file);
		@gzclose($v_file_compressed);
		if(filesize($v_gzip_temp_name) < 18){
			$this->privErrorLog(-10, "gzip temporary file '".$v_gzip_temp_name."' has invalid filesize - should be minimum 18 bytes");
			return $this->errno;
		}
		if(($v_file_compressed = @fopen($v_gzip_temp_name, 'rb')) == 0){
			$this->privErrorLog(-2, "Unable to open temporary file '".$v_gzip_temp_name."' in binary read mode");
			return $this->errno;
		}
		$v_binary_data = @fread($v_file_compressed, 10);
		$v_data_header = unpack('a1id1/a1id2/a1cm/a1flag/Vmtime/a1xfl/a1os', $v_binary_data);
		$v_data_header['os'] = bin2hex($v_data_header['os']);
		@fseek($v_file_compressed, filesize($v_gzip_temp_name) - 8);
		$v_binary_data = @fread($v_file_compressed, 8);
		$v_data_footer = unpack('Vcrc/Vcompressed_size', $v_binary_data);
		$p_header['compression'] = ord($v_data_header['cm']);
		$p_header['crc'] = $v_data_footer['crc'];
		$p_header['compressed_size'] = filesize($v_gzip_temp_name) - 18;
		@fclose($v_file_compressed);
		if(($v_result = $this->privWriteFileHeader($p_header)) != 1){
			return $v_result;
		}
		if(($v_file_compressed = @fopen($v_gzip_temp_name, 'rb')) == 0){
			$this->privErrorLog(-2, "Unable to open temporary file '".$v_gzip_temp_name."' in binary read mode");
			return $this->errno;
		}
		fseek($v_file_compressed, 10);
		$v_size = $p_header['compressed_size'];
		while($v_size != 0){
			$v_read_size = $v_size < 2048 ? $v_size : 2048;
			$v_buffer = @fread($v_file_compressed, $v_read_size);
			@fwrite($this->handle, $v_buffer, $v_read_size);
			$v_size -= $v_read_size;
		}
		@fclose($v_file_compressed);
		@unlink($v_gzip_temp_name);
		return $v_result;
	}
	private function privCalculateStoredFilename(&$p_filedescr, &$p_options){
		$v_result = 1;
		$p_filename = $p_filedescr['filename'];
		if(isset($p_options[77002])){
			$p_add_dir = $p_options[77002];
		}else{
			$p_add_dir = '';
		}
		if(isset($p_options[77003])){
			$p_remove_dir = $p_options[77003];
		}else{
			$p_remove_dir = '';
		}
		if(isset($p_options[77004])){
			$p_remove_all_dir = $p_options[77004];
		}else{
			$p_remove_all_dir = 0;
		}
		if(isset($p_filedescr['new_full_name'])){
			$v_stored_filename = $this->UtilTranslateWinPath($p_filedescr['new_full_name']);
		}else{
			if(isset($p_filedescr['new_short_name'])){
				$v_path_info = pathinfo($p_filename);
				$v_dir = '';
				if($v_path_info['dirname'] != ''){
					$v_dir = $v_path_info['dirname'].'/';
				}
				$v_stored_filename = $v_dir.$p_filedescr['new_short_name'];
			}else{
				$v_stored_filename = $p_filename;
			}
			if($p_remove_all_dir){
				$v_stored_filename = basename($p_filename);
			}else{
				if($p_remove_dir != ''){
					if(substr($p_remove_dir, -1) != '/'){
						$p_remove_dir .= '/';
					}
					if(substr($p_filename, 0, 2) == './' || substr($p_remove_dir, 0, 2) == './'){
						if(substr($p_filename, 0, 2) == './' && substr($p_remove_dir, 0, 2) != './'){
							$p_remove_dir = './'.$p_remove_dir;
						}
						if(substr($p_filename, 0, 2) != './' && substr($p_remove_dir, 0, 2) == './'){
							$p_remove_dir = substr($p_remove_dir, 2);
						}
					}
					$v_compare = $this->UtilPathInclusion($p_remove_dir, $v_stored_filename);
					if($v_compare > 0){
						if($v_compare == 2){
							$v_stored_filename = '';
						}else{
							$v_stored_filename = substr($v_stored_filename, strlen($p_remove_dir));
						}
					}
				}
			}
			$v_stored_filename = $this->UtilTranslateWinPath($v_stored_filename);
			if($p_add_dir != ''){
				if(substr($p_add_dir, -1) == '/'){
					$v_stored_filename = $p_add_dir.$v_stored_filename;
				}else{
					$v_stored_filename = $p_add_dir.'/'.$v_stored_filename;
				}
			}
		}
		$v_stored_filename = $this->UtilPathReduction($v_stored_filename);
		$p_filedescr['stored_filename'] = $v_stored_filename;
		return $v_result;
	}
	private function privWriteFileHeader(&$p_header){
		$v_result = 1;
		$p_header['offset'] = ftell($this->handle);
		$v_date = getdate($p_header['mtime']);
		$v_mtime = ($v_date['hours'] << 11) + ($v_date['minutes'] << 5) + $v_date['seconds'] / 2;
		$v_mdate = ($v_date['year'] - 1980 << 9) + ($v_date['mon'] << 5) + $v_date['mday'];
		$v_binary_data = pack('VvvvvvVVVvv', 0x4034b50, $p_header['version_extracted'], $p_header['flag'], $p_header['compression'], $v_mtime, $v_mdate, $p_header['crc'], $p_header['compressed_size'], $p_header['size'], strlen($p_header['stored_filename']), $p_header['extra_len']);
		fputs($this->handle, $v_binary_data, 30);
		if(strlen($p_header['stored_filename']) != 0){
			fputs($this->handle, $p_header['stored_filename'], strlen($p_header['stored_filename']));
		}
		if($p_header['extra_len'] != 0){
			fputs($this->handle, $p_header['extra'], $p_header['extra_len']);
		}
		return $v_result;
	}
	private function privWriteCentralFileHeader(&$p_header){
		$v_result = 1;
		$v_date = getdate($p_header['mtime']);
		$v_mtime = ($v_date['hours'] << 11) + ($v_date['minutes'] << 5) + $v_date['seconds'] / 2;
		$v_mdate = ($v_date['year'] - 1980 << 9) + ($v_date['mon'] << 5) + $v_date['mday'];
		$v_binary_data = pack('VvvvvvvVVVvvvvvVV', 0x2014b50, $p_header['version'], $p_header['version_extracted'], $p_header['flag'], $p_header['compression'], $v_mtime, $v_mdate, $p_header['crc'], $p_header['compressed_size'], $p_header['size'], strlen($p_header['stored_filename']), $p_header['extra_len'], $p_header['comment_len'], $p_header['disk'], $p_header['internal'], $p_header['external'], $p_header['offset']);
		fputs($this->handle, $v_binary_data, 46);
		if(strlen($p_header['stored_filename']) != 0){
			fputs($this->handle, $p_header['stored_filename'], strlen($p_header['stored_filename']));
		}
		if($p_header['extra_len'] != 0){
			fputs($this->handle, $p_header['extra'], $p_header['extra_len']);
		}
		if($p_header['comment_len'] != 0){
			fputs($this->handle, $p_header['comment'], $p_header['comment_len']);
		}
		return $v_result;
	}
	private function privWriteCentralHeader($p_nb_entries, $p_size, $p_offset, $p_comment){
		$v_result = 1;
		$v_binary_data = pack('VvvvvVVv', 0x6054b50, 0, 0, $p_nb_entries, $p_nb_entries, $p_size, $p_offset, strlen($p_comment));
		fputs($this->handle, $v_binary_data, 22);
		if(strlen($p_comment) != 0){
			fputs($this->handle, $p_comment, strlen($p_comment));
		}
		return $v_result;
	}
	private function privList(&$p_list){
		$v_result = 1;
		if(($this->handle = @fopen($this->file, 'rb')) == 0){
			$this->privErrorLog(-2, "Unable to open archive '".$this->file."' in binary read mode");
			return $this->errno;
		}
		$v_central_dir = [];
		if(($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1){
			return $v_result;
		}
		@rewind($this->handle);
		if(@fseek($this->handle, $v_central_dir['offset'])){
			$this->privErrorLog(-14, 'Invalid archive size');
			return $this->errno;
		}
		for($i = 0; $i < $v_central_dir['entries']; $i++){
			if(($v_result = $this->privReadCentralFileHeader($v_header)) != 1){
				return $v_result;
			}
			$v_header['index'] = $i;
			$this->privConvertHeader2FileInfo($v_header, $p_list[$i]);
			unset($v_header);
		}
		$this->privCloseFd();
		return $v_result;
	}
	private function privConvertHeader2FileInfo($p_header, &$p_info){
		$v_result = 1;
		$v_temp_path = $this->UtilPathReduction($p_header['filename']);
		$p_info['filename'] = $v_temp_path;
		$v_temp_path = $this->UtilPathReduction($p_header['stored_filename']);
		$p_info['stored_filename'] = $v_temp_path;
		$p_info['size'] = $p_header['size'];
		$p_info['compressed_size'] = $p_header['compressed_size'];
		$p_info['mtime'] = $p_header['mtime'];
		$p_info['comment'] = $p_header['comment'];
		$p_info['folder'] = ($p_header['external'] & 0x10) == 0x10;
		$p_info['index'] = $p_header['index'];
		$p_info['status'] = $p_header['status'];
		$p_info['crc'] = $p_header['crc'];
		return $v_result;
	}
	private function privExtractByRule(&$p_file_list, $p_path, $p_remove_path, $p_remove_all_path, &$p_options){
		$v_result = 1;
		if($p_path == '' || substr($p_path, 0, 1) != '/' && substr($p_path, 0, 3) != '../' && substr($p_path, 1, 2) != ':/'){
			$p_path = './'.$p_path;
		}
		if($p_path != './' && $p_path != '/'){
			while(substr($p_path, -1) == '/'){
				$p_path = substr($p_path, 0, strlen($p_path) - 1);
			}
		}
		if($p_remove_path != '' && substr($p_remove_path, -1) != '/'){
			$p_remove_path .= '/';
		}
		$p_remove_path_size = strlen($p_remove_path);
		if(($v_result = $this->privOpenFd('rb')) != 1){
			return $v_result;
		}
		$v_central_dir = [];
		if(($v_result = $this->privReadEndCentralDir($v_central_dir)) != 1){
			$this->privCloseFd();
			return $v_result;
		}
		$v_pos_entry = $v_central_dir['offset'];
		$j_start = 0;
		for($i = 0, $v_nb_extracted = 0; $i < $v_central_dir['entries']; $i++){
			@rewind($this->handle);
			if(@fseek($this->handle, $v_pos_entry)){
				$this->privCloseFd();
				$this->privErrorLog(-14, 'Invalid archive size');
				return $this->errno;
			}
			$v_header = [];
			if(($v_result = $this->privReadCentralFileHeader($v_header)) != 1){
				$this->privCloseFd();
				return $v_result;
			}
			$v_header['index'] = $i;
			$v_pos_entry = ftell($this->handle);
			$v_extract = false;
			if(isset($p_options[77008]) && $p_options[77008] != 0){
				for($j = 0; $j < sizeof($p_options[77008]) && !$v_extract; $j++){
					if(substr($p_options[77008][$j], -1) == '/'){
						if(strlen($v_header['stored_filename']) > strlen($p_options[77008][$j]) && substr($v_header['stored_filename'], 0, strlen($p_options[77008][$j])) == $p_options[77008][$j]){
							$v_extract = true;
						}
					}elseif($v_header['stored_filename'] == $p_options[77008][$j]){
						$v_extract = true;
					}
				}
			}elseif(isset($p_options[77011]) && $p_options[77011] != ''){
				if(preg_match($p_options[77011], $v_header['stored_filename'])){
					$v_extract = true;
				}
			}elseif(isset($p_options[77009]) && $p_options[77009] != 0){
				for($j = $j_start; $j < sizeof($p_options[77009]) && !$v_extract; $j++){
					if($i >= $p_options[77009][$j]['start'] && $i <= $p_options[77009][$j]['end']){
						$v_extract = true;
					}
					if($i >= $p_options[77009][$j]['end']){
						$j_start = $j + 1;
					}
					if($p_options[77009][$j]['start'] > $i){
						break;
					}
				}
			}else{
				$v_extract = true;
			}
			if($v_extract && ($v_header['compression'] != 8 && $v_header['compression'] != 0)){
				$v_header['status'] = 'unsupported_compression';
				if(isset($p_options[77017]) && $p_options[77017] === true){
					$this->privErrorLog(-18, "Filename '".$v_header['stored_filename']."' is compressed by an unsupported compression method (".$v_header['compression'].')');
					return $this->errno;
				}
			}
			if($v_extract && ($v_header['flag'] & 1) == 1){
				$v_header['status'] = 'unsupported_encryption';
				if(isset($p_options[77017]) && $p_options[77017] === true){
					$this->privErrorLog(-19, "Unsupported encryption for filename '".$v_header['stored_filename']."'");
					return $this->errno;
				}
			}
			if($v_extract && $v_header['status'] != 'ok'){
				$v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted++]);
				if($v_result != 1){
					$this->privCloseFd();
					return $v_result;
				}
				$v_extract = false;
			}
			if($v_extract){
				@rewind($this->handle);
				if(@fseek($this->handle, $v_header['offset'])){
					$this->privCloseFd();
					$this->privErrorLog(-14, 'Invalid archive size');
					return $this->errno;
				}
				if($p_options[77006]){
					$v_string = '';
					$v_result1 = $this->privExtractFileAsString($v_header, $v_string, $p_options);
					if($v_result1 < 1){
						$this->privCloseFd();
						return $v_result1;
					}
					if(($v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted])) != 1){
						$this->privCloseFd();
						return $v_result;
					}
					$p_file_list[$v_nb_extracted]['content'] = $v_string;
					$v_nb_extracted++;
					if($v_result1 == 2){
						break;
					}
				}elseif(isset($p_options[77015]) && $p_options[77015]){
					$v_result1 = $this->privExtractFileInOutput($v_header, $p_options);
					if($v_result1 < 1){
						$this->privCloseFd();
						return $v_result1;
					}
					if(($v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted++])) != 1){
						$this->privCloseFd();
						return $v_result;
					}
					if($v_result1 == 2){
						break;
					}
				}else{
					$v_result1 = $this->privExtractFile($v_header, $p_path, $p_remove_path, $p_remove_all_path, $p_options);
					if($v_result1 < 1){
						$this->privCloseFd();
						return $v_result1;
					}
					if(($v_result = $this->privConvertHeader2FileInfo($v_header, $p_file_list[$v_nb_extracted++])) != 1){
						$this->privCloseFd();
						return $v_result;
					}
					if($v_result1 == 2){
						break;
					}
				}
			}
		}
		$this->privCloseFd();
		return $v_result;
	}
	private function privExtractFile(&$p_entry, $p_path, $p_remove_path, $p_remove_all_path, &$p_options){
		$v_result = 1;
		if(($v_result = $this->privReadFileHeader($v_header)) != 1){
			return $v_result;
		}
		$this->privCheckFileHeaders($v_header, $p_entry);
		if($p_remove_all_path == true){
			if(($p_entry['external'] & 0x10) == 0x10){
				$p_entry['status'] = 'filtered';
				return $v_result;
			}
			$p_entry['filename'] = basename($p_entry['filename']);
		}elseif($p_remove_path != ''){
			if($this->UtilPathInclusion($p_remove_path, $p_entry['filename']) == 2){
				$p_entry['status'] = 'filtered';
				return $v_result;
			}
			$p_remove_path_size = strlen($p_remove_path);
			if(substr($p_entry['filename'], 0, $p_remove_path_size) == $p_remove_path){
				$p_entry['filename'] = substr($p_entry['filename'], $p_remove_path_size);
			}
		}
		if($p_path != ''){
			$p_entry['filename'] = $p_path.'/'.$p_entry['filename'];
		}
		if(isset($p_options[77019])){
			$v_inclusion = $this->UtilPathInclusion($p_options[77019], $p_entry['filename']);
			if($v_inclusion == 0){
				$this->privErrorLog(-21, "Filename '".$p_entry['filename']."' is outside 77019");
				return $this->errno;
			}
		}
		if(isset($p_options[78001])){
			$v_local_header = [];
			$this->privConvertHeader2FileInfo($p_entry, $v_local_header);
			$v_result = $p_options[78001](78001, $v_local_header);
			if($v_result == 0){
				$p_entry['status'] = 'skipped';
				$v_result = 1;
			}
			if($v_result == 2){
				$p_entry['status'] = 'aborted';
				$v_result = 2;
			}
			$p_entry['filename'] = $v_local_header['filename'];
		}
		if($p_entry['status'] == 'ok'){
			if(file_exists($p_entry['filename'])){
				if(is_dir($p_entry['filename'])){
					$p_entry['status'] = 'already_a_directory';
					if(isset($p_options[77017]) && $p_options[77017] === true){
						$this->privErrorLog(-17, "Filename '".$p_entry['filename']."' is already used by an existing directory");
						return $this->errno;
					}
				}elseif(!is_writable($p_entry['filename'])){
					$p_entry['status'] = 'write_protected';
					if(isset($p_options[77017]) && $p_options[77017] === true){
						$this->privErrorLog(-1, "Filename '".$p_entry['filename']."' exists and is write protected");
						return $this->errno;
					}
				}elseif(filemtime($p_entry['filename']) > $p_entry['mtime']){
					if(!isset($p_options[77016]) || $p_options[77016] !== true){
						$p_entry['status'] = 'newer_exist';
						if(isset($p_options[77017]) && $p_options[77017] === true){
							$this->privErrorLog(-1, "Newer version of '".$p_entry['filename']."' exists and option 77016 is not selected");
							return $this->errno;
						}
					}
				}
			}else{
				if(($p_entry['external'] & 0x10) == 0x10 || substr($p_entry['filename'], -1) == '/'){
					$v_dir_to_check = $p_entry['filename'];
				}elseif(!strstr($p_entry['filename'], '/')){
					$v_dir_to_check = '';
				}else{
					$v_dir_to_check = dirname($p_entry['filename']);
				}
				if(($v_result = $this->privDirCheck($v_dir_to_check, ($p_entry['external'] & 0x10) == 0x10)) != 1){
					$p_entry['status'] = 'path_creation_fail';
					$v_result = 1;
				}
			}
		}
		if($p_entry['status'] == 'ok'){
			if(!(($p_entry['external'] & 0x10) == 0x10)){
				if($p_entry['compression'] == 0){
					if(($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0){
						$p_entry['status'] = 'write_error';
						return $v_result;
					}
					$v_size = $p_entry['compressed_size'];
					while($v_size != 0){
						$v_read_size = $v_size < 2048 ? $v_size : 2048;
						$v_buffer = @fread($this->handle, $v_read_size);
						@fwrite($v_dest_file, $v_buffer, $v_read_size);
						$v_size -= $v_read_size;
					}
					fclose($v_dest_file);
					touch($p_entry['filename'], $p_entry['mtime']);
				}else{
					if(($p_entry['flag'] & 1) == 1){
						$this->privErrorLog(-19, "File '".$p_entry['filename']."' is encrypted. Encrypted files are not supported.");
						return $this->errno;
					}
					if(!isset($p_options[77022]) && (isset($p_options[77021]) || isset($p_options[77020]) && $p_options[77020] <= $p_entry['size'])){
						$v_result = $this->privExtractFileUsingTempFile($p_entry, $p_options);
						if($v_result < 0){
							return $v_result;
						}
					}else{
						$v_buffer = @fread($this->handle, $p_entry['compressed_size']);
						$v_file_content = @gzinflate($v_buffer);
						unset($v_buffer);
						if($v_file_content === false){
							$p_entry['status'] = 'error';
							return $v_result;
						}
						if(($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0){
							$p_entry['status'] = 'write_error';
							return $v_result;
						}
						@fwrite($v_dest_file, $v_file_content, $p_entry['size']);
						unset($v_file_content);
						@fclose($v_dest_file);
					}
					@touch($p_entry['filename'], $p_entry['mtime']);
				}
				if(isset($p_options[77005])){
					@chmod($p_entry['filename'], $p_options[77005]);
				}
			}
		}
		if($p_entry['status'] == 'aborted'){
			$p_entry['status'] = 'skipped';
		}elseif(isset($p_options[78002])){
			$v_local_header = [];
			$this->privConvertHeader2FileInfo($p_entry, $v_local_header);
			$v_result = $p_options[78002](78002, $v_local_header);
			if($v_result == 2){
				$v_result = 2;
			}
		}
		return $v_result;
	}
	private function privExtractFileUsingTempFile(&$p_entry, &$p_options){
		$v_result = 1;
		$v_gzip_temp_name = uniqid('zip-', true).'.gz';
		if(($v_dest_file = @fopen($v_gzip_temp_name, 'wb')) == 0){
			fclose($v_file);
			$this->privErrorLog(-1, "Unable to open temporary file '".$v_gzip_temp_name."' in binary write mode");
			return $this->errno;
		}
		$v_binary_data = pack('va1a1Va1a1', 0x8b1f, chr($p_entry['compression']), chr(0x0), time(), chr(0x0), chr(3));
		@fwrite($v_dest_file, $v_binary_data, 10);
		$v_size = $p_entry['compressed_size'];
		while($v_size != 0){
			$v_read_size = $v_size < 2048 ? $v_size : 2048;
			$v_buffer = @fread($this->handle, $v_read_size);
			@fwrite($v_dest_file, $v_buffer, $v_read_size);
			$v_size -= $v_read_size;
		}
		$v_binary_data = pack('VV', $p_entry['crc'], $p_entry['size']);
		@fwrite($v_dest_file, $v_binary_data, 8);
		@fclose($v_dest_file);
		if(($v_dest_file = @fopen($p_entry['filename'], 'wb')) == 0){
			$p_entry['status'] = 'write_error';
			return $v_result;
		}
		if(($v_src_file = @gzopen($v_gzip_temp_name, 'rb')) == 0){
			@fclose($v_dest_file);
			$p_entry['status'] = 'read_error';
			$this->privErrorLog(-2, "Unable to open temporary file '".$v_gzip_temp_name."' in binary read mode");
			return $this->errno;
		}
		$v_size = $p_entry['size'];
		while($v_size != 0){
			$v_read_size = $v_size < 2048 ? $v_size : 2048;
			$v_buffer = @gzread($v_src_file, $v_read_size);
			@fwrite($v_dest_file, $v_buffer, $v_read_size);
			$v_size -= $v_read_size;
		}
		@fclose($v_dest_file);
		@gzclose($v_src_file);
		@unlink($v_gzip_temp_name);
		return $v_result;
	}
	private function privExtractFileInOutput(&$p_entry, &$p_options){
		$v_result = 1;
		if(($v_result = $this->privReadFileHeader($v_header)) != 1){
			return $v_result;
		}
		$this->privCheckFileHeaders($v_header, $p_entry);
		if(isset($p_options[78001])){
			$v_local_header = [];
			$this->privConvertHeader2FileInfo($p_entry, $v_local_header);
			$v_result = $p_options[78001](78001, $v_local_header);
			if($v_result == 0){
				$p_entry['status'] = 'skipped';
				$v_result = 1;
			}
			if($v_result == 2){
				$p_entry['status'] = 'aborted';
				$v_result = 2;
			}
			$p_entry['filename'] = $v_local_header['filename'];
		}
		if($p_entry['status'] == 'ok'){
			if(!(($p_entry['external'] & 0x10) == 0x10)){
				if($p_entry['compressed_size'] == $p_entry['size']){
					$v_buffer = @fread($this->handle, $p_entry['compressed_size']);
					echo $v_buffer;
					unset($v_buffer);
				}else{
					$v_buffer = @fread($this->handle, $p_entry['compressed_size']);
					$v_file_content = gzinflate($v_buffer);
					unset($v_buffer);
					echo $v_file_content;
					unset($v_file_content);
				}
			}
		}
		if($p_entry['status'] == 'aborted'){
			$p_entry['status'] = 'skipped';
		}elseif(isset($p_options[78002])){
			$v_local_header = [];
			$this->privConvertHeader2FileInfo($p_entry, $v_local_header);
			$v_result = $p_options[78002](78002, $v_local_header);
			if($v_result == 2){
				$v_result = 2;
			}
		}
		return $v_result;
	}
	private function privExtractFileAsString(&$p_entry, &$p_string, &$p_options){
		$v_result = 1;
		$v_header = [];
		if(($v_result = $this->privReadFileHeader($v_header)) != 1){
			return $v_result;
		}
		$this->privCheckFileHeaders($v_header, $p_entry);
		if(isset($p_options[78001])){
			$v_local_header = [];
			$this->privConvertHeader2FileInfo($p_entry, $v_local_header);
			$v_result = $p_options[78001](78001, $v_local_header);
			if($v_result == 0){
				$p_entry['status'] = 'skipped';
				$v_result = 1;
			}
			if($v_result == 2){
				$p_entry['status'] = 'aborted';
				$v_result = 2;
			}
			$p_entry['filename'] = $v_local_header['filename'];
		}
		if($p_entry['status'] == 'ok'){
			if(!(($p_entry['external'] & 0x10) == 0x10)){
				if($p_entry['compression'] == 0){
					$p_string = @fread($this->handle, $p_entry['compressed_size']);
				}else{
					$v_data = @fread($this->handle, $p_entry['compressed_size']);
					$p_string = @gzinflate($v_data);
				}
			}
		}
		if($p_entry['status'] == 'aborted'){
			$p_entry['status'] = 'skipped';
		}elseif(isset($p_options[78002])){
			$v_local_header = [];
			$this->privConvertHeader2FileInfo($p_entry, $v_local_header);
			$v_local_header['content'] = $p_string;
			$v_result = $p_options[78002](78002, $v_local_header);
			$p_string = $v_local_header['content'];
			unset($v_local_header['content']);
			if($v_result == 2){
				$v_result = 2;
			}
		}
		return $v_result;
	}
	private function privReadFileHeader(&$p_header){
		$v_result = 1;
		$v_binary_data = @fread($this->handle, 4);
		$v_data = unpack('Vid', $v_binary_data);
		if($v_data['id'] != 0x4034b50){
			$this->privErrorLog(-10, 'Invalid archive structure');
			return $this->errno;
		}
		$v_binary_data = fread($this->handle, 26);
		if(strlen($v_binary_data) != 26){
			$p_header['filename'] = '';
			$p_header['status'] = 'invalid_header';
			$this->privErrorLog(-10, 'Invalid block size : '.strlen($v_binary_data));
			return $this->errno;
		}
		$v_data = unpack('vversion/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len', $v_binary_data);
		$p_header['filename'] = fread($this->handle, $v_data['filename_len']);
		if($v_data['extra_len'] != 0){
			$p_header['extra'] = fread($this->handle, $v_data['extra_len']);
		}else{
			$p_header['extra'] = '';
		}
		$p_header['version_extracted'] = $v_data['version'];
		$p_header['compression'] = $v_data['compression'];
		$p_header['size'] = $v_data['size'];
		$p_header['compressed_size'] = $v_data['compressed_size'];
		$p_header['crc'] = $v_data['crc'];
		$p_header['flag'] = $v_data['flag'];
		$p_header['filename_len'] = $v_data['filename_len'];
		$p_header['mdate'] = $v_data['mdate'];
		$p_header['mtime'] = $v_data['mtime'];
		if($p_header['mdate'] && $p_header['mtime']){
			$v_hour = ($p_header['mtime'] & 0xf800) >> 11;
			$v_minute = ($p_header['mtime'] & 0x7e0) >> 5;
			$v_seconde = ($p_header['mtime'] & 0x1f) * 2;
			$v_year = (($p_header['mdate'] & 0xfe00) >> 9) + 1980;
			$v_month = ($p_header['mdate'] & 0x1e0) >> 5;
			$v_day = $p_header['mdate'] & 0x1f;
			$p_header['mtime'] = @mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);
		}else{
			$p_header['mtime'] = time();
		}
		$p_header['stored_filename'] = $p_header['filename'];
		$p_header['status'] = 'ok';
		return $v_result;
	}
	private function privReadCentralFileHeader(&$p_header){
		$v_result = 1;
		$v_binary_data = @fread($this->handle, 4);
		$v_data = unpack('Vid', $v_binary_data);
		if($v_data['id'] != 0x2014b50){
			$this->privErrorLog(-10, 'Invalid archive structure');
			return $this->errno;
		}
		$v_binary_data = fread($this->handle, 42);
		if(strlen($v_binary_data) != 42){
			$p_header['filename'] = '';
			$p_header['status'] = 'invalid_header';
			$this->privErrorLog(-10, 'Invalid block size : '.strlen($v_binary_data));
			return $this->errno;
		}
		$p_header = unpack('vversion/vversion_extracted/vflag/vcompression/vmtime/vmdate/Vcrc/Vcompressed_size/Vsize/vfilename_len/vextra_len/vcomment_len/vdisk/vinternal/Vexternal/Voffset', $v_binary_data);
		if($p_header['filename_len'] != 0){
			$p_header['filename'] = fread($this->handle, $p_header['filename_len']);
		}else{
			$p_header['filename'] = '';
		}
		if($p_header['extra_len'] != 0){
			$p_header['extra'] = fread($this->handle, $p_header['extra_len']);
		}else{
			$p_header['extra'] = '';
		}
		if($p_header['comment_len'] != 0){
			$p_header['comment'] = fread($this->handle, $p_header['comment_len']);
		}else{
			$p_header['comment'] = '';
		}
		$v_hour = ($p_header['mtime'] & 0xf800) >> 11;
		$v_minute = ($p_header['mtime'] & 0x7e0) >> 5;
		$v_seconde = ($p_header['mtime'] & 0x1f) * 2;
		$v_year = (($p_header['mdate'] & 0xfe00) >> 9) + 1980;
		$v_month = ($p_header['mdate'] & 0x1e0) >> 5;
		$v_day = $p_header['mdate'] & 0x1f;
		$p_header['mtime'] = @mktime($v_hour, $v_minute, $v_seconde, $v_month, $v_day, $v_year);
		$p_header['stored_filename'] = $p_header['filename'];
		$p_header['status'] = 'ok';
		if(substr($p_header['filename'], -1) == '/'){
			$p_header['external'] = 0x10;
		}
		return $v_result;
	}
	private function privCheckFileHeaders(&$p_local_header, &$p_central_header){
		$v_result = 1;
		if(($p_local_header['flag'] & 8) == 8){
			$p_local_header['size'] = $p_central_header['size'];
			$p_local_header['compressed_size'] = $p_central_header['compressed_size'];
			$p_local_header['crc'] = $p_central_header['crc'];
		}
		return $v_result;
	}
	private function privReadEndCentralDir(&$p_central_dir){
		$v_result = 1;
		$v_size = filesize($this->file);
		@fseek($this->handle, $v_size);
		if(@ftell($this->handle) != $v_size){
			$this->privErrorLog(-10, "Unable to go to the end of the archive '".$this->file."'");
			return $this->errno;
		}
		$v_found = 0;
		if($v_size > 26){
			@fseek($this->handle, $v_size - 22);
			if(($v_pos = @ftell($this->handle)) != $v_size - 22){
				$this->privErrorLog(-10, "Unable to seek back to the middle of the archive '".$this->file."'");
				return $this->errno;
			}
			$v_binary_data = @fread($this->handle, 4);
			$v_data = @unpack('Vid', $v_binary_data);
			if($v_data['id'] == 0x6054b50){
				$v_found = 1;
			}
			$v_pos = ftell($this->handle);
		}
		if(!$v_found){
			$v_maximum_size = 65557;
			if($v_maximum_size > $v_size){
				$v_maximum_size = $v_size;
			}
			@fseek($this->handle, $v_size - $v_maximum_size);
			if(@ftell($this->handle) != $v_size - $v_maximum_size){
				$this->privErrorLog(-10, "Unable to seek back to the middle of the archive '".$this->file."'");
				return $this->errno;
			}
			$v_pos = ftell($this->handle);
			$v_bytes = 0x0;
			while($v_pos < $v_size){
				$v_byte = @fread($this->handle, 1);
				$v_bytes = ($v_bytes & 0xffffff) << 8 | Ord($v_byte);
				if($v_bytes == 0x504b0506){
					$v_pos++;
					break;
				}
				$v_pos++;
			}
			if($v_pos == $v_size){
				$this->privErrorLog(-10, 'Unable to find End of Central Dir Record signature');
				return $this->errno;
			}
		}
		$v_binary_data = fread($this->handle, 18);
		if(strlen($v_binary_data) != 18){
			$this->privErrorLog(-10, 'Invalid End of Central Dir Record size : '.strlen($v_binary_data));
			return $this->errno;
		}
		$v_data = unpack('vdisk/vdisk_start/vdisk_entries/ventries/Vsize/Voffset/vcomment_size', $v_binary_data);
		if($v_data['comment_size'] != 0){
			$p_central_dir['comment'] = fread($this->handle, $v_data['comment_size']);
		}else{
			$p_central_dir['comment'] = '';
		}
		$p_central_dir['entries'] = $v_data['entries'];
		$p_central_dir['disk_entries'] = $v_data['disk_entries'];
		$p_central_dir['offset'] = $v_data['offset'];
		$p_central_dir['size'] = $v_data['size'];
		$p_central_dir['disk'] = $v_data['disk'];
		$p_central_dir['disk_start'] = $v_data['disk_start'];
		return $v_result;
	}
	private function privDirCheck($p_dir, $p_is_dir=false){
		$v_result = 1;
		if($p_is_dir && substr($p_dir, -1) == '/'){
			$p_dir = substr($p_dir, 0, strlen($p_dir) - 1);
		}
		if(is_dir($p_dir) || $p_dir == ''){
			return 1;
		}
		$p_parent_dir = dirname($p_dir);
		if($p_parent_dir != $p_dir){
			if($p_parent_dir != ''){
				if(($v_result = $this->privDirCheck($p_parent_dir)) != 1){
					return $v_result;
				}
			}
		}
		if(!@mkdir($p_dir, 0777)){
			$this->privErrorLog(-8, "Unable to create directory '".$p_dir."'");
			return $this->errno;
		}
		return $v_result;
	}
	private function privErrorLog($p_errno, $p_errstr){
		$this->errno = $p_errno;
		$this->errstr = $p_errstr;
	}
	private function UtilPathReduction($p_dir){
		$v_result = '';
		if($p_dir != ''){
			$v_list = explode('/', $p_dir);
			$v_skip = 0;
			for($i = sizeof($v_list) - 1; $i >= 0; $i--){
				if($v_list[$i] !== '.'){
					if($v_list[$i] == '..'){
						$v_skip++;
					}elseif($v_list[$i] == ''){
						if($i == 0){
							$v_result = '/'.$v_result;
							if($v_skip > 0){
								$v_result = $p_dir;
								$v_skip = 0;
							}
						}elseif($i == sizeof($v_list) - 1){
							$v_result = $v_list[$i];
						}
					}elseif($v_skip > 0){
						$v_skip--;
					}else{
						$v_result = $v_list[$i].($i != sizeof($v_list) - 1 ? '/'.$v_result : '');
					}
				}
			}
			if($v_skip > 0){
				while($v_skip > 0){
					$v_result = '../'.$v_result;
					$v_skip--;
				}
			}
		}
		return $v_result;
	}
	private function UtilPathInclusion($p_dir, $p_path){
		$v_result = 1;
		if($p_dir == '.' || strlen($p_dir) >= 2 && substr($p_dir, 0, 2) == './'){
			$p_dir = $this->UtilTranslateWinPath(getcwd(), false).'/'.substr($p_dir, 1);
		}
		if($p_path == '.' || strlen($p_path) >= 2 && substr($p_path, 0, 2) == './'){
			$p_path = $this->UtilTranslateWinPath(getcwd(), false).'/'.substr($p_path, 1);
		}
		$v_list_dir = explode('/', $p_dir);
		$v_list_dir_size = sizeof($v_list_dir);
		$v_list_path = explode('/', $p_path);
		$v_list_path_size = sizeof($v_list_path);
		$i = 0;
		$j = 0;
		while($i < $v_list_dir_size && $j < $v_list_path_size && $v_result){
			if($v_list_dir[$i] == ''){
				$i++;
				continue;
			}
			if($v_list_path[$j] == ''){
				$j++;
				continue;
			}
			if($v_list_dir[$i] != $v_list_path[$j] && $v_list_dir[$i] != '' && $v_list_path[$j] != ''){
				$v_result = 0;
			}
			$i++;
			$j++;
		}
		if($v_result){
			while($j < $v_list_path_size && $v_list_path[$j] == ''){
				$j++;
			}
			while($i < $v_list_dir_size && $v_list_dir[$i] == ''){
				$i++;
			}
			if($i >= $v_list_dir_size && $j >= $v_list_path_size){
				$v_result = 2;
			}elseif($i < $v_list_dir_size){
				$v_result = 0;
			}
		}
		return $v_result;
	}
	private function UtilTranslateWinPath($p_path, $p_remove_disk_letter=true){
		if(stristr(php_uname(), 'windows')){
			if($p_remove_disk_letter && ($v_position = strpos($p_path, ':')) != false){
				$p_path = substr($p_path, $v_position + 1);
			}
			if(strpos($p_path, '\\') > 0 || substr($p_path, 0, 1) == '\\'){
				$p_path = strtr($p_path, '\\', '/');
			}
		}
		return $p_path;
	}
}
?>