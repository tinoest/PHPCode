<?php

$pid = file_get_contents('/var/run/socketServer.pid');
echo "Sending SIGTERM to $pid";
posix_kill($pid, SIGTERM);

?>
