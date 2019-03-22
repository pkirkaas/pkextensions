<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Processor\WebProcessor;
use Monolog\Processor\MemoryUsageProcessor;
use Monolog\Formatter\LineFormatter;
use Request;
use Sentry;
use Config;

class PkLogger {
  #A static array of log names to log instances
  public static $logs = [];

  /** 
   * Writes data to the named log. We get the logname from the named arg, the
   * remaining args we get with 
   * @param string $logname
   */
  public static function write($logname) {

  }

  
  
}
