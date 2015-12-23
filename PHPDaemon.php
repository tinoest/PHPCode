<?php
class Log {

	public $prefix;

  function Log ( $facility=LOG_LOCAL4 , $ident = __CLASS__ , $option = FALSE ) {{{

    if(empty($option)) {
      $option = (LOG_PID|LOG_ODELAY);
    }
    $this->prefix = '';

    return openlog( $ident , $option , $facility );

  }}}

  function syslog($msg , $level = FALSE ) {{{

      if(empty($level)) {
        $priority = LOG_INFO;
      } else {
        $priority = (int)$level;
      }

      $len  = strlen ($msg);
      $p    = 0; // Linux has a length of 500 characters wrap the string
      while ( $p < $len ) {
        $str  = substr($msg,$p,'480');
        $p    = $p + '480';
        syslog ($priority , $this->prefix.$str );
      }

  }}}

  function close () {{{

      return closelog();

  }}}

}

class PHPDaemon {

	public $log;
	public $sleep;
	private $daemonName;

	function signal_handler($signo) {{{

		switch ($signo) {
			case SIGTERM:
				$this->log->syslog("Stopping $this->daemonName Process");
				$this->log->close();
				unlink("/var/run/$this->daemonName.pid");
				exit;
				break;
			default:
		// handle all other signals
		}

	}}}

	function init_tasks() {{{

		if (posix_setsid() === -1) {
			die('Could not setsid');
		}

		fclose(STDIN);  // Close all of the standard 
		fclose(STDOUT); // file descriptors as we 
		fclose(STDERR); // are running as a daemon. 
		set_time_limit (0);
		chdir("/");
		// setup signal handlers
		foreach(array(SIGTERM, SIGINT, SIGUSR1, SIGHUP, SIGCHLD) as $signal)
			pcntl_signal($signal, array($this, 'signal_handler'));


	}}}

	function init( $daemonName = 'daemon' ) {{{

		if(file_exists("/var/run/$daemonName.pid")) {
			echo("$daemonName is already running!\n");
			exit;
		}	

		$this->daemonName = $daemonName;

		// Setup log class
		$this->log = new Log(LOG_LOCAL5 , $this->daemonName);

		$pid 			= pcntl_fork();
		switch($pid) {
		case -1:
			return 1;
			break;
		case 0:
			$this->childPid = getmypid();
			$this->log->syslog("Forked - child process ($this->childPid)");
			$this->init_tasks();			
			$this->sleep = 1; // Default to 1 second
			break;
		default:
			file_put_contents("/var/run/$this->daemonName.pid",$pid);
			exit;
		}	


		return;

	}}}

	function run( $function ) {{{

		// loop forever
		for (;;) {
			pcntl_signal_dispatch(); // remember to call the signal dispatch to see if we have any waiting signals
			// Check to see if its a function call 
			if (is_string($function) && function_exists($function)) { 
				call_user_func_array($function,array(&$this->log));
			} 
			// Check to see if its an anonymous function
			else if(is_object($function) && ($function instanceof Closure)) {
				$function($this->log); // Function we wish to call
			} 
			sleep($this->sleep);
		}

	}}}

}

?>
