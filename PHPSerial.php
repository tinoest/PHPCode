<?php
				$filename = "/dev/tty.usbserial-A901J3MB";
        $fd=dio_open( $filename ,O_RDWR | O_NOCTTY | O_NDELAY);
        dio_fcntl($fd,F_SETFL,0);
				$result = '';
				do {
					$ret =dio_read($fd, 1);
					if($ret == "\n") {
						echo "RX: ".$result.PHP_EOL;
						// Should do something with the data really....
						
						$result = '';
					} 
					else {
						$result .= $ret;
					}
				} while (true);
?>
