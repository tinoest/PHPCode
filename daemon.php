#!/usr/bin/php
 
<?php
require_once('config.php');
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

$debug    = true;
$log 			= '/var/log/USBSerialDaemon.log';
$filename = "/dev/tty.usbserial-A901J3MB";
$pid 			= pcntl_fork();
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
	
	openlog("myDaemon", LOG_PID|LOG_ODELAY , LOG_LOCAL3);
	
	$fd=dio_open( $filename ,O_RDWR | O_NOCTTY | O_NDELAY);
	dio_fcntl($fd,F_SETFL,0);
	$result = '';
	for(;;) { 
		$ret = dio_read($fd, 1);
		if($ret == "\n") {
			if($debug) syslog(LOG_DEBUG, "RX: ".$result.PHP_EOL); // <xml><n>8</n><c>140</c><tmpr>16</tmpr><v>2880</v></xml>
			$data   = xml2array($result);
			$data   = $data['xml'];
			if(array_key_exists('tmpr',$data)) {
				$conn   = pg_connect("dbname=$database user=$user password=$password");
				$sql    = "INSERT INTO raw_data ( log_dt , tmpr , batt , node ) VALUES ( NOW() , {$data['tmpr'][0]} , {$data['v'][0]} , {$data['n'][0]} );";
				$result = pg_query($conn, $sql);
				if (!$result) {
					syslog(LOG_WARNING, "Database Error Occurred ".pg_last_error($conn));
				}
			}
			$result = '';
		}
		else {
			$result .= $ret;
		}
	}
	closelog();
	pg_close($conn);
}


function child_init() {{{

	$fd=dio_open( $filename ,O_RDWR | O_NOCTTY | O_NDELAY);
	dio_fcntl($fd,F_SETFL,0);
	return $fd;

}}}

function xml2array($xml) {{{

    $opened     = array();
    $xmlParser  = xml_parser_create();
    xml_parse_into_struct($xmlParser, $xml, $xmlArray);
    $array      = array();
    for($j=0;$j<count($xmlArray);$j++){
        $val    = $xmlArray[$j];
        switch($val["type"]){
            case "open":
                $opened[strtolower($val["tag"])] = $array;
                unset($array);
                break;
            case "complete":
                $array[strtolower($val["tag"])][] = $val["value"];
            break;
            case "close":
                $opened[strtolower($val["tag"])] = $array;
                $array = $opened;
            break;
        }
    }
    return $array;

}}}


?>
