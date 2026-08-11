<?php
namespace server\pack\smtp;
class mail{
	private $host;
	private $user;
	private $pw;
	private $port;
	private $charset;
	private $crlf = "\r\n";
	public function __construct($host, $user, $pw, $port, $charset){
		$this->host = $host;
		$this->user = $user;
		$this->pw = $pw;
		$this->port = $port;
		$this->charset = $charset;
	}
	private function connect($timeout){
		$ssl = $this->port == 465 ? 'ssl://' : '';
		$stream = @fsockopen($ssl.$this->host, $this->port, $errno, $errstr, $timeout);
		if($stream){
			$state = fread($stream, 512);
			if(substr($state, 0, 3) != 220){
				return 'SMTP Error: '.$state;
			}
			return $stream;
		}
		return 'SMTP Error: Failed to connect to server: '.$errstr.' ('.$errno.')';
	}
	private function sign($stream, $ident){
		fwrite($stream, $ident.' '.$_SERVER['SERVER_NAME'].$this->crlf);
		$state = fread($stream, 512);
		if(substr($state, 0, 3) != 250){
			return false;
		}
		return true;
	}
	private function auth($stream){
		fwrite($stream, 'AUTH LOGIN'.$this->crlf);
		$state = fread($stream, 512);
		if(substr($state, 0, 3) != 334){
			return false;
		}
		fwrite($stream, base64_encode($this->user).$this->crlf);
		$state = fread($stream, 512);
		if(substr($state, 0, 3) != 334){
			return false;
		}
		fwrite($stream, base64_encode($this->pw).$this->crlf);
		$state = fread($stream, 512);
		if(substr($state, 0, 3) != 235){
			return false;
		}
		return true;
	}
	private function from($stream, $addr){
		fwrite($stream, 'MAIL FROM:<'.$addr.'>'.$this->crlf);
		$state = fread($stream, 512);
		if(substr($state, 0, 3) != 250){
			return false;
		}
		return true;
	}
	private function rcpt($stream, $addr){
		fwrite($stream, 'RCPT TO:<'.$addr.'>'.$this->crlf);
		$state = fread($stream, 512);
		if(substr($state, 0, 3) != 250){
			return false;
		}
		return true;
	}
	private function data($stream, $option){
		fwrite($stream, 'DATA'.$this->crlf);
		$state = fread($stream, 512);
		if(substr($state, 0, 3) != 354){
			return false;
		}
		$line = [md5(uniqid($option[3].'-', true))];
		$line[] = 'Date:'.date('D,j M Y H:i:s O');
		$line[] = 'Return-Path:'.$option[0];
		$line[] = 'To:'.$option[4].' <'.$option[3].'>';
		$line[] = 'From:'.$option[1].' <'.$option[0].'>';
		$line[] = 'Subject:'.$option[2];
		$line[] = 'Message-ID:<'.$line[0].'@'.$_SERVER['SERVER_NAME'].'>';
		$line[] = 'X-Priority:3';
		$line[] = 'MIME-Version:1.0';
		$line[] = 'Content-Type:multipart/alternative;boundary="bd_'.$line[0].'"'.$this->crlf;
		$line[] = '--bd_'.$line[0];
		$line[] = 'Content-Type:text/plain;charset="'.$this->charset.'"';
		$line[] = 'Content-Transfer-Encoding:8bit';
		$line[] = $this->crlf.strip_tags($option[5]).$this->crlf;
		$line[] = '--bd_'.$line[0];
		$line[] = 'Content-Type:text/html;charset="'.$this->charset.'"';
		$line[] = 'Content-Transfer-Encoding:8bit';
		$line[] = $this->crlf.$option[5].$this->crlf;
		$line[] = '--bd_'.$line[0].'--';
		$line[] = '.'.$this->crlf;
		array_shift($line);
		$meta = implode($this->crlf, $line);
		fwrite($stream, $meta);
		fwrite($stream, 'QUIT'.$this->crlf);
		$state = fread($stream, 512);
		if(substr($state, 0, 3) != 250){
			return false;
		}
		return true;
	}
	public function send($from, $fromname, $title, $rcpt, $rcptname, $content){
		$stream = $this->connect(30);
		if(is_string($stream)){
			return $stream;
		}elseif(!$this->sign($stream, 'HELO')){
			return 'SMTP Error: Unable to confirm identity.';
		}elseif(!$this->auth($stream)){
			return 'SMTP Error: Could not authenticate.';
		}elseif(!$this->from($stream, $from)){
			return 'SMTP Error: The following From address failed: '.$from;
		}elseif(!$this->rcpt($stream, $rcpt)){
			return 'SMTP Error: The following recipients failed: '.$rcpt;
		}elseif(!$this->data($stream, func_get_args())){
			return 'SMTP Error: Data not accepted.';
		}
		return true;
	}
}
?>