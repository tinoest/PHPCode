#!/usr/bin/php
<?php 

date_default_timezone_set('UTC');

$server = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);
if (!$server) {
	echo "$errstr ($errno)<br />\n";
} else {
	$i = 0;
	while (true) {
		$socket = stream_socket_accept($server);
		if($socket) {	
			$request = stream_socket_recvfrom($socket, 1500);
			// parse the request 
			
			// return data based upon the request
			stream_socket_sendto($socket, 'Connection accepted from ' . stream_socket_get_name($socket, true) . "\n");
			stream_socket_sendto($socket, 'The local time is ' . date('n/j/Y g:i a') . "\n", STREAM_OOB);
			stream_socket_shutdown($socket, STREAM_SHUT_WR);
		}
		if($i++ > 10) break;
	}
	stream_socket_shutdown($server, STREAM_SHUT_WR);
}
?>
