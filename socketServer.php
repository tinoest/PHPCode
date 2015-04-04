#!/usr/bin/php
<?php 
declare(ticks = 1);

// signal handler function
function sig_handler($signo) {{{
	global $conn;

	switch ($signo) {
		case SIGKILL:
			syslog(LOG_WARNING,"SIGKILL Called");
			pg_close($conn);
			closelog();
			// handle shutdown tasks
			exit;
			break;
		default:
			// handle all other signals
	}

}}}

date_default_timezone_set('UTC');

$path = '/tmp/img';

$pid	= pcntl_fork();
if($pid == -1){
	return 1;
}
else if($pid){
	exit; 
}
else{
	// We are the child...loop forever
	fclose(STDIN);  // Close all of the standard 
	fclose(STDOUT); // file descriptors as we 
	fclose(STDERR); // are running as a daemon. 

	// setup signal handlers
	pcntl_signal(SIGKILL, "sig_handler");

	openlog("mySocketServer", LOG_PID|LOG_ODELAY , LOG_LOCAL3);
	$server = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);
	if (!$server) {
		syslog(LOG_WARNING, "Socket Error Occurred Error Number ".$errno);
	} else {
		for(;;) { // Loop forever
			$socket = @stream_socket_accept($server);
			if($socket) {	
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
			}
		}
		closelog();
		stream_socket_shutdown($server, STREAM_SHUT_RDWR);
	}
}
?>
