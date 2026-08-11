<?php
namespace server\controller\index;
use server\library\tpl;
use server\library\lib;
use server\library\db;
use server\model\index as model;
class Room extends model{
	public static function init(){
		$login = parent::login();
		$login or exit(header('location:'.parent::PATH));
		$room = parent::room($login['id']);
		$room or exit(header('location:'.parent::PATH.'index.php/hall'));
		$tpl = new tpl('index');
		$tpl->set('room', $room);
		$tpl->set('point', $room['house'] ? ['pea', $login['pea']] : ['gem', $login['gem']]);
		$tpl->set('const', ['path' => parent::PATH, 'charset' => parent::CHARSET]);
		$tpl->get('room');
	}
	public static function quit(){
		$login = parent::login();
		$login or exit('-1');
		lib::request(['token', $_COOKIE['member']]) or exit('Access Denied');
		if($room = parent::room($login['id'])){
			$room['state'] and exit('-2');
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
		echo '1';
	}
	public static function talk(){
		$login = parent::login();
		$login or exit('-1');
		lib::request(['token', $_COOKIE['member']]) or exit('Access Denied');
		$type = intval(lib::method('type'));
		in_array($type, [1, 2, 3, 4]) or exit('Access Denied');
		if($room = parent::room($login['id'])){
			$time = time();
			$seat = $room['aid'] != $login['id'] ? $room['bid'] != $login['id'] ? ['a', 'b'] : ['c', 'a'] : ['b', 'c'];
			$room[$seat[0].'id'] and db::insert('talk', ['mid' => $login['id'], 'tid' => $room[$seat[0].'id'], 'rid' => $room['id'], 'type' => $type, 'time' => $time]);
			$room[$seat[1].'id'] and db::insert('talk', ['mid' => $login['id'], 'tid' => $room[$seat[1].'id'], 'rid' => $room['id'], 'type' => $type, 'time' => $time]);
		}
		echo '1';
	}
	public static function kick(){
		$login = parent::login();
		$login or exit('-1');
		lib::request(['token', $_COOKIE['member']]) or exit('Access Denied');
		$mid = intval(lib::method('mid'));
		$room = parent::room($login['id']);
		$room or exit('-2');
		$room['owner'] == $login['id'] or exit('-3');
		$room['state'] and exit('-4');
		$mid == $login['id'] and exit('-5');
		$seat = $room['aid'] != $login['id'] ? $room['bid'] != $login['id'] ? ['a', 'b'] : ['c', 'a'] : ['b', 'c'];
		$kid = $mid != $room[$seat[0].'id'] ? $mid != $room[$seat[1].'id'] ? 0 : 2 : 1;
		$kid or exit('-6');
		db::update('room', [$seat[$kid - 1].'fight' => '', $seat[$kid - 1].'state' => 0, $seat[$kid - 1].'id' => 0], ['id' => $room['id']]);
		echo '1';
	}
	public static function ready(){
		$login = parent::login();
		$login or exit('-1');
		lib::request(['token', $_COOKIE['member']]) or exit('Access Denied');
		$room = parent::room($login['id']);
		$room or exit('-2');
		$cost = $room['mode'] ? $room['owner'] == $login['id'] ? $room['cost'] * 3 : 0 : $room['cost'];
		$point = $room['house'] ? $room['base'] : $cost;
		$login[$room['house'] ? 'pea' : 'gem'] < $point and exit('-3');
		$seat = $room['aid'] != $login['id'] ? $room['bid'] != $login['id'] ? ['c', 'a', 'b'] : ['b', 'c', 'a'] : ['a', 'b', 'c'];
		if(!$room['state'] && $room[$seat[0].'fight'] != 'ready'){
			if($room[$seat[1].'fight'] == 'ready' && $room[$seat[2].'fight'] == 'ready'){
				$card = parent::card();
				$play = preg_match('/\&/', $room[$seat[0].'last']) ? $room[$seat[0].'id'] : $room['owner'];
				$play = preg_match('/\&/', $room[$seat[1].'last']) ? $room[$seat[1].'id'] : $play;
				$play = preg_match('/\&/', $room[$seat[2].'last']) ? $room[$seat[2].'id'] : $play;
				$update = ['card' => $card[0], 'acard' => $card[1], 'bcard' => $card[2], 'ccard' => $card[3], 'afight' => '', 'bfight' => '', 'cfight' => ''];
				$update = array_merge($update, ['state' => 1, 'astate' => 1, 'bstate' => 1, 'cstate' => 1, 'mult' => 1, 'play' => $play, 'clock' => time()]);
				$room['cost'] and parent::cost($room, date('Y-m-d H:i:s'));
				db::update('room', $update, ['id' => $room['id']]);
			}else{
				db::update('room', [$seat[0].'fight' => 'ready'], ['id' => $room['id']]);
			}
		}
		echo '1';
	}
	public static function lord(){
		$login = parent::login();
		$login or exit('-1');
		lib::request(['token', $_COOKIE['member']]) or exit('Access Denied');
		$type = lib::method('type');
		$room = parent::room($login['id']);
		$room or exit('-2');
		if($room['state'] == 1){
			$time = time();
			if($room['play'] == $login['id'] && $type != 'robot' || $type == 'robot' && $room['clock'] <= $time - 30){
				$type = $type ? 'toss' : 'grab';
				$seat = $room['aid'] != $room['play'] ? $room['bid'] != $room['play'] ? ['c', 'a', 'b'] : ['b', 'c', 'a'] : ['a', 'b', 'c'];
				if($type == 'toss' && $room[$seat[1].'fight'] == 'toss' && $room[$seat[2].'fight'] == 'toss'){
					$card = parent::card();
					$update = ['card' => $card[0], 'acard' => $card[1], 'bcard' => $card[2], 'ccard' => $card[3], $seat[1].'fight' => '', $seat[2].'fight' => ''];
					$update = array_merge($update, ['astate' => 2, 'bstate' => 2, 'cstate' => 2, 'play' => $room[$seat[1].'id'], 'clock' => $time]);
				}elseif($room[$seat[1].'fight'] && $room[$seat[2].'fight']){
					$play = preg_match('/\&/', $room[$seat[0].'last']) ? $room['play'] : $room['owner'];
					$play = preg_match('/\&/', $room[$seat[1].'last']) ? $room[$seat[1].'id'] : $play;
					$play = preg_match('/\&/', $room[$seat[2].'last']) ? $room[$seat[2].'id'] : $play;
					if($room['play'] == $play){
						$shift = $type == 'toss' ? $room[$seat[2].'fight'] == 'grab' ? [$seat[2], 2] : [$seat[1], 2] : [$seat[0], 2];
					}elseif($room[$seat[1].'id'] == $play){
						if($type == 'toss'){
							$shift = $room[$seat[2].'fight'] == 'grab' ? $room[$seat[1].'fight'] == 'grab' ? [$seat[1], 1] : [$seat[2], 2] : [$seat[1], 2];
						}elseif($room[$seat[1].'fight'] == 'toss' && $room[$seat[2].'fight'] == 'toss'){
							$shift = [$seat[0], 2];
						}else{
							$shift = $room[$seat[1].'fight'] == 'toss' ? $room[$seat[2].'fight'] == 'grab' ? [$seat[2], 1] : [$seat[0], 2] : [$seat[1], 1];
						}
					}else{
						$shift = $type == 'toss' ? [$seat[1], 2] : [$seat[0], 2];
					}
					$update = $type == 'grab' ? ['mult' => ['*', 2]] : [];
					if($shift[1] < 2){
						$update = array_merge($update, [$seat[0].'fight' => $type, $shift[0].'fight' => '']);
					}else{
						$card = parent::flip($room['card'].$room[$shift[0].'card']);
						$count = '[y:1][x:1][2:4][a:4][k:4][q:4][j:4][t:4][9:4][8:4][7:4][6:4][5:4][4:4][3:4]';
						$update = array_merge($update, ['lord' => $room[$shift[0].'id'], $shift[0].'card' => $card, 'count' => $count]);
						$update = array_merge($update, ['state' => 2, 'astate' => 1, 'bstate' => 1, 'cstate' => 1, 'afight' => '', 'bfight' => '', 'cfight' => '']);
						$update = array_merge($update, [$seat[0].'last' => $type, $seat[1].'last' => $room[$seat[1].'fight'], $seat[2].'last' => $room[$seat[2].'fight']]);
					}
					$update = array_merge($update, ['play' => $room[$shift[0].'id'], 'clock' => $time]);
				}else{
					$update = $type == 'grab' ? ['mult' => ['*', 2]] : [];
					$update = array_merge($update, [$seat[0].'fight' => $type, 'play' => $room[$seat[1].'id'], 'clock' => $time]);
				}
				db::update('room', $update, ['id' => $room['id']]);
			}
		}
		echo '1';
	}
	public static function play(){
		$login = parent::login();
		$login or exit('-1');
		lib::request(['token', $_COOKIE['member']]) or exit('Access Denied');
		$type = lib::method('type');
		$room = parent::room($login['id']);
		$room or exit('-2');
		if($room['state'] == 2){
			$time = time();
			if($room['play'] == $login['id'] && $type != 'robot' || $type == 'robot' && $room['clock'] <= $time - 30){
				$seat = $room['aid'] != $room['play'] ? $room['bid'] != $room['play'] ? ['c', 'a', 'b'] : ['b', 'c', 'a'] : ['a', 'b', 'c'];
				if($type == 'robot'){
					$fight = parent::robot($room, $seat);
				}elseif($type){
					$fight = parent::fight($room, $seat, explode(',', $type));
					$fight and parent::check($fight[0], $room[$seat[1].'fight'], $room[$seat[2].'fight']) or exit('Access Denied');
				}else{
					preg_match('/\[/', $room[$seat[1].'fight']) or preg_match('/\[/', $room[$seat[2].'fight']) or exit('Access Denied');
					$fight = 'past';
				}
				$update = ['play' => $room[$seat[1].'id'], 'clock' => $time];
				if(is_array($fight)){
					$tool = parent::tool($room['count'], $room[$seat[0].'card'], $fight[0]);
					$update = array_merge($update, ['count' => $tool[0], $seat[0].'card' => $tool[1], $seat[0].'fight' => $fight[0]]);
					$update = $fight[1] ? array_merge($update, ['mult' => ['*', 2]]) : $update;
					if(empty($tool[1])){
						$score = $fight[1] ? $room['base'] * $room['mult'] * 2 : $room['base'] * $room['mult'];
						if($room['play'] == $room['lord']){
							$snext = db::fetch('member', 'pea', ['id' => $room[$seat[1].'id']]);
							$next = [1, $snext < $score ? $snext : $score];
							$sprev = db::fetch('member', 'pea', ['id' => $room[$seat[2].'id']]);
							$prev = [1, $sprev < $score ? $sprev : $score];
							$now = $next[1] + $prev[1];
						}elseif($room[$seat[1].'id'] == $room['lord']){
							$snext = db::fetch('member', 'pea', ['id' => $room['lord']]);
							$next = [1, $snext < $score * 2 ? $snext : $score * 2];
							$prev = [2, floor($next[1] / 2)];
							$now = ceil($next[1] / 2);
						}else{
							$sprev = db::fetch('member', 'pea', ['id' => $room['lord']]);
							$prev = [1, $sprev < $score * 2 ? $sprev : $score * 2];
							$next = [2, floor($prev[1] / 2)];
							$now = ceil($prev[1] / 2);
						}
						$room['base'] and parent::base($room, $seat, $now, $next, $prev, date('Y-m-d H:i:s'));
						$last = ['2:'.$now.'&'.$fight[0], $next[0].':'.$next[1].':'.$room[$seat[1].'fight'], $prev[0].':'.$prev[1].'|'.$room[$seat[2].'fight']];
						$update = array_merge($update, [$seat[0].'last' => $last[0], $seat[1].'last' => $last[1], $seat[2].'last' => $last[2]]);
						$update = array_merge($update, ['state' => 0, 'astate' => 1, 'bstate' => 1, 'cstate' => 1]);
					}
				}else{
					$update = array_merge($update, [$seat[0].'fight' => $fight]);
				}
				db::update('room', $update, ['id' => $room['id']]);
			}
		}
		echo '1';
	}
	public static function game(){
		$login = parent::login();
		$login or exit('{"status":-1}');
		lib::request(['token', $_COOKIE['member']]) or exit('{"status":"Access Denied"}');
		$rid = intval(lib::method('rid'));
		$room = parent::room($login['id']);
		$room or exit('{"status":-2}');
		$rid == $room['id'] or exit('{"status":-3}');
		$time = time();
		$seat = $room['aid'] != $login['id'] ? $room['bid'] != $login['id'] ? ['c', 'a', 'b'] : ['b', 'c', 'a'] : ['a', 'b', 'c'];
		$point = $login[$room['house'] ? 'pea' : 'gem'];
		$card = $room['state'] > 1 ? $room['card'] : '';
		$clock = $room['state'] ? 30 + $room['clock'] - $time : 0;
		$group = '"state":'.$room['state'].',"mult":'.$room['mult'].',"card":"'.$card.'","count":"'.$room['count'].'","owner":'.$room['owner'].',"lord":'.$room['lord'].',"play":'.$room['play'].',"clock":'.$clock;
		$self = json_encode(parent::person($room, $seat[0], $login['id'], $time));
		$right = json_encode(parent::person($room, $seat[1], $login['id'], $time));
		$left = json_encode(parent::person($room, $seat[2], $login['id'], $time));
		$room['house'] and parent::owner($room, $login['id'], $time);
		db::update('room', [$seat[0].'state' => 0, $seat[0].'time' => $time, 'time' => $time], ['id' => $room['id']]);
		echo '{"status":1,"point":'.$point.','.$group.',"self":'.$self.',"right":'.$right.',"left":'.$left.'}';
	}
}
?>