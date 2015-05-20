#!/usr/bin/php
<?php 
// signal handler function
function sig_handler($signo) {{{

	switch ($signo) {
		case SIGTERM:
			syslog(LOG_WARNING,"SIGTERM called, closing Socket Server");
			unlink('/var/run/socketServer.pid');
			closelog();
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
		return;
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

date_default_timezone_set('UTC');

$path = '/img';

fork_child(FALSE);
// We are the child...loop forever

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

$timeout = 1;

openlog("mySocketServer", LOG_PID|LOG_ODELAY , LOG_LOCAL3);
$server = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);
if (!$server) {
	syslog(LOG_WARNING, "Socket Error Occurred Error Number ".$errno);
} else {
	for($c=0;$c<10;$c++) $childPid[$c] = FALSE; // prepopulate array with false
	for(;;) { // Loop forever
		pcntl_signal_dispatch(); // remember to call the signal dispatch to see if we have any waiting signals
		$socket = @stream_socket_accept($server, $timeout);
		if($socket) {
			for($c=0;$c<2;$c++) { 
				if($childPid[$c] == FALSE) { 
					$childPid[$c] = fork_child(TRUE);
					if($childPid[$c] == 0) { 
						$status			= "1";
						$sharedId		= shmop_open(getmypid(),"c",0644,strlen($status));
						$status			= "2";
						shmop_write($sharedId,$status,0);
						handle_request($socket);
						$status			= "3";
						shmop_write($sharedId,$status,0);
						exit;
					} else {
						sleep(1); 
						$sharedId			= shmop_open($childPid[$c],"a",0,0);
						$childStatus	= shmop_read($sharedId,0,shmop_size($sharedId));
						syslog(LOG_INFO,"Created Child {$childPid[$c]} SharedID {$sharedId} Status $childStatus");
						break;
					}
				} 
			}
		}
		for($c=0;$c<2;$c++) { 
			if($childPid[$c] != FALSE) { 
				syslog(LOG_INFO,"Checking Child Complete {$childPid[$c]}");
				$res = pcntl_waitpid($childPid[$c], $stat, WNOHANG);
				// If the process has already exited
				if($res == -1 || $res > 0) { 
					$sharedId			= shmop_open($childPid[$c],"a",0,0);
					$childStatus	= shmop_read($sharedId,0,shmop_size($sharedId));
					syslog(LOG_INFO,"Child Complete {$childPid[$c]} Status $childStatus");
					shmop_delete($sharedId);
          shmop_close($sharedId);
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
