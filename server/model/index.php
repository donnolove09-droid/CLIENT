<?php
namespace server\model;
use server\library\lib;
use server\library\db;
use \ReflectionMethod;
class index{
	const ROOT = BASE_ROOT;
	const PATH = BASE_PATH;
	const CHARSET = BASE_CHARSET;
	private static function stat(){
		if(empty($_SERVER['QUERY_STRING']) && empty($_SERVER['HTTP_X_REQUESTED_WITH'])){
			$ip = lib::ip();
			$date = date('Y-m-d H:i:s');
			if($id = db::fetch('stat', 'id', ['date' => ['date', '=0', 'and'], 'ip' => $ip])){
				db::update('stat', ['pv' => ['+', 1], 'date' => $date], ['id' => $id]);
			}else{
				db::insert('stat', ['ip' => $ip, 'pv' => 1, 'date' => $date]);
			}
		}
	}
	public static function router($slice){
		$controller = $GLOBALS['param']['basic']['power'] && isset($slice[1]) && ctype_alpha($slice[1]) ? $slice[1] : 'index';
		$function = $GLOBALS['param']['basic']['power'] ? isset($slice[2]) ? $slice[2] : 'init' : 'notice';
		$loader = self::ROOT.'server/controller/index/'.$controller.'.php';
		if(!is_file(self::ROOT.'upload/install.lock')){
			header('location:'.self::PATH.'index.php/install');
		}elseif(is_file($loader)){
			require $loader;
			$object = 'server\controller\index\\'.ucfirst($controller);
			$let = function() use($object, $function){
				$method = new ReflectionMethod($object, $function);
				return $method->isPublic() && $method->class != __CLASS__;
			};
			method_exists($object, $function) and $let() and $object::$function(self::stat());
		}
	}
	protected static function login(){
		$online = intval($GLOBALS['param']['shield']['shield_online']);
		$salt = isset($_COOKIE['member']) ? lib::filter($_COOKIE['member']) : '';
		$id = db::fetch('session', 'val', ['key' => ['=', 'member', 'and'], 'time' => ['>', time() - $online * 86400, 'and'], 'salt' => $salt]);
		return db::fetch('member', '[]', ['id' => ['=', $id, 'and'], 'lock' => 0]);
	}
	protected static function avatar($uid){
		$file = 'upload/avatar/'.$uid.'.png';
		$default = self::PATH.'client/view/index/css/avatar.png';
		return is_file(self::ROOT.$file) ? self::PATH.$file.'?'.time() : $default;
	}
	protected static function room($mid){
		return db::fetch('room', '[]', ['aid' => ['=', $mid, 'or'], 'bid' => ['=', $mid, 'or'], 'cid' => $mid]);
	}
	private static function say($rid, $pid, $mid, $time){
		$where = ['mid' => ['=', $pid, 'and'], 'tid' => ['=', $mid, 'and'], 'time' => ['>', $time - 30, 'and'], 'rid' => $rid];
		$result = db::fetch('talk', '[]', $where, ['time', 'asc'], [0, 1]);
		$result and db::update('talk', ['tid' => 0], ['id' => $result['id']]);
		return $result ? $result['type'] : 0;
	}
	protected static function person($room, $seat, $mid, $time){
		$pid = $room[$seat.'id'];
		$avatar = $pid ? self::avatar($pid) : '';
		$nick = $pid ? lib::convert(db::fetch('member', 'nick', ['id' => $pid]), true) : '';
		$offline = $room[$seat.'time'] > $time - 15 ? 0 : 1;
		$number = $room['state'] > 1 ? count(explode('[', $room[$seat.'card'])) - 1 : 17;
		$card = $pid != $mid && $room['state'] ? '' : $room[$seat.'card'];
		$talk = $pid != $mid ? self::say($room['id'], $pid, $mid, $time) : 0;
		$where = ['mid' => $pid, 'avatar' => $avatar, 'nick' => $nick, 'offline' => $offline, 'number' => $number, 'card' => $card, 'talk' => $talk];
		return array_merge($where, ['fight' => $room[$seat.'fight'], 'last' => $room[$seat.'last'], 'state' => $room[$seat.'state']]);
	}
	protected static function owner($room, $mid, $time){
		$seat = $room['aid'] != $room['owner'] ? $room['bid'] != $room['owner'] ? ['c', 'a', 'b'] : ['b', 'c', 'a'] : ['a', 'b', 'c'];
		if(!$room['state'] && $room['owner'] != $mid && $room[$seat[0].'time'] <= $time - 15){
			$owner = $room[$seat[1].'id'] ? $seat[1] : $seat[2];
			db::update('room', ['owner' => $room[$owner.'id'], $owner.'fight' => ''], ['id' => $room['id']]);
		}
	}
	protected static function card(){
		$push = $apush = $bpush = $cpush = [];
		$random = range(1, 54);
		$number = range(0, 53);
		shuffle($random) and shuffle($number);
		$aslice = array_slice($number, 0, 17);
		$bslice = array_slice($number, 17, 17);
		$cslice = array_slice($number, 34, 17);
		$slice = array_slice($number, 51);
		for($i = 0; $i < 17; $i++){
			$apush[] = $random[$aslice[$i]];
			$bpush[] = $random[$bslice[$i]];
			$cpush[] = $random[$cslice[$i]];
		}
		for($i = 0; $i < 3; $i++){
			$push[] = $random[$slice[$i]];
		}
		rsort($push) and rsort($apush) and rsort($bpush) and rsort($cpush);
		return ['['.implode('][', $push).']', '['.implode('][', $apush).']', '['.implode('][', $bpush).']', '['.implode('][', $cpush).']'];
	}
	protected static function flip($card){
		$push = [];
		$array = explode('[', $card);
		for($i = 1; $i < count($array); $i++){
			$push[] = str_replace(']', '', $array[$i]);
		}
		rsort($push);
		return '['.implode('][', $push).']';
	}
	protected static function cost($room, $date){
		$room['mode'] and db::update('member', ['gem' => ['-', $room['cost'] * 3]], ['id' => $room['owner']]);
		$room['mode'] and db::insert('bill', ['mid' => $room['owner'], 'rid' => $room['id'], 'way' => 0, 'type' => 1, 'point' => $room['cost'] * 3, 'date' => $date]);
		$room['mode'] or db::update('member', ['gem' => ['-', $room['cost']]], ['id' => $room['aid']]);
		$room['mode'] or db::update('member', ['gem' => ['-', $room['cost']]], ['id' => $room['bid']]);
		$room['mode'] or db::update('member', ['gem' => ['-', $room['cost']]], ['id' => $room['cid']]);
		$room['mode'] or db::insert('bill', ['mid' => $room['aid'], 'rid' => $room['id'], 'way' => 0, 'type' => 1, 'point' => $room['cost'], 'date' => $date]);
		$room['mode'] or db::insert('bill', ['mid' => $room['bid'], 'rid' => $room['id'], 'way' => 0, 'type' => 1, 'point' => $room['cost'], 'date' => $date]);
		$room['mode'] or db::insert('bill', ['mid' => $room['cid'], 'rid' => $room['id'], 'way' => 0, 'type' => 1, 'point' => $room['cost'], 'date' => $date]);
	}
	private static function mold($card, $keep){
		if(is_int($keep)){
			$push = [];
			for($i = 0; $i < count($card); $i++){
				$push[] = self::mold($card[$i], $keep ? true : false);
			}
			return $push;
		}elseif(is_array($keep)){
			return $keep[0] ? $keep[1] : $card;
		}elseif($card < 5 || $card < 9 || $card < 13){
			return $card < 9 ? $card < 5 ? 3 : 4 : 5;
		}elseif($card < 17 || $card < 21 || $card < 25){
			return $card < 21 ? $card < 17 ? 6 : 7 : 8;
		}elseif($card < 29 || $card < 33 || $card < 37){
			return $card < 33 ? $card < 29 ? 9 : self::mold('t', [$keep, 10]) : self::mold('j', [$keep, 11]);
		}elseif($card < 41 || $card < 45 || $card < 49){
			return $card < 45 ? $card < 41 ? self::mold('q', [$keep, 12]) : self::mold('k', [$keep, 13]) : self::mold('a', [$keep, 14]);
		}
		return $card < 54 ? $card < 53 ? self::mold(2, [$keep, 15]) : self::mold('x', [$keep, 16]) : self::mold('y', [$keep, 17]);
	}
	protected static function base($room, $seat, $now, $next, $prev, $date){
		if($now){
			db::update('member', ['pea' => ['+', $now]], ['id' => $room['play']]);
			db::insert('bill', ['mid' => $room['play'], 'rid' => $room['id'], 'way' => 2, 'type' => 2, 'point' => $now, 'date' => $date]);
		}
		if($next[1]){
			db::update('member', ['pea' => [$next[0] < 2 ? '-' : '+', $next[1]]], ['id' => $room[$seat[1].'id']]);
			db::insert('bill', ['mid' => $room[$seat[1].'id'], 'rid' => $room['id'], 'way' => $next[0], 'type' => 2, 'point' => $next[1], 'date' => $date]);
		}
		if($prev[1]){
			db::update('member', ['pea' => [$prev[0] < 2 ? '-' : '+', $prev[1]]], ['id' => $room[$seat[2].'id']]);
			db::insert('bill', ['mid' => $room[$seat[2].'id'], 'rid' => $room['id'], 'way' => $prev[0], 'type' => 2, 'point' => $prev[1], 'date' => $date]);
		}
	}
	protected static function tool($count, $card, $fight){
		preg_match_all('/\d+/', $fight, $matches);
		$mold = self::mold($matches[0], 0);
		for($i = 0; $i < count($mold); $i++){
			$total = preg_match('/'.$mold[$i].':(\d)/', $count, $m) ? $m[1] - 1 : 0;
			$count = preg_replace('/'.$mold[$i].':\d/', $mold[$i].':'.$total, $count);
			$card = str_replace('['.$matches[0][$i].']', '', $card);
		}
		return [$count, $card];
	}
	protected static function robot($room, $seat){
		if(preg_match('/\[/', $room[$seat[1].'fight']) || preg_match('/\[/', $room[$seat[2].'fight'])){
			return 'past';
		}
		$list = explode('[', $room[$seat[0].'card']);
		$fight = '['.$list[count($list) - 1];
		return [$fight, 0];
	}
	protected static function fight($room, $seat, $type){
		$push = [];
		for($i = 0; $i < count($type); $i++){
			$push[] = $item = intval($type[$i]);
			if(!preg_match('/\['.$item.'\]/', $room[$seat[0].'card'])){
				return false;
			}
		}
		rsort($push);
		$push = array_values(array_unique($push));
		$fight = '['.implode('][', $push).']';
		$mold = self::mold($push, 1);
		return [$fight, count($mold) == 2 && $mold[1] == 16 || count($mold) == 4 && $mold[0] == $mold[3] ? 1 : 0];
	}
	protected static function check($now, $next, $prev){
		$card = function($self, $right, $left){
			preg_match_all('/\d+/', $self, $after);
			if(preg_match('/\[/', $left) || preg_match('/\[/', $right)){
				preg_match_all('/\d+/', preg_match('/\[/', $left) ? $left : $right, $before);
				sort($before[0]);
			}
			return [$after[0], isset($before[0]) ? $before[0] : []];
		};
		list($plan, $fight) = $card($now, $next, $prev);
		$me = self::mold($plan, 1);
		$he = self::mold($fight, 1);
		$own = implode('', self::mold($plan, 0));
		$foe = implode('', self::mold($fight, 0));
		$mix = function($m){
			if(strlen($m) == 2){
				return $m == 'yx' ? [0, 0] : [1, 'mix'];
			}elseif(strlen($m) == 4){
				return substr($m, 0, 1) == substr($m, 1, 1) && substr($m, 2, 1) == substr($m, -1) && substr($m, 0, 1) != substr($m, -1) ? [1, 'mix'] : [0, 0];
			}
			return [0, 0];
		};
		$filter = function($f, $c=[]) use($me){
			if($f){
				array_push($c, array_count_values($f));
				return array_values(array_filter($f, function($t) use($c){
					return $c[0][$t] > 3;
				}))[0];
			}
			array_push($c, array_count_values($me));
			array_push($f, array_values(array_unique(array_filter($me, function($t) use($c){
				return $c[0][$t] > 2;
			}))));
			return [$f[0], array_values(array_filter($me, function($t) use($f){
				return !in_array($t, $f[0]);
			}))];
		};
		$fly = function($f){
			if(!$f[1]){
				for($i = 0; $i < count($f[0]) - 1; $i++){
					if($f[0][$i] != $f[0][$i + 1] + 1){
						if(count($f[0]) == 4){
							if(!$i && $f[0][1] == $f[0][3] + 2 || $i > 1 && $f[0][0] < 15){
								return [1, 'fly'];
							}
						}
						return [0, 0];
					}elseif($i == count($f[0]) - 2){
						if($f[0][0] < 15 || count($f[0]) == 4){
							return [1, 'fly'];
						}
					}
				}
			}elseif(count($f[0]) == 5 && count($f[1]) == 1){
				if($f[0][0] != $f[0][1] + 1 && $f[0][1] == $f[0][4] + 3 || $f[0][3] != $f[0][4] + 1 && $f[0][0] == $f[0][3] + 3 && $f[0][0] < 15 || $f[0][4] > 10){
					return [1, 'fly'];
				}
			}elseif(count($f[0]) == count($f[1]) || count($f[0]) * 2 == count($f[1])){
				if(count($f[0]) > 1){
					if($f[0][0] > 14 || $f[0][0] + 1 != $f[0][count($f[0]) - 1] + count($f[0])){
						return [0, 0];
					}
				}
				if(count($f[0]) == count($f[1])){
					if(count($f[1]) > 1 && $f[1][1] > 15){
						return [0, 0];
					}
				}elseif(strlen(preg_replace('/(\d+)\1{1}/', '', implode('', $f[1])))){
					return [0, 0];
				}
				return [1, 'fly'];
			}
			return [0, 0];
		};
		$pair = function() use($me){
			if(count($me) > 5 && count($me) % 2 < 1){
				for($i = 1; $i < count($me) / 2; $i++){
					if($me[$i * 2 - 1] > 14 || $me[$i * 2 - 1] != $me[$i * 2 - 2] || $me[$i * 2 - 1] != $me[$i * 2] + 1 || $me[$i * 2] != $me[$i * 2 + 1]){
						return [0, 0];
					}
				}
				return [1, 'pair'];
			}
			return [0, 0];
		};
		$flow = function() use($me){
			if(count($me) > 4){
				for($i = 0; $i < count($me) - 1; $i++){
					if($me[$i] > 14 || $me[$i] != $me[$i + 1] + 1){
						return [0, 0];
					}
				}
				return [1, 'flow'];
			}
			return [0, 0];
		};
		$alone = function() use($own, $mix, $filter, $fly, $pair, $flow){
			if(strlen($own) < 2){
				return [1, 'single'];
			}elseif(strlen($own) < 3){
				return substr($own, 0, 1) != substr($own, -1) ? $own == 'yx' ? [1, 'joker'] : [0, 0] : [1, 'double'];
			}elseif(strlen($own) < 4){
				return substr($own, 0, 1) == substr($own, -1) ? [1, 'same'] : [0, 0];
			}elseif(preg_match('/(\d|t|j|q|k|a)\1{3}/', $own)){
				return strlen($own) < 5 ? [1, 'bomb'] : $mix(preg_replace('/(\d|t|j|q|k|a)\1{3}/', '', $own, 1));
			}elseif(preg_match('/(\d|t|j|q|k|a)\1{2}/', $own)){
				return $fly($filter([]));
			}elseif(preg_match('/(\d|t|j|q|k|a)\1{1}/', $own)){
				return $pair();
			}
			return $flow();
		};
		$triple = function($t, $e){
			if(!strlen($t)){
				for($i = 0; $i < count($e) - 3; $i++){
					if(($i + 1) % 3 < 1){
						if($e[$i] != $e[$i + 3] - 1){
							return [$e[3], 1];
						}elseif($i == count($e) - 4){
							return [$e[0], $e[count($e) - 1] < 15 ? 0 : 1];
						}
					}
				}
			}elseif(count($e) == 16 && strlen($t) == 1){
				return [$e[4], 1];
			}
			for($i = 0; $i < count($e) - 2; $i++){
				if($e[$i] == $e[$i + 2]){
					return [$e[$i], strlen($t) == (count($e) - strlen($t)) / 3 ? 1 : 2];
				}
			}
		};
		$battle = function($b) use($me, $he, $own, $foe, $filter, $triple){
			sort($me);
			if($foe == 'xy'){
				return false;
			}elseif(strlen($foe) == 4 && substr($foe, 0, 1) == substr($foe, -1)){
				if($b[1] == 'joker' || $b[1] == 'bomb' && $me[0] > $he[0]){
					return true;
				}
			}elseif($b[1] == 'joker' || $b[1] == 'bomb'){
				return true;
			}elseif(strlen($foe) < 4){
				if(count($me) == count($he) && $me[0] > $he[0]){
					return true;
				}
			}elseif(preg_match('/(\d|t|j|q|k|a)\1{3}/', $foe)){
				if($b[1] == 'mix' && count($me) == count($he) && $filter($me) > $filter($he)){
					return true;
				}
			}elseif(preg_match('/(\d|t|j|q|k|a)\1{2}/', $foe)){
				array_push($b, $triple(preg_replace('/(\d|t|j|q|k|a)\1{2}/', '', $own), $me), $triple(preg_replace('/(\d|t|j|q|k|a)\1{2}/', '', $foe), $he));
				if($b[1] == 'fly' && count($me) == count($he) && $b[2][0] > $b[3][0] && $b[2][1] == $b[3][1]){
					return true;
				}
			}elseif(preg_match('/(\d|t|j|q|k|a)\1{1}/', $foe)){
				if($b[1] == 'pair' && count($me) == count($he) && $me[0] > $he[0]){
					return true;
				}
			}elseif($b[1] == 'flow' && count($me) == count($he) && $me[0] > $he[0]){
				return true;
			}
			return false;
		};
		$alone = $alone();
		return $alone[0] ? $he ? $battle($alone) : true : false;
	}
}
?>