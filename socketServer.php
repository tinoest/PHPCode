#!/usr/bin/php
<?php 
// signal handler function
function sig_handler($signo) {{{

	global $parent, $childPid, $maxChildren;

	switch ($signo) {
		case SIGTERM:
			syslog(LOG_WARNING,"SIGTERM called, closing Socket Server");
			if($parent) {
				unlink('/var/run/socketServer.pid');
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
			}
			// handle shutdown tasks
			exit;
			break;
		default:
			// handle all other signals
	}

}}}

function fork_child($parent) {{{

	if($parent == FALSE && file_exists('/var/run/socketServer.pid')) {
		die("Socket Server Running");
	}

	$pid	= pcntl_fork();
	if($pid == -1) {
		return 1;
	}
	else if($pid) {
		if($parent == FALSE) {
			file_put_contents('/var/run/socketServer.pid',$pid);
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

function handle_request($socket) {{{ 
			global $path;
	
			$request = stream_socket_recvfrom($socket, 1500);
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
				if(!empty($output)) { 
					// return data based upon the request
					$ret = stream_socket_sendto($socket, $output);
				}
			}
			stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
			return;
}}}

function child_process($server) {{{

	$timeout = 1;
	// setup signal handlers
	pcntl_signal(SIGTERM, "sig_handler");

	// We are the child...loop forever
	for(;;) {
		pcntl_signal_dispatch(); // remember to call the signal dispatch to see if we have any waiting signals
		$socket = @stream_socket_accept($server, $timeout, $peername);
		//stream_set_blocking($socket,FALSE);
		if(is_resource($socket)) {
			syslog(LOG_INFO, 'Accepted Stream from '.$peername.' Memory usage '.get_memory_usage());
			handle_request($socket);
			stream_socket_shutdown($socket,STREAM_SHUT_RDWR);
		}
		usleep(1); 
	}

}}}

function get_memory_usage() {{{

	return number_format(memory_get_usage(true)/1048576,2).' MB';

}}}

date_default_timezone_set('UTC');

$maxChildren	= 2;
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
$server = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);
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
					child_process($server);
					$parent = FALSE;
				}
				else {
					syslog(LOG_INFO,"Created Child {$childPid[$c]}");
				}
			} 
			else { 
				//syslog(LOG_INFO,"Checking Child {$childPid[$c]}");
				$res = pcntl_waitpid($childPid[$c], $stat, WNOHANG);
				// If the process has already exited
				if($res == -1 || $res > 0) { 
					syslog(LOG_INFO,"Child {$childPid[$c]} Status $res");
					$childPid[$c] = FALSE;
				}
			}
		}
		sleep(1);
	}
	closelog();
	stream_socket_shutdown($server, STREAM_SHUT_RDWR);
}


?>
