<?php
$debug		= true;

$filename = "/dev/tty.usbserial-A901J3MB";
$fd=dio_open( $filename ,O_RDWR | O_NOCTTY | O_NDELAY);
dio_fcntl($fd,F_SETFL,0);
$result = '';
do {
	$ret =dio_read($fd, 1);
	if($ret == "\n") {
		if($debug) echo "RX: ".$result.PHP_EOL;
		$data		= xml2array($result);
		var_export($data);		
		$result = '';
	} 
	else {
		$result .= $ret;
	}
} while (true);




function xml2array($xml) {{{

    $opened			= array();
    $xmlParser	= xml_parser_create();
    xml_parse_into_struct($xmlParser, $xml, $xmlArray);
    $array			= array();
    for($j=0;$j<count($xmlArray);$j++){
        $val		= $xmlArray[$j];
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
