#!/usr/bin/php
<?php
require_once('PHPDaemon.php');
$daemon = new PHPDaemon();
$daemon->init('exampleDaemon');
// Pass function we wish to run, the log class is passed back to assist debug
$daemon->run(function( &$log ) {	
	$log->syslog('running function');

 }
);

?>
