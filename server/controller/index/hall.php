<?php
namespace server\controller\index;
use server\library\tpl;
use server\library\lib;
use server\library\db;
use server\model\index as model;
class Hall extends model{
	public static function init(){
		$login = parent::login();
		$login or exit(header('location:'.parent::PATH));
		parent::room($login['id']) and exit(header('location:'.parent::PATH.'index.php/room'));
		$tpl = new tpl('index');
		$tpl->set('login', $login);
		$tpl->set('avatar', parent::avatar($login['id']));
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('hall');
	}
	public static function upload(){
		$login = parent::login();
		$login or exit('{"state":-1}');
		lib::request(['token', $_COOKIE['member']]) or exit('{"state":"Access Denied"}');
		empty($_FILES) and exit('{"state":"Access Denied"}');
		in_array(strtolower(pathinfo($_FILES['avatar']['name'])['extension']), ['png', 'jpg', 'jpeg', 'gif']) or exit('{"state":"Access Denied"}');
		$_FILES['avatar']['size'] > 1048576 and exit('{"state":"Access Denied"}');
		move_uploaded_file($_FILES['avatar']['tmp_name'], parent::ROOT.'upload/avatar/'.$login['id'].'.png');
		echo '{"state":'.$login['id'].',"msg":"'.parent::PATH.'"}';
	}
	public static function logout(){
		parent::login() or exit('1');
		lib::request(['token', $_COOKIE['member']]) or exit('Access Denied');
		db::delete('session', ['key' => ['=', 'member', 'and'], 'salt' => lib::filter($_COOKIE['member'])]);
		setcookie('member', '', time() - 1, parent::PATH);
		echo '1';
	}
	public static function info(){
		$login = parent::login();
		$login or exit('-1');
		lib::request(['token', $_COOKIE['member']]) or exit('Access Denied');
		$nick = lib::method('member_nick');
		lib::length($nick, [1, 24]) or exit('Access Denied');
		db::update('member', ['nick' => $nick], ['id' => $login['id']]);
		echo '1';
	}
	public static function pw(){
		$login = parent::login();
		$login or exit('-1');
		lib::request(['token', $_COOKIE['member']]) or exit('Access Denied');
		$mpw = lib::method('member_mpw');
		$rpw = lib::method('member_rpw');
		!preg_match('/[^\x21-\x7e]/', $mpw) and lib::length($mpw, [6, 24]) or exit('Access Denied');
		!preg_match('/[^\x21-\x7e]/', $rpw) and lib::length($rpw, [6, 24]) or exit('Access Denied');
		$mpw == $rpw and exit('Access Denied');
		md5($mpw) == $login['pw'] or exit('-2');
		db::update('member', ['pw' => md5($rpw)], ['id' => $login['id']]);
		echo '1';
	}
	public static function buy(){
		$login = parent::login();
		$login or exit('{"state":-1}');
		lib::request(['token', $_COOKIE['member']]) or exit('{"state":"Access Denied"}');
		$type = intval(lib::method('buy_type'));
		$secret = lib::method('buy_secret');
		preg_match('/^[a-z0-9]{32}$/', $secret) or exit('{"state":"Access Denied"}');
		$buy = db::fetch('buy', '[]', ['type' => ['=', $type < 2 ? 1 : 2, 'and'], 'secret' => ['=', $secret, 'and'], 'mid' => 0]);
		$buy or exit('{"state":-2}');
		db::update('member', [$type < 2 ? 'gem' : 'pea' => ['+', $buy['point']]], ['id' => $login['id']]);
		db::update('buy', ['mid' => $login['id'], 'date' => date('Y-m-d H:i:s')], ['id' => $buy['id']]);
		$amount = $login[$type < 2 ? 'gem' : 'pea'];
		$yet = $amount + $buy['point'];
		echo '{"state":1,"msg":'.$yet.'}';
	}
	public static function order(){
		$login = parent::login();
		$login or exit('{"state":-1}');
		lib::request(['token', $_COOKIE['member']]) or exit('{"state":"Access Denied"}');
		$type = lib::method('type');
		$start = intval(lib::method('start'));
		$total = 0;
		$msg = '';
		if($type == 'buy'){
			$result = db::fetch('buy', '{}', ['mid' => $login['id']], ['date', 'desc'], [$start, 10]);
			foreach($result as $object){
				$total += 1;
				$buy_type = $object->type < 2 ? '\u84dd\u94bb' : '\u91d1\u8c46';
				$msg .= '<tr><td>'.$buy_type.'</td><td>+'.$object->point.'</td><td>'.$object->secret.'</td><td>'.$object->date.'</td></tr>';
			}
		}else{
			$result = db::fetch('bill', '{}', ['mid' => $login['id']], ['date', 'desc'], [$start, 10]);
			foreach($result as $object){
				$total += 1;
				$final = $object->way < 2 ? '\u8f93\u5c40' : '\u8d62\u5c40';
				$bill_way = $object->way ? $object->way < 3 ? $final : '\u9886\u94bb' : '\u5f00\u5c40';
				$bill_type = $object->type < 2 ? '\u84dd\u94bb' : '\u91d1\u8c46';
				$bill_point = $object->way < 2 ? '-'.$object->point : '+'.$object->point;
				$msg .= '<tr><td>'.$bill_way.'</td><td>'.$bill_type.'</td><td>'.$bill_point.'</td><td>'.$object->rid.'</td><td>'.$object->date.'</td></tr>';
			}
		}
		echo '{"state":1,"total":'.$total.',"msg":"'.$msg.'"}';
	}
	public static function score(){
		$login = parent::login();
		$login or exit('{"state":-1}');
		lib::request(['token', $_COOKIE['member']]) or exit('{"state":"Access Denied"}');
		$mid = intval(lib::method('mid')) ?: $login['id'];
		$house = intval(lib::method('house'));
		$member = db::fetch('member', '[]', ['id' => $mid]);
		$member or exit('{"state":-2}');
		$win = db::fetch('bill', '()', ['way' => ['=', 2, 'and'], 'mid' => $mid]);
		$lose = db::fetch('bill', '()', ['way' => ['=', 1, 'and'], 'mid' => $mid]);
		echo '{"state":1,"win":'.$win.',"lose":'.$lose.',"point":'.$member[$house ? 'pea' : 'gem'].'}';
	}
	public static function gift(){
		$login = parent::login();
		$login or exit('{"state":-1}');
		lib::request(['token', $_COOKIE['member']]) or exit('{"state":"Access Denied"}');
		db::fetch('bill', 'id', ['date' => ['date', '=0', 'and'], 'way' => ['=', 3, 'and'], 'mid' => $login['id']]) and exit('{"state":-2}');
		$gem = intval($GLOBALS['param']['game']['game_gift']);
		$gem or exit('{"state":-3}');
		db::update('member', ['gem' => ['+', $gem]], ['id' => $login['id']]);
		db::insert('bill', ['mid' => $login['id'], 'rid' => 0, 'way' => 3, 'type' => 1, 'point' => $gem, 'date' => date('Y-m-d H:i:s')]);
		$yet = $login['gem'] + $gem;
		echo '{"state":1,"msg":'.$yet.'}';
	}
	public static function get(){
		$login = parent::login();
		$login or exit('-1');
		lib::request(['token', $_COOKIE['member']]) or exit('Access Denied');
		parent::room($login['id']) and exit('-2');
		$type = intval(lib::method('get_type'));
		$rid = intval(lib::method('get_rid'));
		preg_match('/^[1-9][0-9]{0,9}$/', $rid) or exit('Access Denied');
		$room = db::fetch('room', '[]', ['house' => [$type < 2 ? '=' : '>', 0, 'and'], 'state' => ['=', 0, 'and'], 'id' => $rid]);
		$room or exit('-3');
		$room['aid'] and $room['bid'] and $room['cid'] and exit('-3');
		$time = time();
		$point = $type < 2 ? $room['mode'] ? 0 : $room['cost'] : $room['base'];
		$login[$type < 2 ? 'gem' : 'pea'] < $point and exit('-4');
		$update = $room['aid'] ? $room['bid'] ? ['cid', 'ctime'] : ['bid', 'btime'] : ['aid', 'atime'];
		db::update('room', [$update[0] => $login['id'], $update[1] => $time, 'time' => $time], ['id' => $rid]);
		echo '1';
	}
	public static function put(){
		$login = parent::login();
		$login or exit('-1');
		lib::request(['token', $_COOKIE['member']]) or exit('Access Denied');
		parent::room($login['id']) and exit('-2');
		$type = intval(lib::method('put_type'));
		$mode = intval(lib::method('put_mode'));
		$house = intval(lib::method('put_house'));
		in_array($house, [1, 2, 3, 4]) or exit('Access Denied');
		$time = time();
		$array = [1 => 'small', 2 => 'medium', 3 => 'high', 4 => 'big'];
		$base = intval($GLOBALS['param']['game']['game_'.$array[$house].'_base']);
		$cost = $type < 2 ? intval($GLOBALS['param']['game']['game_cost']) : 0;
		$point = $type < 2 ? $mode ? $cost * 3 : $cost : $base;
		$login[$type < 2 ? 'gem' : 'pea'] < $point and exit('-3');
		$insert = $type < 2 ? [$mode ? 1 : 0, 0, 0] : [0, $house, $base];
		db::insert('room', ['cost' => $cost, 'mode' => $insert[0], 'house' => $insert[1], 'base' => $insert[2], 'aid' => $login['id'], 'atime' => $time, 'owner' => $login['id'], 'time' => $time]);
		echo '1';
	}
	public static function fast(){
		$login = parent::login();
		$login or exit('-1');
		lib::request(['token', $_COOKIE['member']]) or exit('Access Denied');
		$house = intval(lib::method('house'));
		$rid = intval(lib::method('rid'));
		in_array($house, [1, 2, 3, 4]) or exit('Access Denied');
		$room = parent::room($login['id']);
		$room and !$rid and exit('-4');
		$room and $rid != $room['id'] and exit('-4');
		$room and $room['state'] and exit('-5');
		$time = time();
		$where = ['id' => ['<>', $rid, 'and'], 'house' => ['=', $house, 'and'], 'state' => ['=', 0, 'and'], 'time' => ['>', $time - 15, 'and'], 'aid' => ['=', 0, 'or']];
		$where = array_merge($where, ['1:id' => ['<>', $rid, 'and'], '1:house' => ['=', $house, 'and'], '1:state' => ['=', 0, 'and'], '1:time' => ['>', $time - 15, 'and'], 'bid' => ['=', 0, 'or']]);
		$where = array_merge($where, ['2:id' => ['<>', $rid, 'and'], '2:house' => ['=', $house, 'and'], '2:state' => ['=', 0, 'and'], '2:time' => ['>', $time - 15, 'and'], 'cid' => 0]);
		$count = db::fetch('room', '()', $where);
		$count or exit('-2');
		$result = db::fetch('room', '[]', $where, ['id', 'asc'], [mt_rand(1, $count) - 1, 1]);
		$login['pea'] < $result['base'] and exit('-3');
		if($room){
			$seat = $room['aid'] != $login['id'] ? $room['bid'] != $login['id'] ? ['c', 'a', 'b'] : ['b', 'c', 'a'] : ['a', 'b', 'c'];
			if(!$room[$seat[1].'id'] && !$room[$seat[2].'id']){
				db::delete('room', ['id' => $room['id']]);
			}elseif($room['owner'] == $login['id']){
				$owner = $room[$seat[1].'id'] ? $seat[1] : $seat[2];
				db::update('room', ['owner' => $room[$owner.'id'], $owner.'fight' => '', $seat[0].'fight' => '', $seat[0].'state' => 0, $seat[0].'id' => 0], ['id' => $room['id']]);
			}else{
				db::update('room', [$seat[0].'fight' => '', $seat[0].'state' => 0, $seat[0].'id' => 0], ['id' => $room['id']]);
			}
		}
		$update = $result['aid'] ? $result['bid'] ? ['cid', 'ctime'] : ['bid', 'btime'] : ['aid', 'atime'];
		db::update('room', [$update[0] => $login['id'], $update[1] => $time, 'time' => $time], ['id' => $result['id']]);
		echo '1';
	}
}
?>