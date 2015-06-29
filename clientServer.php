#!/usr/bin/php
<?php 
// signal handler function
function sig_handler($signo) {{{

	global $parent, $childPid, $maxChildren;

	switch ($signo) {
		case SIGTERM:
			syslog(LOG_WARNING,"SIGTERM called, closing Socket Server");
			if($parent) {
				unlink('/var/run/clientServer.pid');
				for($c=0;$c<$maxChildren;$c++) {
					$try = 0;
					while($childPid[$c] != FALSE) {
						if($try++ > 10 ) {
							syslog(LOG_WARNING,"Sending SIGKILL to Child {$childPid[$c]}");
							posix_kill($childPid[$c], SIGKILL);
						} else {
							syslog(LOG_WARNING,"Sending SIGTERM to Child {$childPid[$c]}");
							posix_kill($childPid[$c], SIGTERM);
						}

						$res = pcntl_waitpid($childPid[$c], $status, WNOHANG);
        
						// If the process has already exited
						if($res == -1 || $res > 0)
							$childPid[$c] = FALSE;						

						sleep(1);
					}
				}
				closelog();
			} else {
				unlink('/tmp/internalSocket-'.getmypid());
			}
			// handle shutdown tasks
			exit;
			break;
		default:
			// handle all other signals
	}

}}}

function fork_child($parent) {{{

	if($parent == FALSE && file_exists('/var/run/clientServer.pid')) {
		die("Socket Server Running");
	}

	$pid	= pcntl_fork();
	if($pid == -1) {
		return 1;
	}
	else if($pid) {
		if($parent == FALSE) {
			file_put_contents('/var/run/clientServer.pid',$pid);
			exit;
		} 
		else { 
			return $pid;
		}
	}
	else {
		// we are the child
		return 0;
	}

}}}

function handle_request($request) {{{ 
			global $path;
	
			//$request = stream_socket_recvfrom($socket, 1500);
			if(!empty($request)) {
				$output		= '';
				$headers	= explode("\n",$request);
				if(is_array($headers)) { 
					// parse the request 
					for($i=0;$i<sizeof($headers);$i++) {
						if(strstr($headers[$i],':') !== FALSE) {
							list($headerType,$headerValue) = explode(':',$headers[$i]);
							$headerType		= trim($headerType); 
							$headerValue	= trim($headerValue);
						} else /*if ( strstr($headers[$i],'HTTP')) */{
							if(!empty($headers[$i])) { 
								switch(true) { 
									case strstr($headers[$i],'GET'):
									case strstr($headers[$i],'get'):
										preg_match('/\ (.*?)\ /',$headers[$i],$requestURI); 
										$requestURI = $requestURI[1];
										if ($requestURI == "/") {
											$requestURI = "/index.html";
										}
										syslog(LOG_INFO,"RequestURI ".$requestURI);
										if (file_exists($path.$requestURI) && is_readable($path.$requestURI)) {
											$contents = file_get_contents($path.$requestURI);
											$mime			= "image/jpg";
											$output		= "HTTP/1.1 200 OK\r\nServer: PHPSocketServer\r\nConnection: close\r\nContent-Type: $mime\r\n\r\n$contents";
										} else {
											$contents = "The file you requested does not exist.";
											$output		= "HTTP/1.1 404\r\nServer: PHPSocketServer\r\nConnection: close\r\nContent-Type: text/html\r\n\r\n$contents";
										}	
										break;
									case strstr($headers[$i],'POST'):
									case strstr($headers[$i],'post'):
										break;	
									default:
										break;
								}
							}
						}
					}
				}
				//if(!empty($output)) { 
					// return data based upon the request
					//$ret = stream_socket_sendto($socket, $output);
				//}
			}
			syslog(LOG_WARNING, "Child $output");
			//stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
			return $output;
}}}

