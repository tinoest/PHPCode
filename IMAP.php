<?php
/*
Copyright (c) 2017, tinoest
All rights reserved.

Redistribution and use in source and binary forms, with or without
modification, are permitted provided that the following conditions are met:

* Redistributions of source code must retain the above copyright notice, this
  list of conditions and the following disclaimer.

* Redistributions in binary form must reproduce the above copyright notice,
  this list of conditions and the following disclaimer in the documentation
  and/or other materials provided with the distribution.

* Neither the name of the {organization} nor the names of its
  contributors may be used to endorse or promote products derived from
  this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE
FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY,
OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

This license may be viewed online at http://opensource.org/licenses/BSD-3-Clause
*/
class IMAP
{
		private $sock;
		private $host;
		private $port;
		private $sid;
		private $tag;
		private $lastTag;
		private $currentMailbox;

		public function __construct() {{{

			$this->sock 					= null;
			$this->host 					= "localhost";
			$this->port 					= "993";
			$this->sid 						= "";
			$this->tag 						= 0;
			$this->lastTag				= $this->tag;
			$this->currentMailbox = "";

		}}}

		public function __destruct() {{{

			$this->send_command('LOGOUT');
			fclose($this->sock);

		}}}

		public function connect($host = "", $port = "", $ssl = false) {{{

			$this->host		= $host;
			$this->port		= $port;

			if($ssl == true) {
				$this->host = "ssl://".$host;
			}

			if( function_exists( 'stream_socket_client' ) ) {
				$socket	= stream_socket_client( $this->host.':'.$this->port, $errno, $errstr, 15 );
			}
			else {
				$socket = fsockopen( $this->host, $this->port, $errno, $errstr, 15 ); 
			}

			if(!$socket) {
				return false;
			}

			$this->sock	= $socket;


			if(!$this->_assumed_next_line('* OK')) {
				return false;
			}

		}}}

		public function get_tag() {{{

			$this->tag				= $this->tag + 1;
			$this->lastTag		= "A00".$this->tag;

			return $this->tag;
		
		}}}

		public function login($username, $password) {{{

			$this->send_command('LOGIN '.$username.' '.$password);

			if(!$this->read_response($this->lastTag)) {
				return false;
			}
			
			return TRUE;
		
		}}}

		public function list_mailbox() {{{

			$this->send_command('LIST "" "*"');
			$response = $this->read_response($this->lastTag);

			return $response;

		}}}

		public function select_mailbox($mailbox) {{{

			$this->send_command('SELECT '.$mailbox);
			$this->currentMailbox = $mailbox;

			$this->read_response($this->lastTag);
		
		}}}

		public function count_messages() {{{

			$this->send_command('SEARCH ALL');
			$response = $this->read_response($this->lastTag);
			if(!is_array($response) || empty($response)) {
				return false;
			}

			$emails		= explode(' ', $response[0]);
			unset($emails[0]);
			$emails		= array_values($emails);
			
			return count($emails);

		}}}

		public function get_subject($msgId) {{{

			if(empty($msgId)) {
				return false;
			}

			$this->fetch($msgId." BODY[HEADER.FIELDS (subject)]");

			$array = array();
			while(!$this->read_line($tokens, $this->lastTag)) {
				if(!empty($tokens) && preg_match("/FETCH/", $tokens)) {
					$msgId = preg_split("/ /", $tokens, 2);
					$msgId = $msgId[0];
				}

				if(!empty($tokens) && !preg_match("/FETCH/", $tokens)) {
					$array[] = array("id" => $msgId, "subject" => $tokens);
				}

			}

			return mb_decode_mimeheader($array[0]['subject']);

		}}}

		public function get_message_text($msgId = "") {{{

			if(empty($msgId)) {
				return false;
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
				return false;
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
				return false;
			}

			$this->fetch($msgId." BODY[HEADER.FIElDS (From)]");

			while(!$this->read_line($tokens, $this->lastTag)) {
				if(!isset($return) && !preg_match('/FETCH/', $tokens)) {
					$return = htmlspecialchars($tokens);
				}
			}

			if(empty($return)) {
				return false;
			}

			return mb_decode_mimeheader($return);
		
		}}}

		private function fetch($command, $param = false) {{{
			
			if(!$param) {
				$this->send_command('FETCH '.$command);
			}
			else {
				$this->send_command('FETCH '.$command.' '.$param);
			}

		}}}

		function _next_line() {{{
			
			$line = @fgets($this->sock);
			
			if ($line === false) {
				return false;
			}

			//echo "DEBUG $line\n";

			return $line;

		}}}

		protected function _wait_for_response($start) {{{
		
			do {
				$line = $this->_next_line();
			}
			while (strpos($line, $start) === false);
			
		}}}

		protected function _assumed_next_line($start) {{{
		
			$line = $this->_next_line();
			
			return strpos($line, $start) === 0;
			
		}}}

		public function read_line(&$tokens = array(), $wantedTag = '*', &$tag="") {{{

			$this->_next_tagged_line($tag, $line);
			$tokens = $line;
			$tag		= $tag;

			if(($tag == "A00".$this->tag) || ($tag == $wantedTag)) {
				return $tag;
			}

		}}}

		public function read_response($tag="*") {{{

			$lines = array();
			while (!$this->read_line($tokens, $tag)) {
				$lines[] = $tokens;
			}

			return $lines;
		
		}}}

		protected function _next_tagged_line(&$tag, &$line) {{{
			
			$line		= $this->_next_line();

			$parts	= preg_split('/(\s)/', $line, PREG_SPLIT_DELIM_CAPTURE);
			if(!is_array($parts)) {
				return false;
			}

			$tag		= $parts[0];
			$line		= $parts[1];

			return TRUE;

		}}}

		protected function send_command($cmd) {{{

			//echo "SEND $cmd\n";

			fwrite($this->sock, 'A00'.$this->get_tag().' '.$cmd."\r\n");

		}}}

}
