#!/usr/bin/php
<?php 

date_default_timezone_set('UTC');

$server = stream_socket_server("tcp://0.0.0.0:8000", $errno, $errstr);
if (!$server) {
	echo "$errstr ($errno)<br />\n";
} else {
	while (true) {
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
										$path = '/tmp/img';
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
										print_r($headers[$i].PHP_EOL);
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
					print("Ret ".$ret.PHP_EOL);
					print("strlen ".strlen($output).PHP_EOL);
				}
			}
			stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
		}
	}
	stream_socket_shutdown($server, STREAM_SHUT_RDWR);
}
?>
