<?php
class IMAP
{
		private $sock;
		private $host;
		private $port;
		private $sid;
		private $tag;
		private $lastTag;
		private $currentMailbox;

		function __construct() {{{

			$this->sock 					= null;
			$this->host 					= "localhost";
			$this->port 					= "993";
			$this->sid 						= "";
			$this->tag 						= 0;
			$this->lastTag				= $this->tag;
			$this->currentMailbox = "";

		}}}

		function __destruct() {{{

			$this->send_command('LOGOUT');

		}}}

		function connect($host = "", $port = "", $ssl = FALSE) {{{

			$this->host		= $host;
			$this->port		= $port;

			if($ssl == true) {
				$this->host = "ssl://".$host;
			}

			$this->sock		= fsockopen($this->host, $this->port, $errno, $errstr);

			if(!$this->_assumed_next_line('* OK')) {
				return FALSE;
			}

		}}}

		function get_tag() {{{

			$this->tag				= $this->tag + 1;
			$this->lastTag	= "A00".$this->tag;

			return $this->tag;
		
		}}}

		function login($username, $password) {{{

			$this->send_command('LOGIN '.$username.' '.$password);

			if(!$this->_assumed_next_line($this->lastTag)) {
				return FALSE;
			}

			return TRUE;
		
		}}}

		function select_mailbox($mailbox) {{{

			$this->send_command('SELECT '.$mailbox);
			$this->currentMailbox = $mailbox;

			$this->read_response($this->lastTag);
		
		}}}

		function count_messages() {{{

			$this->send_command('SEARCH ALL');
			$response = $this->read_response($this->lastTag);
			if(!is_array($response) && empty($emails)) {
				return FALSE;
			}

			$emails		= explode(' ', $response[0]);
			unset($emails[0]);
			$emails		= array_values($emails);
			
			return count($emails);

		}}}

		public function get_subject($msgId) {{{

			if(empty($msgId)) {
				return FALSE;
			}

			$this->fetch($msgId." BODY[HEADER.FIELDS (subject)]");

			$array = array();
			while(!$this->read_line($tokens, $this->lastTag)) {
				if(!empty($tokens) && preg_match("/FETCH/", $tokens)) {
					$msgId = preg_split("/ /", $tokens, 2);
					$msgId = $msgId[0];
				}

				if(!empty($tokens) && !preg_match("/FETCH/", $tokens)) {
					$array[] = array("id" => $msgId,
							"subject" => $tokens);
				}

			}

			return mb_decode_mimeheader($array[0]['subject']);

		}}}

		public function get_message_text($msgId="") {{{

			if(empty($msgId)) {
				return FALSE;
			}

			$this->fetch($msgId." BODY[TEXT]");

			$array = array();
			$array[] = "=?ISO-8859-1?Q?";
			while(!$this->read_line($tokens, $this->lastTag, $tag)) {
				$array[] .= $tag.' '.$tokens;
			}

			unset($array[1]);
			unset($array[count($array)]);

			$array = array_values($array);

			$array = implode('', $array);

			return mb_decode_mimeheader($array);
		
		}}}

		public function get_to($msgId = "") {{{

			if(empty($msgId)) {
				return FALSE;
			}

			$this->fetch($msgId." BODY[HEADER.FIElDS (To)]");

			while(!$this->read_line($tokens, $this->lastTag)) {
				if(!isset($return) && !preg_match('/FETCH/', $tokens)) {
					$return = htmlspecialchars($tokens);
				}
			}

			return mb_decode_mimeheader($return);
		
		}}}

		public function get_from($msgId = "") {{{

			if(empty($msgId)) {
				return FALSE;
			}

			$this->fetch($msgId." BODY[HEADER.FIElDS (From)]");

			while(!$this->read_line($tokens, $this->lastTag)) {
				if(!isset($return) && !preg_match('/FETCH/', $tokens)) {
					$return = htmlspecialchars($tokens);
				}
			}

			return mb_decode_mimeheader($return);
		
		}}}

		private function fetch($command, $param = FALSE) {{{
			
			if(!$param) {
				$this->send_command('FETCH '.$command);
			}
			else {
				$this->send_command('FETCH '.$command.' '.$param);
			}

		}}}

		function _next_line() {{{
			
			$line = @fgets($this->sock);
			
			if ($line === FALSE) {
				return FALSE;
			}

			return $line;

		}}}

		protected function _assumed_next_line($start) {{{
		
			$line = $this->_next_line();
			
			return strpos($line, $start) === 0;
			
		}}}

		public function read_line(&$tokens = array(), $wantedTag = '*', &$tag="") {{{

			$line		= $this->_next_tagged_line($tag);
			$tokens = $line;
			$tag		= $tag;

			if(($tag == "A00".$this->tag) || ($tag == $wantedTag)) {
				return $tag;
			}

		}}}

		public function read_response($tag="*") {{{

			if($tag == "lastTag") {
				$tag = $this->lastTag;
			}

			$lines = array();
			while (!$this->read_line($tokens, $tag)) {
				$lines[] = $tokens;
			}

			return $lines;
		
		}}}

		protected function _next_tagged_line(&$tag) {{{
			
			$line = $this->_next_line();

			// seperate tag from line
			@list($tag, $line) = explode(' ', $line, 2);

			return $line;

		}}}

		protected function send_command($cmd) {{{

			fwrite($this->sock, 'A00'.$this->get_tag().' '.$cmd."\r\n");

		}}}

}
