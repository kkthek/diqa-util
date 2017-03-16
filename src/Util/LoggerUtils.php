<?php
namespace DIQA\Util;

/**
 * @author Michael
 *
 */
class LoggerUtils {
	private $logEntries = array();
	private $id = '';
	private $keepMessages = 'OFF';
	private $logpath = '';
	private $globalLogpath = '';
	private $doConsoleLog = true;
	/**
	 * set $wgODBLogLevel to on of these
	 */
	const LOG_LEVELS = array (
			'OFF' => 0,
			'TRACE' =>  1,
			'DEBUG' =>  2,
			'LOG' =>  3,
			'INFO' =>  3,
			'' =>  3,
			'WARN' =>  4,
			'ERROR' =>  5,
			'FATAL' =>  6
	);
	
	public function setConsoleLog($doConsoleLog) {
		$this->doConsoleLog = $doConsoleLog;
	}

	/**
	 * create a Logger for the extension and use the $id in each line to indicate the root
	 * for the log entry.
	 *
	 * @param string $id
	 * @param string $extension leave blank to write to the general log-file
	 * @param string $keepMessages, cf. LOG_LEVELS
	 */
	public function __construct($id, $extension='', $keepMessages = 'OFF') {
		global $IP;

		$this->id = $id;
		$this->keepMessages = $keepMessages;

		$date = (new \DateTime('now', new \DateTimeZone(date_default_timezone_get())))->format("Y-m-d");

		if($extension == '') {
			$this->logpath = '';
		} else {
			$this->logpath = "$IP/extensions/$extension/logFiles/{$extension}_$date.log";
			static::ensureDirExists ($this->logpath);
		}

		$this->globalLogpath = "$IP/logs/general_$date.log";
		static::ensureDirExists ($this->globalLogpath);
	}

	/**
	 * creates the directory for the gien $filename, if it does not exist, and makes it writable
	 */
	 static private function ensureDirExists($filename) {
		$logdir= dirname($filename);
		if(!file_exists($logdir)) {
			mkdir($logdir);
			chmod($logdir, 0775);
		}
	}

	/**
	 * @return String the log level for this logger
	 */
	private function logLevel() {
		global $wgODBLogLevel;
		if(isset($wgODBLogLevel)) {
			return static::LOG_LEVELS[$wgODBLogLevel];
		} else {
			return static::LOG_LEVELS['LOG'];
		}
	}

	/**
	 * @return array of messages created during processing
	 */
	public function getLogMessages() {
		return $this->logEntries;
	}

	/**
	 * @return string of log messages created during processing
	 */
	public function getLogMessagesAsString() {
		$y = "";
		foreach ($this->logEntries as $log) {
			$y .= $log ."\n";
		}
		return $y;
	}

	public function clearLogMessages() {
		$this->logEntries = array();
	}

	public function trace($message) {
		$this->logMessage('TRACE', $message);
	}

	public function debug($message) {
		$this->logMessage('DEBUG', $message);
	}

	public function log($message) {
		$this->logMessage('', $message);
	}

	public function warn($message) {
		$this->logMessage('WARN', $message);
	}

	public function error($message) {
		$this->logMessage('ERROR', $message);
	}

	/**
	 * @deprecated use error() instead
	 */
	public function logError($message) {
		$this->error($message);
	}

	public function fatal($message) {
		$this->logMessage('FATAL', $message);
	}

	private function logMessage($level, $message) {
		$this->keepMessage($level, $message);

		if($this->logLevel() > static::LOG_LEVELS[$level]) {
			return;
		}

		if(strlen($level) == 0) {
			$level .= '     ';
		} else if(strlen($level) == 3) {
			$level .= '  ';
		} else if(strlen($level) == 4) {
			$level .= ' ';
		}

		$message = date('H:i:s') . " " .  $level . " " . $this->id . " - " . $message . "\n";

		$this->logToConsole( $message );
		$this->logToFile( $message );
		$this->logToGlobalFile( $message );
	}

	 /**
	  * add message to $this->logEntries[] for later retrieval
	  * @param string $level
	  * @param string $message
	  */
	 private function keepMessage($level, $message) {
		if(static::LOG_LEVELS[$this->keepMessages] > static::LOG_LEVELS[$level]) {
			return;
		}

		if($level == '' || $level == 'LOG'){
			$keepMessage = $message;
		} else {
			$keepMessage = $level . " " . $message;
		}
		$this->logEntries[] = $keepMessage;
	}

	/**
	 * Log messages to log file
	 * @param string $message
	 * @return void
	 */
	private function logToFile($message) {
		if($this->logpath != '') {
			$logfile = fopen($this->logpath, "a");
			if($logfile) {
				fwrite($logfile, $message);
				fclose($logfile);
			}
		}
	}

	/**
	 * Log messages to log file
	 * @param string $message
	 * @return void
	 */
	private function logToGlobalFile($message) {
		if($this->globalLogpath != '') {
			$logfile = fopen($this->globalLogpath, "a");
			if($logfile) {
				fwrite($logfile, $message);
				fclose($logfile);
			}
		}
	}

/**
	 * Log messages to console only, when in CLI-mode
	 * @param string $message
	 * @return void
	 */
	 private function logToConsole($message) {
		if ( PHP_SAPI === 'cli' && !defined('UNITTEST_MODE') && $this->doConsoleLog) {
			echo $message;
		}
	 }
	 
	 public function getLogPath() {
	 	return $this->logpath;
	 }

}