<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Monolog\Logger as MonologLogger;
use Monolog\Handler\RotatingFileHandler;

class Logger{

	private $logFile;
	private $monologLogger = null;
	
	public function lfile($path) {
        $this->logFile = $path;
    }

    public function lwrite($message){
        if(!$this->monologLogger) {
			$this->lopen();
        }
        $scriptName = pathinfo($_SERVER['PHP_SELF'], PATHINFO_FILENAME);
        $this->monologLogger->info("$scriptName, $message");
    }

    private function lopen(){
        $this->monologLogger = new MonologLogger('wavss');
        $handler = new RotatingFileHandler($this->logFile . '.txt', 14);
        $handler->setFilenameFormat('{filename}_{date}', 'Y-m-d');
        $this->monologLogger->pushHandler($handler);
    }
}