function child_process() {{{

	// setup signal handlers
	pcntl_signal(SIGTERM, "sig_handler");

	$childSocket = stream_socket_server("unix:///tmp/internalSocket-".getmypid(), $errno, $errstr);
	if($childSocket === FALSE) {
		syslog(LOG_WARNING, "Socket Failed to create");
	}
	/*$bind	= socket_bind($childSocket,'/tmp/internalSocket-'.getmypid());
	if($bind === FALSE) { 
		syslog(LOG_WARNING, "Socket Failed to Bind");
	} 
	$timeout = 1;
*/
	// We are the child...loop forever
	for(;;) {
		pcntl_signal_dispatch(); // remember to call the signal dispatch to see if we have any waiting signals
		while ($conn = stream_socket_accept($childSocket)) {
			pcntl_signal_dispatch(); // remember to call the signal dispatch to see if we have any waiting signals
			//$address	= null;
			//$stream		= stream_socket_recvfrom($conn, 1500, 0, $address);
			$stream		= stream_socket_recvfrom($conn, 1500);
			$address	= stream_socket_get_name($conn,false);
			syslog(LOG_WARNING, "Socket Received Stream $stream from $address");
			$response = handle_request($stream);
			//fwrite($conn, 'The local time is ' . date('n/j/Y g:i a') . "\n");
			$response = 'The local time is ' . date('n/j/Y g:i a');
			stream_socket_sendto($conn, $response);
			fclose($conn);
		}
		usleep(1); 
	}

}}}

date_default_timezone_set('UTC');

$timeout			= 1;
$maxChildren	= 1;
$path					= '/img';
$parent				= TRUE;
fork_child(FALSE);

if (posix_setsid() === -1) {
     die('Could not setsid');
}

fclose(STDIN);  // Close all of the standard 
fclose(STDOUT); // file descriptors as we 
fclose(STDERR); // are running as a daemon. 
set_time_limit (0);

chdir("/");

// setup signal handlers
pcntl_signal(SIGTERM, "sig_handler");

openlog("mySocketServer", LOG_PID|LOG_ODELAY , LOG_LOCAL3);
$server = stream_socket_server("tcp://0.0.0.0:8080", $errno, $errstr);
if (!$server) {
	syslog(LOG_WARNING, "Socket Error Occurred Error Number ".$errno);
} else {
	for($c=0;$c<$maxChildren;$c++) $childPid[$c] = FALSE; // prepopulate array with false
	for(;;) { // Loop forever

		pcntl_signal_dispatch(); // remember to call the signal dispatch to see if we have any waiting signals
		// fork the children 
		for($c=0;$c<$maxChildren;$c++) { 
			if($childPid[$c] == FALSE) {
				$childPid[$c] = fork_child(TRUE);
				if($childPid[$c] == 0) { 
					$parent = FALSE;
					child_process();
				}
				else {
					syslog(LOG_INFO,"Created Child {$childPid[$c]}");
				}
			} 
			else { 
				$socket = @stream_socket_accept($server, $timeout);
				if($socket) {
					$stream = stream_socket_recvfrom($socket, 1500);
					$remote	= stream_socket_get_name($socket,true);
					syslog(LOG_INFO,"Sending Stream to Child /tmp/internalSocket-{$childPid[$c]} from $remote");
					$fp = stream_socket_client("unix:///tmp/internalSocket-".$childPid[$c], $errno, $errstr, 30);
					if (!$fp) {
						syslog(LOG_WARNING, "Socket Error $errstr ($errno)");
					} else {
						$output = '';
						stream_socket_sendto($fp,$stream);
						$output = stream_socket_recvfrom($fp,4500);
						syslog(LOG_WARNING, "Socket $output");
						fclose($fp);
						$ret = stream_socket_sendto($socket, $output);
					}

				}
				//syslog(LOG_INFO,"Checking Child {$childPid[$c]}");
				$res = pcntl_waitpid($childPid[$c], $stat, WNOHANG);
				// If the process has already exited
				if($res == -1 || $res > 0) { 
					syslog(LOG_INFO,"Child {$childPid[$c]} Status $res");
					$childPid[$c] = FALSE;
				}
			}
		}
		usleep(1);
	}
	closelog();
	stream_socket_shutdown($server, STREAM_SHUT_RDWR);
}


?>
