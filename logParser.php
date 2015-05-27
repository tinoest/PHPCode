#!/usr/bin/php
<?php

$logFile		= '/var/log/Daemon.log';
$delimiter	= "\n";
$pattern		= '/ERROR/i';

if(!(file_exists($logFile) && is_readable($logFile)) ) {
	die("Can't Read File".PHP_EOL);
}


$pid 			= pcntl_fork();
if($pid == -1){
	return 1;
}
else if($pid){
	file_put_contents('/var/run/logParser.pid',$pid);
	exit; 
}
else{
	// We are the child...
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

	$oldSize		= 0;
	$handle			= fopen($logFile,'r');

	// loop forever
	for (;;) {

		pcntl_signal_dispatch(); // remember to call the signal dispatch to see if we have any waiting signals
		$line = stream_get_line($handle, 10000 , $delimiter); 
		if($line === FALSE) {
			sleep(1); 
		} else {
			$count = preg_match_all($pattern, $line, $matches);
			if($count > 0 )	{
				smtp_mail('a@email.com','Error Found',$line);
			}
		}

		// Clear the stat cache each time
		clearstatcache();
		$size = filesize($logFile);
		if($oldSize > $size) {
			// We must of rotated close the handle and open a new one
			fclose($handle);
			$handle		= fopen($logFile,'r');			
			$oldSize	= $size = 0;
		} else { 
			$oldSize	= $size;
		}
	} 

	fclose($handle);
	exit;
}

function smtp_mail($to, $subject, $message, $headers = '') {{{

 	$recipients = explode(',', $to);
	$user				= 'from@mail.com';
	$mailfrom		= '<from@mail.com>';
	$pass				= 'somepassword';
	$smtp_host	= 'ssl://smtp.gmail.com';
	$smtp_port	= 465;

	//echo( "Sending email to: ".var_export($to,TRUE));

	if (!($socket = fsockopen($smtp_host, $smtp_port, $errno, $errstr, 15))) {
		//echo( "Could not connect to smtp host '$smtp_host' ($errno) ($errstr)");
	}

	if(!server_parse($socket, '220')) { return FALSE; }

	fwrite($socket, 'EHLO '.$smtp_host."\r\n");
	//stream_socket_sendto($socket, 'EHLO '.$smtp_host."\r\n");
	if(!server_parse($socket, '250')) { return FALSE; }

	fwrite($socket, 'AUTH LOGIN'."\r\n");
	//stream_socket_sendto($socket, 'AUTH LOGIN'."\r\n");
	if(!server_parse($socket, '334')) { return FALSE; }

	fwrite($socket, base64_encode($user)."\r\n");
	if(!server_parse($socket, '334')) { return FALSE; }

	fwrite($socket, base64_encode($pass)."\r\n");
	if(!server_parse($socket, '235')) { return FALSE; }

	fwrite($socket, 'MAIL FROM: '.$mailfrom."\r\n");
	if(!server_parse($socket, '250')) { return FALSE; }

	foreach ($recipients as $email) {
		fwrite($socket, 'RCPT TO: <'.$email.'>'."\r\n");
		if(!server_parse($socket, '250')) { return FALSE; }
	}

	fwrite($socket, 'DATA'."\r\n");
	if(!server_parse($socket, '354')) { return FALSE; }

	fwrite($socket, 'Subject: '.$subject."\r\n".'To: <'.implode('>, <', $recipients).'>'."\r\n".$headers."\r\n\r\n".$message."\r\n");

	fwrite($socket, '.'."\r\n");
	if(!server_parse($socket, '250')) { return FALSE; }

	fwrite($socket, 'QUIT'."\r\n");
	fclose($socket);

	//echo( "Sent email to: ".var_export($to,TRUE));

	return TRUE;

}}}

function server_parse($socket, $expected_response) {{{

	$server_response = '';
	while (substr($server_response, 3, 1) != ' ') {
		if (!($server_response = fgets($socket, 256))) { 
			//echo( "Couldn't get mail server response codes. Please contact the administrator.");
			return FALSE;
		}
	}

	if (!(substr($server_response, 0, 3) == $expected_response)) { 
		//echo( "Unable to send e-mail. Please contact the administrator with the following error message reported by the SMTP server: ".var_export($server_response,TRUE));        
		return FALSE;
	}
	return TRUE;

}}}

// signal handler function
function sig_handler($signo) {{{

	global $handle;

	switch ($signo) {
		case SIGTERM:
				// handle shutdown tasks
				unlink('/var/run/logParser.pid');
				fclose($handle);
			exit;
			break;
		default:
			// handle all other signals
	}

}}}

?>
