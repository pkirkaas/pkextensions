<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
use \PkExtensions\Models\PkModel;

/**
 * PKMVC Framework
 *
 * @author    Paul Kirkaas
 * @email     p.kirkaas@gmail.com
 * @link
 * @copyright Copyright (c) 2012-2014 Paul Kirkaas. All rights Reserved
 * @license   http://opensource.org/licenses/BSD-3-Clause
 */
/**
 * General, non-symfony utility functions
 * Paul Kirkaas, 29 November 2012
 */
//$defaultTimeZone = date_default_timezone_get ();
date_default_timezone_set("America/Los_Angeles");
//The supported MySql date range is '1000-01-01' to '9999-12-31'.
define('MYSQL_MAXDATE', '9999-12-31');
define('MYSQL_MINDATE', '1000-01-01');

function console() {
  if (!class_exists("ChromePhp")) {
    /** Have to install the Chrome Extension - but have to include the chromePHP
     * file one & only once - doesn't work w. regular laravel. So install by:
     * composer require ccampbell/chromephp
     * then, either in index.php:
     * //require_once ("../vendor/ccampbell/chromephp/ChromePhp.php");
     * or try in composer.json:
     * autoload: {
     *         "files": [
          "vendor/ccampbell/chromephp/ChromePhp.php"
        ]
     * 
     */
    $args = func_get_args();
    array_unshift($args,"\nWhat! ChromePhp doesn't exist? Would have said:\n");
    $out = call_user_func_array("pkdebug_base", $args);
    pkdebugOut($out);
    return false;
  }
  pkdebug("CALLING CONSOLE ChromePhp!");
  $args = func_get_args();
  $out = call_user_func_array("pkdebug_base", $args);
  $cout = "\nCONSOLE OUT from PHP:\n$out\n";
  ChromePhp::log($cout);
}

class PkLibConfig {

  static $suppressPkDebug = false;

  public static function getSuppressPkDebug() {
    return static::$suppressPkDebug;
  }

  public static function setSuppressPkDebug($suppress = true) {
    static::$suppressPkDebug = $suppress;
  }

  static $warnLogName = 'app.warn.log';
  static $warnLogDir; #Defaults to same dir as debug app log
  static $isWarning = 0; #Toggled by pkwarn to 1, then pkdebugOut, then back to 0

}

/** Does nothing - so can be used in place of a real object, and no references
 * to it will cause an error. Has a single construction option - to return
 * new instances of itself so references can be chained, or just return null.
 */
Class Undefined {
  private $chain = true;
  public function __construct($chain = true) {
    $this->$chain = $chain;
  }

  public function __get($x) {
    if ($this->chain) {
      return new Undefined();
    }
    return null;
  }
  public function __set($x, $y) {
  }
  public function __call($method, $args) {
    if ($this->chain) {
      return new Undefined();
    }
    return null;
  }
  public function __toString() {
    return '';
  }
}/** This is for functions/methods that might legitimately return null or false
 * without failing - an instance of Failure can be returned instead. Like,
 * if you want the value of a key in an array - it might legitimaly be false or
 * null, a valid return - but if the key doesn't exist, it's a failure, return
 * this.
 */
Class Failure {
  public static $failure;
  public static function get($msg=null) {
    if (!static::$failure) {
      static::$failure = new Failure($msg);
    } else {
      static::$failure->msg = $msg;
    }
    return static::$failure;
  }
  public $msg='Failure';
  public function getMsg() {
    return displayData($this->msg);
  }
  public function __construct($msg = null) {
    if ($msg !== null) {
      $this->msg = $msg;
    }
  }
}

  /** So not every every method returning a failure needs to know/use the
   * the Failure class, just the ones listening for it 
   * @param mixed $msg
   * @return \Failure
   */
  function failure($msg=null) {
    return new Failure($msg);
  }
  /** And namespaces don't have to know about Failure to check, either
   * 
   * @param mixed|Failure $res - the result from another function to check
   * for failure
   * @return boolean
   */
  function didFail($res) {
    if ($res instanceOf Failure) {
      return true;
    }
    return false;
  }


/** Time/Interval logging section - for profiling */
class TimeLogger {
  // Stores moments (in micro seconds) with string key
  public static $instants = [];
  //Initializes a $keyed moment
  public static function startInterval($key) {
    static::$instants[$key] = microtime(true);
  }
  //Returns the interval from the $keyed moment til now
  public static function getInterval($key) {
    return number_format((microtime(true) - static::$instance[$key])/1000000,2);
  }
  public static $start;
  public static $current;
  public static function sinceFirstLast() {
    if (!static::$start) {
      static::$start = static::$current =  microtime(true);
    }
    $previous = static::$current;
    $now = static::$current = microtime(true);
    $fsincefirst = number_format(($now - static::$start)/1000000,2);
    $fsincelast = number_format(($now - $previous)/1000000,2);
    return "Since start: $fsincefirst; since last: $fsincelast\n";
  }
  public static function init() {
    static::$current = static::$start = microtime(true);
  }
}















function isCli() {
  return php_sapi_name() == 'cli';
}

/** UnCamelCases a string, or, recursively, an array
 *
 * @param mixed $arg: String or array of possibly camel cased values
 * @return mixed: The un_camel_cased result, string or array
 * @throws Exception
 */
function unCamelCase($arg) {
  if (!$arg || !sizeOf($arg) || is_int($arg)) {
    return $arg;
  }
  if (is_string($arg)) {
    return unCamelCaseString($arg);
  }
  if (is_array($arg)) {
    $resarr = array();
    foreach ($arg as $key => $value) {
      $resarr[$key] = unCamelCase($value);
    }
    return $resarr;
  }
  // pkdebug("Unexpected arg to unCamelCase:", $arg);
  // pkstack();
  throw new \Exception("Unexpected arg to unCamelCase: [" . print_r($arg, true) . "]");
}

function unCamelCaseString($string) {
  if (!is_string($string)) {
    throw new \Exception("No string argument to unCamelCase");
  }
  if (!strlen($string)) {
    return $string;
  }
  //$str = mb_strtolower(preg_replace("/([A-Z])/", "_$1", $string));
  $str = strtolower(preg_replace("/([A-Z])/", "_$1", $string));
  if ($str[0] == '_') {
    $str = substr($str, 1);
  }
  return $str;
}

function toCamelCase($str, $capitalise_first_char = false) {
  if (is_int($str)) return $str;
  if (!is_string($str)) return '';
  if ($capitalise_first_char) {
    $str[0] = mb_strtoupper($str[0]);
  }
  $func = create_function('$c', 'return mb_strtoupper($c[1]);');
  return preg_replace_callback('/_([a-z])/', $func, $str);
}

/** Sort of the revers of 'slugify' - takes a variable or field name & tries
 * to make it pretty as a 'label'
 * @param string $fname
 */
function labify($fname) {
  if (!ne_string($fname)) return false;
  $clean = removeEndStr($fname, '_id');
  if (!$clean) $clean = $fname;
  $resarr = [];
  $larr = explode('_', $clean);
  foreach ($larr as $seg) {
    $resarr[] = ucfirst($seg);
  }
  $label = implode(' ', $resarr);
  return $label;
}

//But I really like 'labelize' better...
function labelize($fname) {
  return labify($fname);
}

/** Removes underscores and converts to lower case - so:
 * fieldName & field_name & fieldname & Field_Name all become the same.
 * Takes a string or array of strings as arg.
 * @param string|array $arg: a string or array of strings to collapse.
 * @return string|array: The collapsed result of arg.
 */
function collapsefieldnames($arg) {
  if (is_string($arg)) {
    return strtolower(str_replace('_', '', $arg));
  }
  if (is_array($arg)) {
    $retarr = [];
    foreach ($arg as $key => $value) {
      $retarr[$key] = collapsefieldnames($value);
    }
    return $retarr;
  }
  if (is_numeric($arg)) {
    return $arg;
  }
  return '';
}

/** Returns true if the two string arguments would be equivalent if converted
 * to lowercase and with underscores removed. so (FieldId equiv to field_id)
 * @param type $arga
 * @param type $argb
 */
function equivalentcollapsed($arga, $argb) {
  if (collapsefieldnames($arga) === collapsefieldnames($argb)) {
    return true;
  }
  return false;
}

/**
 * For any number of arguments, print out the file & line number,
 * the argument type, and contents/value of the arg -- unless the very last
 * argument is a boolean false.
 */
function pkecho() {
  $args = func_get_args();
  $out = call_user_func_array("pkdebug_base", $args);
  echo "<pre>$out</pre>";
}

function tmpdbg() {
  $args = func_get_args();
  PKMVC\BaseController::addSlot('debug', call_user_func_array("pkdebug_base", $args));
}

/** Like PkDebug, except first arg is the name of a logfile -
 * To create a specific named Log function, do:
  function myLog( ) {
  $args = func_get_args();
  array_unshift($args, LOGFILE);
  return call_user_func_array('pkdebugNamedLog',$args);
  }
 */
function pkdebugNamedLog() {
  $args = func_get_args();
  //$logName = array_shift($args);
  //$logPath = getAppLogDir() . '/' . $logName;
  $logPath = array_shift($args);
  $out = call_user_func_array("pkdebug_base", $args);
  pkdebugOut($out, $logPath);
}

/* Works like pkdebug, except used for "warnings" of events that are recovered
 * from, but shouldn't happen. Also calls pkdebug, to write there as well.
 */

function pkwarn() {
  $args = func_get_args();
  $out = call_user_func_array("pkdebug_base", $args);
  PkLibConfig::$isWarning = 1;
  $warnout = "\nWarning LOG: " . date('j-M-y; H:i:s') . "\n$out\n";
  pkdebugOut($warnout); // Also writes to debug log out
  PkLibConfig::$isWarning = 0;
  return false;
  //return (keyVal(0, $args));
}

function pkdebug() {
  $args = func_get_args();
  $out = call_user_func_array("pkdebug_base", $args);
  pkdebugOut($out);
  return false;
  //return (keyVal(0, $args));
}

/** Like pkdebug, except adds time of call & ms since last call, for
 * performance debugging
 */
function pkdbgtm() {
  static $previous = 0;
  static $first = 0;
  if (!$previous) {
    $first = $previous = microtime(true); #float
  }
  $current = microtime(true);
  $diff = $current - $previous;
  $odiff = $current - $first;
  $msdiff = intval($diff * 1000);
  $omsdiff = intval($odiff * 1000);
  $previous = $current;
  $currsec = intval($current);
  $currms = intval(($current - $currsec) * 1000);
  $currsecstr = date("i:s",$currsec);
  $currtmstr=$currsecstr.".".$currms;
  $msgstr = "Called At: $currtmstr - $msdiff ms since last; - $omsdiff ms since first";

  $args = func_get_args();
  array_unshift($args,$msgstr);
  $out = call_user_func_array("pkdebug_base", $args);
  pkdebugOut($out);
  return false;




}

/** Take a stab to Try to get function/method/file this function was called from
 * Not always right because debug_backtrace is inconsistent.
 * Not top priority now - but useful for DB logging
 *
 * TODO: This version only for calling from within a LOG class - not general
 * function yet.
 * @return assoc array - file, line, function, method, etc.
 *
 */
//function getCallingFrame($level = 0, $classlevel =0) {
function getCallingFrame($baseFile = null) {
  $func = function($stacksize, $stack, $idx, $name, $skipVals, $num = 5) {
    $i = 0;
    $str = '';
    while ($idx < $stacksize) {
      if (empty($stack[$idx][$name]) || in_array($stack[$idx][$name], $skipVals)) {
        $idx++;
        continue;
      } else {
        $str .= $stack[$idx][$name] . "<br>\n";
        $num--;
        if (!$num) break;
        $idx++;
      }
    }
    return $str;
  };
  $retinfo = ['file' => '', 'function' => '', 'line' => '', 'class' => '', 'type' => ''];
  $stack = debug_backtrace();
  if (!$baseFile) $baseFile = __FILE__;
  $stacksize = sizeof($stack);

  for ($i = 0; $i < $stacksize; $i++) {
    if (!array_key_exists('file', $stack[$i])) continue;
    if ($stack[$i]['file'] == $baseFile) break;
  }
  $baseIdx = $i + 1;
  if (!array_key_exists($baseIdx, $stack)) return $retinfo;
  $file = $func($stacksize, $stack, $baseIdx, 'file', [], 5);
  $retinfo['file'] = $file;
  $line = $func($stacksize, $stack, $baseIdx, 'line', [], 5);
  $retinfo['line'] = $line;
  $baseIdx ++;
  if (!array_key_exists($baseIdx, $stack)) return $retinfo;
  $retinfo['class'] = keyVal('class', $stack[$baseIdx], '');
  $retinfo['function'] = $func($stacksize, $stack, $baseIdx, 'function', ['call_user_func_array', 'siteLog'], 5);
  $retinfo['type'] = keyVal('type', $stack[$baseIdx], '');
  $retinfo['idx'] = $baseIdx;
  return $retinfo;
}

/** Another attempt to get the calling frame */
function callingFrame2($file = null) {
  $stack = debug_backtrace();
  $retvals = [];
  $keys = ['file', 'class', 'object', 'function', 'line', 'args'];
  $uninteresting =
      ['pkdebug','pkdebug_base','call_user_func_array', __FILE__, '', $file];
  foreach ($stack as $aframe) { #Set line at the first interesting
    if (in_array(keyVal('file',$aframe), $uninteresting, 1)) {
      continue;
    }
    return $aframe;
    /*
    foreach ($keys as $key) {
      $tst = keyVal($key, $aframe);
      if (!keyVal($key, $retvals) && $tst &&
          !in_array($tst, $uninteresting) && ($key !== 'line')) {
        $retvals[$key] = $tst;
        if (!keyVal('line', $retvals) && keyVal('line', $aframe)) {
          $retvals['line'] = keyVal('line', $aframe);
        }
      }
    }
     */
  }
  return null;
}

/*
///if ((config('app.log_level'))=='debug') {
if (true) {
  //require ("../vendor/ccampbell/chromephp/ChromePhp.php");
  require ("../../../../ccampbell/chromephp/ChromePhp.php");
}
 *
 */
/** $exclusions are an array of the form:
 *
    ['file'=>__FILE__,'line'=>__LINE__,
 *  'class'=>__CLASS_, 'function' => __FUNCTION__];
 * @param type $exclusions
 * @return type
 */
function callingFrame($exclusions) {
  $stack = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 10);
  $retvals = [];
  //$keys = ['file', 'class', 'object', 'function', 'line', 'args'];
  //$keys = ['file', 'class', 'object', 'function', 'line',];
  $allskip = ['',null];
  $fuskip = array_merge(['pkdebug','pkdebug_base','call_user_func_array',
      __FUNCTION__, keyVal('function',$exclusions)] , $allskip);
  $fiskip = array_merge([__FILE__, keyVal('file',$exclusions)] , $allskip);
  $lskip = array_merge([__LINE__,keyVal('line',$exclusions)],$allskip);
  $cskip = array_merge([__CLASS__,keyVal('class',$exclusions)],$allskip);
  //PC::debug(['fuskip'=>$fuskip,'fiskip'=>$fiskip,'lskip'=>$lskip,'cskip'=>$cskip]);
  foreach ($stack as $aframe) { #Set line at the first interesting
   // PC::debug(['aframe'=>$aframe]);
    if (!keyVal('file',$retvals)) {
      if (!in_array(keyVal('file',$aframe),$fiskip, 1)) {
        $retvals['file'] = keyVal('file',$aframe);
      }
    }
    if (!keyVal('class',$retvals)) {
      if (!in_array(keyVal('class',$aframe),$cskip, 1)) {
        $retvals['class'] = keyVal('class',$aframe);
      }
    }
    if (!keyVal('line',$retvals)) {
      if (!in_array(keyVal('line',$aframe),$lskip, 1)) {
        $retvals['line'] = keyVal('line',$aframe);
      }
    }
    if (!keyVal('function',$retvals)) {
      if (!in_array(keyVal('function',$aframe),$fuskip, 1)) {
        $retvals['function'] = keyVal('function',$aframe);
      }
    }
  }
  return $retvals;
}

$starttime = 0;
$startframe = [];

function startTime($msg=null) {
  if (ne_string($msg)) {
    $msg = ['startmsg' => $msg];
  } else {
    $msg = [];
  }
  global $starttime, $startframe;
  $starttime = time();
  $startframe = $msg + callingFrame(['function'=>__FUNCTION__]);
}
function endTime($msg=null) {
  if (ne_string($msg)) {
    $msg = ['endmsg' => $msg];
  } else {
    $msg = [];
  }
  global $starttime, $startframe;
  $interval = time() - $starttime ." seconds";
  $endframe = $msg + callingFrame(['function'=>__FUNCTION__]);
  $data = ['start'=>$startframe, 'end'=>$endframe, 'interval'=>$interval];
  pkdebugOut(print_r($data,1));
}
function callingFrame3($file = null) {
  $stack = debug_backtrace();
  $retvals = [];
  $keys = ['file', 'class', 'object', 'function', 'line', 'args'];
  $uninteresting =
      ['pkdebug','pkdebug_base','call_user_func_array', __FILE__, '', $file];
  foreach ($stack as $aframe) { #Set line at the first interesting
    foreach ($keys as $key) {
      $tst = keyVal($key, $aframe);
      if (!keyVal($key, $retvals) && $tst &&
          !in_array($tst, $uninteresting) && ($key !== 'line')) {
        $retvals[$key] = $tst;
        if (!keyVal('line', $retvals) && keyVal('line', $aframe)) {
          $retvals['line'] = keyVal('line', $aframe);
        }
      }
    }
  }
  return $retvals;
}
/**
 * Returns the (unlimited) args as a string, arg strings as strings,
 * array and obj args as var_dumps.
 * TODO: Keep static var of log files written to in current request;
 * only initialize w. date on first call PER FILE
 * @return string
 */
function pkdebug_base() {
  static $callno = 0;
  if (PkLibConfig::getSuppressPkDebug()) return null;
  $stack = debug_backtrace();
  $retvals = [];
  $keys = ['file', 'class', 'object', 'function', 'line', 'args'];
  $uninteresting = ['pkdebug','pkdebug_base','call_user_func_array', __FILE__, ''];
  foreach ($stack as $aframe) { #Set line at the first interesting
    foreach ($keys as $key) {
      $tst = keyVal($key, $aframe);
      if (!keyVal($key, $retvals) && $tst &&
          !in_array($tst, $uninteresting) && ($key !== 'line')) {
        $retvals[$key] = $tst;
        if (!keyVal('line', $retvals) && keyVal('line', $aframe)) {
          $retvals['line'] = keyVal('line', $aframe);
        }
      }
    }
  }

  //$stacksize = sizeof($stack);
  $out = '';
  //$out = "\nLOG OUT: STACKSIZE: $stacksize";
  if (!$callno) {
    $out .= "\nLOG: " . date('j-M-y; H:i:s');
  } else {
//    $out .= "\n\n";
  }
  $callno ++;
  $idx = 0;
  //while (empty($stack[$idx]['file']) || ($stack[$idx]['file'] === __FILE__) || (substr($stack[$idx+1]['function'],-3)==='Log')) {
  while (!keyVal('file', keyVal($idx, $stack)) || keyVal('file', keyVal($idx, $stack) === __FILE__) || (substr(keyVal('function', keyVal($idx + 1, $stack)), -3) === 'Log')) {
    $idx++;
  }

  while (empty($stack[$idx]['file']) ||
      ($stack[$idx]['file'] === __FILE__) ||
      (isset($stack[$idx+1]) && (substr($stack[$idx + 1]['function'], -3) === 'Log'))) {
    $idx++;
  }
  $frame = $stack[$idx];
  $frame['function'] = isset($stack[$idx + 1]['function']) ? $stack[$idx + 1]['function'] : '';
  $fline = keyVal('line', $frame);
  // Try using new way (below) - add option to display args & object later
  /*
    if (isset($stack[1])) {
    $out .= "\nF$idx: " . $frame['file'] . ": " . $frame['function'] . ': ' . $frame['line'] . ": \n  ";
    } else {
    $out .= "\n: " . $frame['file'] . ": TOP-LEVEL: " . $frame['line'] . ": \n  ";
    }
   *
   */
  /** Test $retvals - better way? */
  $tstrr = "\n";
  foreach ($retvals as $key => $value) {
    if (($key === 'object') || ($key === 'args')) continue;
    //$tstrr.= "$key: $value, ";
    $tstrr .= "$value; ";
  }
  $tstrr .= "; LINE: $fline\n";
  $out .= $tstrr;
  $lastarg = func_get_arg(func_num_args() - 1);
  $dumpobjs = true;
  if (is_bool($lastarg) && ($lastarg === false)) $dumpobjs = false;
  $msgs = func_get_args();
  foreach ($msgs as $msg) {
    $printMsg = true;
    $type = typeOf($msg);
    if (is_bool($msg)) {
      $msg = $msg ? 'TRUE' : 'FALSE';
    }
    if (is_object($msg)) {
      $printMsg = $dumpobjs;
    }
    if ($msg instanceOf Doctrine_Pager) $printMsg = false;
    if ($printMsg && (is_object($msg) || is_array($msg)))
        $msg = displayData($msg);
    //$out .= ('Type: ' . $type . ($printMsg ? ': Payload: ' . $msg : '') . "\n  ");
    $out .= ($type . ($printMsg ? ': ' . $msg : '') . "\n  ");
  }
  //$out.="END DEBUG OUT\n\n";
  return $out;
}

/**
 * Outputs the stack to the debug out.
 * @param type $depth
 */
function pkstack($depth = 10, $args = false) {
  $out = pkstack_base($depth, $args);
  pkdebugOut($out);
}

/**
 * Returns the stack as a string
 * @param type $depth
 * @return string
 */
function pkstack_base($depth = 10, $args = false) {
  //if (sfConfig::get('release_content_env') != 'dev') return;
  $stack = debug_backtrace();
  $stacksize = sizeof($stack);
  if (!$depth) {
    $depth = $stacksize;
  }
  $frame = $stack[0];
  $out = "STACKTRACE: Stack Depth: $stacksize; Trace depth: $depth\n";
  //pkdebugOut($out);
  ////pkdebugOut("Stack Depth: $stacksize; backtrace depth: $depth\n");
  $i = 0;
  foreach ($stack as $frame) {
    //$out = $frame['file'].": ".$frame['line'].": Function: ".$frame['function']." \n  ";
    if (isset($frame['file']) && ($frame['file'] == __FILE__)) {
      $i++;
      continue;
    }
    //$out .= pkvardump($frame) . "\n";
    if (!empty($frame['file'])) $out .= $frame['file'] . ': ';
    if (!empty($frame['line'])) $out .= $frame['line'] . ': ';
    if (!empty($frame['function'])) $out .= $frame['function'] . ': ';
    if (!empty($frame['class'])) $out .= $frame['class'] . ': ';
    $out .= "\n";
    if ($args) $out .= "Args:" . pkvardump($frame['args']) . "\n";
    //$out .= "Args:" . print_r($frame['args'],true) . "\n";
    if (++$i >= $depth) {
      break;
    }
  }
  return $out;
}

function pkTypeOf($var) {
//  if (sfConfig::get('release_content_env') != 'dev') return;
  if (is_object($var)) return get_class($var);
  return gettype($var);
}

if (!function_exists('typeOf')) {

  function typeOf($var) {
    return pkTypeOf($var);
  }

}

/** Returns an array of all ancestor classes of the class name or object
 *
 * @staticvar array $classhierarchy
 * @param objinstance|classname $heritable
 * @return boolean|array - false if invalid argument, else array of class hierarchy.
 */
function ancestry($heritable) { //Can be object instance or classname
  static $classhierarchy = []; //Cache the results
  if (is_object($heritable)) {
    $class = get_class($heritable);
  } else if (class_exists($heritable, 1)) {
    $class = $heritable;
  } else { #Not a class or object
    return false;
  }
  if (array_key_exists($class, $classhierarchy)) return $classhierarchy[$class];
  $parents = [];
  $parent = $class;
  while ($parent = get_parent_class($parent)) {
    $parents[] = $parent;
  }
  $classhierarchy[$class] = $parents;
  return $parents;
}

/** For debug functions that just echo to the screen --
 *  catch in a string and return.
 * @param type $runnable
 * @return type
 */
function pkcatchecho($runnable) {
  if (!is_callable($runnable)) {
    return "In pkcatchecho -- the function passed[" .
        pkvardump($runnable) . "]is not callable...";
  }
  $args = func_get_args();
  array_shift($args);
  ob_start();
  call_user_func_array($runnable, $args);
  //Var_Dump($arg);
  //print_r($arg);
  $vardump = ob_get_contents();
  //Experimenting 6/16 - replacing with ob_end_flush...
  ob_end_clean();
  //ob_end_flush();
  ini_set('xdebug.overload_var_dump', 1);
  return "<pre>$vardump</pre>";
}

/**
 * Returns a string, in the form var_dump or print_r would output.
 * @param type $arg
 * @param type $disableXdebug
 * @return type
 */
function pkvardump($arg, $disableXdebug = true, $useVarDump=true) {
  ini_set('html_errors', 0);
  ini_set('xdebug.overload_var_dump', 0);
  if ($useVarDump || (is_object($arg) && method_exists($arg, '__debuginfo' ))) {
    ob_start();
    Var_Dump($arg);
    $vardump = ob_get_contents();
    //Experimenting 6/16 - replacing with ob_end_flush...
    //NOPE! Flush also outputs to browser!
    ob_end_clean();
    //ob_end_flush();
  } else {
    $vardump = print_r($arg, true);
  }
  ini_set('xdebug.overload_var_dump', 1);
  ini_set('html_errors', 1);
  return $vardump;
}

/** Sets the path and name for the debug log IF
 * PkLibConfig::getSuppressPkDebug() is FALSE
 * @staticvar type $logpath
 * @param type $path
 * @return string
 */
function appLogPath($path = null) {
  if (PkLibConfig::getSuppressPkDebug()) {
    error_log("Debug Suppressed - No Log");
    return getNullPath();
  }
  if (isCli()) {
    $base = __DIR__;
  } else {
    $base = $_SERVER['DOCUMENT_ROOT'] . '/..';
  }
  $defaultPath = $base . '/logs/app.log';
  static $logpath = null;
  error_log("defaultPath: [$defaultPath]");
  if ($path === false) {
    $logpath = $defaultPath;
    $res = makePathToFile($logpath);
    error_log("The used log path...$logpath");
    return $logpath;
  }
  if (!$path) {
    if (!$logpath) {
      $logpath = $defaultPath;
    }
    $res = makePathToFile($logpath);
    error_log("The used log path...$logpath");
    return $logpath;
  }
  $logpath = $path;
  $res = makePathToFile($logpath);
  error_log("The used log path...$logpath");
  return $logpath;
}

function getAppLogDir() {
  $appLogPath = appLogPath();
  if ($appLogPath == getNullPath()) return $appLogPath;
  return dirname($appLogPath);
}

/** Sets whether the first call to the pkdebug function in a given request
 * should reset the log file a the start of the request, or append to it.
 * @staticvar boolean $staticReset
 * @param boolean|null $reset If given, changes the reset value and returns it.
 *   if empty, returns the current reset value.
 * @return boolean
 */
function appLogReset($reset = null) {
  return false;
  static $staticReset = true;
  if ($reset === true) {
    $staticReset = true;
  } else if ($reset === false) {
    $staticReset = false;
  }
  return $staticReset;
}

/** Request Info - URL, Type, params, etc.
* @return string - w. the req info
*/
function pkReqInfoStr(){
  $host = $_SERVER['HTTP_HOST'];
  $requestURI = $_SERVER['REQUEST_URI'];
  $method = $_SERVER['REQUEST_METHOD'];
  //In Laravel, already drained request
  /*
  $params = $_REQUEST;
  $paramStr = pkvardump($params);
  $reqInfoStr = "
Request: $host/$requestURI
Method: $method
Params: 
$paramStr
";
*/

  $reqInfoStr = "$method: $host/$requestURI\n";
  return $reqInfoStr;
}

/**
 * Outputs the reqinfo to the log
 */
function pkReqInfo($logpath=null) {
  pkdebugOut(pkReqInfoStr(),$logpath);
}

/** Outputs to the destination specified by $useDebugLog
 *
 * @staticvar boolean $first
 * @param type $str
 * @return boolean
 * @throws Exception
 */
function pkdebugOut($str, $logpath = null) {

  //PkLibConfig::$isWarning = 1;
  if ($logpath) error_log("Logpath is: [$logpath]");
  static $first = true;
  if ($logpath) $reset = false;
  else $reset = appLogReset();
  //if ($reset) {
  try {
    //$logpath = $_SERVER['DOCUMENT_ROOT'].'/../app/logs/app.log';
    //$logpath =  WP_CONTENT_DIR.'/app.log';
    //$logpath = $_SERVER['DOCUMENT_ROOT'] . '/logs/app.log';
    if (!$logpath) $logpath = appLogPath();
    if (isCli() && $first) {
      echo ("The logpath: [$logpath]\n");
    }
    if (PkLibConfig::$isWarning) {
      if (PkLibConfig::$warnLogDir) {
        $warnpath = PkLibConfig::$warnLogDir . '/' . PkLibConfig::$warnLogName;
      } else {
        $warnpath = getAppLogDir() . '/' . PkLibConfig::$warnLogName;
      }
      $fp = fopen($warnpath, 'a+');
      if (!$fp) {
        throw new Exception("Failed to open Warning Log [$warnpath] for writing");
      }
      fwrite($fp, $str);
      fflush($fp);
      fclose($fp);
    }
    if ($first && $reset) {
      //$first = false;
      $fp = fopen($logpath, 'w+');
    } else {
      $fp = fopen($logpath, 'a+');
    }
    if (!$fp) {
      throw new Exception("Failed to open DebugLog [$logpath] for writing");
    }
    if ($first) {
      $v = " \n\n";
      $h = "=================================";
      fwrite($fp, $v . $h . $v);
    }
    fwrite($fp, $str);
    fflush($fp);
    fclose($fp);
    $first = false;
  } catch (Exception $e) {
    error_log("Error Writing to Debug Log: " . $e);
    return false;
  }
  //} else {
  // error_log($str);
  //}
  return true;
}

function getHtmlTagWhitelist() {
  static $whitelist = "<address><a><abbr><acronym><area><article><aside><b><big><blockquote><br><caption><cite><code><col><del><dd><details><div><dl><dt><em><figure><figcaption><font><footer><h1><h2><h3><h4><h5><h6><header><hgroup><hr><i><img><ins><kbd><label><legend><li><map><menu><nav><p><pre><q><s><span><section><small><strike><strong><sub><summary><sup><table><tbody><td><textarea><tfoot><th><thead><title><tr><tt><u><ul><ol><p>";
  return $whitelist;
}

/** Takes a string or multi-dimentional array of text (like from a POST)
 * and recursively trims it and strips tags except from a whitelist
 * @param type $input input string or array.
 */
//function htmlclean ($arr, $usehtmlspecchars = false) {
function html_clean(&$arr, $usehtmlspecchars = false) {
  $whitelist = getHtmlTagWhitelist();
  if (!$arr) return $arr;
  if (is_string($arr) || is_numeric($arr)) {
    return strip_tags(trim($arr), $whitelist);
  }
  if (is_object($arr)) {
    $arr = get_object_vars($arr);
  }
  if (!is_array($arr)) {
    pkdebug("Bad Data Input?:", $arr);
    throw new Exception("Unexpected input to htmlclean:" . pkvardump($arr));
  }
  $retarr = array();
  foreach ($arr as $key => &$value) {
    $retarr[$key] = html_clean($value, $usehtmlspecchars);
  }
  return $retarr;
}

/**
 * Takes a file name or path, and returns the lower-cased extension (ex, 'jpg')
 * @param string $filePath: The string file path
 * @param boolean $tolower: if true, lower case
 * @return string: the LOWER CASED file extension, if any, with no ".'
 */
function getFileExtension($filePath, $tolower = true) {
  $ext = substr($filePath, strrpos($filePath, '.') + 1);
  if ($tolower) $ext = strtolower($ext);
  return $ext;
}

/**
 * Removes the protocol, domain, and parameters, just returns
 * indexed array of route segments. ex, for URL:
 * http://www.example.com/some/lengthy/path?with=get1&another=get2
 * ... returns: array('some', 'lengthy', 'path');
 * @param Boolean|String $default: If the first two segments are missing, should
 *   return ['index','index'] as default?
 * @param int $depth default = 2: How many segments to set to the default 'index' the base URL
 * we return the default value for them? Default false, otherwise probably 'index'
 * @return Array: Route Segments
 */
function getRouteSegments($default = false, $depth = 2) {
  if ($default === true) $default = 'index';
  $aseg = $_SERVER['REQUEST_URI'];
  $breakGets = explode('?', $aseg);
  $noGet = $breakGets[0];
  $anarr = explode('/', $noGet);
  array_shift($anarr);
  if (isset($anarr[0]) && ($anarr[0] === 'favicon.ico')) return [];
  if ($default) {
    for ($i = 0; $i < $depth; $i++) {
      if (isset($anarr[$i]) && !$anarr[$i] && ($anarr[$i] === 'favicon.ico')) {
        pkdebug("WTF!:!: Favicon from: aseg:", $aseg);
      }
      if (!isset($anarr[$i]) || !$anarr[$i] || ($anarr[$i] === 'favicon.ico')) {
        $anarr[$i] = 'index';
      }
    }
  }
  return $anarr;
}

/**
 * Returns true if HTTP request protocol is HTTPS; else false
 * @return boolean - true if in a request and request is HTTPS; else false
 */
function isHttps() {
  return is_array($_SERVER) && !empty($_SERVER) &&
      (
      (array_key_exists('HTTPS', $_SERVER) && (strtolower($_SERVER["HTTPS"]) === "on")) ||
      (array_key_exists('SERVER_PROTOCOL', $_SERVER) && (strtolower(substr($_SERVER["SERVER_PROTOCOL"], 0, 5)) === 'https')) ||
      (array_key_exists('HTTP_X_FORWARDED_PROTO', $_SERVER) && (strtolower($_SERVER["HTTP_X_FORWARDED_PROTO"]) === 'https'))
      );
}

/**
 *
 * @return String: The URL without subdirs, but with protocol (http://, etc)
 */
function getBaseUrl() {
  $pageURL = 'http';
  if (isHttps()) {
    $pageURL .= "s";
  }
  $pageURL .= "://";
  return $pageURL . $_SERVER["HTTP_HOST"];
}

function getUrl() {
  return getFullUrl();
}

/**
 * Returns the the full current URL. NOTE! NO FILTERING/SANITIZING!
 * @return String: full URL
 */
function getFullUrl($withgets = true) {
  $path = getRequestUri($withgets);
  return fullHost() . $path;
}

function getRequestUri($withgets = true) {
  $path = $_SERVER["REQUEST_URI"];
  if (!$withgets) {
    $reqArray = explode('?', $_SERVER['REQUEST_URI']);
    $path = $reqArray[0];
  }
  return $path;
}

/* * Another version, only handles ports & forwarding, etc.
 * NOTE: THESE URL FUNCTIONS DO NOT FILTER! Just return the raw URL.
 *
 */

function fullHost($use_forwarded_host = false) {
  $s = $_SERVER;
  $sp = strtolower($s['SERVER_PROTOCOL']);
  $protocol = substr($sp, 0, strpos($sp, '/')) . ((isHttps()) ? 's' : '');
  $port = $s['SERVER_PORT'];
  $port = ((!isHttps() && $port == '80') || (isHttps() && $port == '443')) ? '' : ':' . $port;
  $host = ($use_forwarded_host && isset($s['HTTP_X_FORWARDED_HOST'])) ? $s['HTTP_X_FORWARDED_HOST'] : (isset($s['HTTP_HOST']) ? $s['HTTP_HOST'] : null);
  $host = isset($host) ? $host : $s['SERVER_NAME'] . $port;
  return $protocol . '://' . $host;
}

/**
 * Returns all components of the page URL, without the
 * GET query parameters
 */
function getUrlNoQuery() {
  #Do we want to filter here?
  $noGetUriArr = explode('?', $_SERVER["REQUEST_URI"]);
  //var_dump("NO GET URI:", $noGetUriArr);
  $noGetUri = $noGetUriArr[0];
  $baseUrl = getBaseUrl();
  //$baseUrl = substr($baseUrl, 0, -1);
  $getUrlNoQuery = $baseUrl . $noGetUri;
  return $getUrlNoQuery;
}

/**
 * Returns the URL path components as an array. For example, for:
 * "http://www.example.com/here/there?arg=none", returns:
 * <code>array('here','there');</code>
 * For "http://www.example.com", returns <code>array('');</code>, so can safely
 * deref the result: <code>$base = getPathArray()[0];</code>
 * @param String $path: [optional]: The URL or path compoent. If null (default)
 * uses the current URL.
 * @return array: The URL path componentes, or array of empty string.
 */
function getPathArray($path = null) {
  if (!$path) {
    $path = $_SERVER['REQUEST_URI'];
  }
  $comps = parse_url($path, PHP_URL_PATH);
  if (empty($comps['path'])) {
    return array('');
  }
  $pathStr = trim($comps['path'], '/');
  if (empth($pathStr)) {
    return array('');
  }
  $pathArr = explode('/', $pathStr);
  return $pathArr;
}

/** Sets (changes or adds or unsets/clears) a get parameter to a value
 *
 *
 * @param type $getkey -- the get parameter name
 * @param type $getval -- the new get parameter value, or if NULL,
 *   clea's the get parameter
 * @param type $qstr -- can be null, in which case the current URL is
 * used and returned with the GET parameter added, or an empty string '',
 * in which case just a query string is returned, or just a query string,
 * or another URL
 */
function setGet($getkey, $getval = null, $qstr = null) {
  if ($qstr === '') {
    if ($getval !== null) {
      return http_build_query(array($getkey => $getval));
    } else {
      return '';
    }
  }
  if ($qstr === null) {
    $qstr = getUrl();
  }
  $start = substr($qstr, 0, 4);
  $starts = substr($qstr, 0, 5);
  $col = substr($qstr, 6, 1);
  $fullurl = false;
  $preqstr = '';
  $qm = false;
  $urlarr = explode('?', $qstr);
  //$returi = '';
  if (strpos($qstr, '?') === false) {# No "?" ($qm) in string
    $qm = false;
  } else {
    $qm = true;
  }
  if ((($start === 'http') || ($starts === 'https')) && ($col === '/')) { //URL
    $fullurl = true;
  }
  #Check if a "path":
  if (strpos($qstr, '/') === false) {# No path
    $ispath = false;
  } else {
    $ispath = true;
  }


#This only goes wrong if passed a query string w/o '?', like "akey=avall&bkey=bval"
#But how to distinguish that from "/path/within/url"?.
  if (empty($urlarr[0]) || $qm || $fullurl || $ispath) {
    $preqstr = array_shift($urlarr);
  }
  $quearr = array();
  if (!empty($urlarr[0])) {
    parse_str($urlarr[0], $quearr);
  }
  if ($getval === null) {
    unset($quearr[$getkey]);
  } else {
    $quearr[$getkey] = $getval;
  }
  $retquery = http_build_query($quearr);
  $returl = $preqstr . '?' . $retquery;
  pkdebug("qstr: [$qstr]; returl: [$returl], querr:", $quearr, 'urlarr:', $urlarr);
  return $returl;
}

/** Sets (changes or adds or unsets/clears) get parameters to values. Only works
 * with/returns the query string, not the whole URL.
 *
 * @param array $paramArr -- assoc arr of GET [$key => $val]. If $val = '', sets
 * &key=&nextkey=nextval - but if $val === NULL, clears the
 * @param string|null $qstr -- if string, assumed a query string and paramArr added;
  if '', returned str is just from the $paramArr; if NULL, the current REQUEST QUERY
  is used, and $paramArr added or replaced into it.
 *

  @return string - query string
 */
function setGets(array $paramArr, $qstr = null) {
  if ($qstr === null) {
    $requri = $_SERVER['REQUEST_URI'];
    $uriArr = explode('?', $requri);
    $qstr = empty($uriArr[1]) ? '' : $uriArr[1];
  }
  if (!is_string($qstr)) {
    pkdebug("Bad parameter qstr:", $qstr);
    return '';
  }

  $getArr = [];
  parse_str($qstr, $getArr);
  foreach ($paramArr as $param => $val) {
    if ($val === null) {
      unset($getArr[$param]);
    } else {
      $getArr[$param] = $val;
    }
  }
  return http_build_query($getArr);
}

/**
 * Creates a select box with the input
 * @param $name - String - The HTML Control Name. Makes class from 'class-$name'
 * #@param $label - String - The label on the control
 * #@param $key_str - The key of the select option array element
 * #@param $val_str - The key for the array element to display in the option
 * @param $arr - Array - The array of key/value pairs
 * @param $selected - String or Null - if present, the selected value
 * @param $none - String or Null - if present, the label to show for a new
 *   entry (value 0), or if null, only allows pre-existing options
 * @return String -- The HTML Select Box
 * */
function makePicker($name, $key, $val, $arr, $selected = null, $none = null) {
#function makePicker($name, $arr, $selected=null, $none=null) {
  $select = "<select name='$name' class='$name-sel'>\n";
  if ($none) $select .= "\n  <option value=''><b>$none</b></option>\n";
  foreach ($arr as $row) {
    $selstr = '';
    if ($selected == $row[$key]) $selstr = " selected='selected' ";
    $option = "\n  <option value='" . $row[$key] . "' $selstr>" . $row[$val] . "</option>\n";
    $select .= $option;
  }
  $select .= "\n</select>";
  return $select;
}

/** No guarantee, but approximate heuristic to determine if an array is
 * associative or integer indexed.
 * NOTE: Will return FALSE if array is empty, and TRUE if array is
 * indexed but not sequential.
 * @param type $array
 * @return type
 */
function is_array_assoc($array) {
  if (!is_array($array) || !sizeOf($array)) {
    return false;
  }
  return ($array !== array_values($array));
}

function is_arrayish_assoc($val) {
  $arrCopy = getAsArray($val);
  if (!$arrCopy || !is_array($arrCopy) || !count($arrCopy)) return false;
  if (is_array($arrCopy)) return is_array_assoc($arrCopy);
  return false;
}

function is_arrayish_indexed($val) {
  $arrCopy = getAsArray($val);
  if (!$arrCopy || !is_array($arrCopy) || !count($arrCopy)) return false;
  if (is_array($arrCopy)) return is_array_indexed($arrCopy);
  return false;
}

/** Tries to convert PHP Objects that implement ArrayAccess into PHP arrays.
 * Imperfect stub so far, for PHP ArrayObject, & Laravel Collection
 * @param mixed $val - the value to convert
 * @return array|false|null: Array if we manage - null if it could be done
 * but we didn't, or false if it doesn't implement ArrayAccess
 */
function getAsArray($val) {
  if (is_array($val)) return $val;
  if (!is_arrayish($val)) return false;
  #Now we have something that implements ArrayAccess:
  if ($val instanceOf ArrayObject) {
    return $val->getArrayCopy();
  }
  if (method_exists($val, 'toArray')) return $val->toArray();
  if (is_iterable($val)) {
    $ret = [];
    foreach ($val as $key=>$data) {
        $ret[$key] = $data;
    }
    return $ret;
  }
  return null;
}

/** Checks that $array is an indexed array. If $sequential = true (default),
 * also verifies the integer indices are consecutive starting from 0
 * @param array $array
 * @param boolean $sequential - if true, indices must be sequential integers.
 *   if false, indices may be non-sequential, but must be integer or integer
 *   equivalents. If false, returns the array with integerish keys converted
 *   to integers. Like, $array['1'=>'aval', 3=>'bval'] will return:
 *   [1=>'aval', 3=>'bval'];
 * @return boolean|array - is the array consecutively integer indexed array?
 *   If $consecutive == false, returns the array with integerish keys converted
 *   to integers
 */
function is_array_idx($array, $sequential = true) {
  return is_array_indexed($array,$sequential);
}
function is_array_indexed($array, $sequential = true) {
  if (!is_array($array) || !sizeOf($array)) {
    return false;
  }
  if ($sequential) return ($array === array_values($array));
  $keys = array_keys($array);
  $retarr = [];
  foreach ($keys as $key) {
    if (($intkey = to_int($key)) === false) return false;
    $retarr[$intkey] = $array[$key];
  }
  return $retarr;
}

/**
 * Determines if the value can be output as a string.
 * @param type $value
 * @param boolean $nullorfalse - are null and false considered stringish? Default, true
 * @return boolean: Can the value be output as a string?
 */
function is_stringish($value, $nullorfalse = true) {
  if (is_object($value) && method_exists($value, '__toString')) return true;
  if (!$nullorfalse && (($value === false) || ($value === null))) {
    return false;
  }
  if (is_null($value)) return true;
  return is_scalar($value);
}

function is_scalarish($value, $nullorfalse = true) {
  return is_stringish($value, $nullorfalse);
}

/**
 * Takes any number of arguments as scalars or arrays, or nested arrays, and
 * returns a 1 dimentional indexed array of the values
 * $args: any number of arguments to flatten into an array
 * @return array: 1 dimensional index array of values
 */
if (function_exists('pk_array_flatten')) {
  error_log("pk_Array_Flatten already defined outside of pklib - might have different behavior!");
} else {

  function pk_array_flatten(/* $args */) {
    $args = func_get_args();
    $return = array();
    array_walk_recursive($args, function($a) use (&$return) {
      $return[] = $a;
    });
    return $return;
  }

}

/** Like 'is_scalar', but returns true for null. Anything usable as an array key */
function is_simple($val) {
  return is_scalar($val) || is_null($val);
}

/**
 * Like array_merge(), except instead of a list of arrays to merge, takes an
 * array of arrays to merge, and returns the merged array.
 * @param Array $arrs: Array of arrays
 * @return Array: Merged array of arrays
 */
function array_merge_array($arrs = []) {
  if (!is_array($arrs) || !count($arrs)) {
    return [];
  }
  if (is_array($arrs) && (count($arrs) ===1)){
    return $arrs[0];
  }
  $res = [];
  foreach ($arrs as $arr) {
    if (!is_array($arr)) {
      $res[] = $arr;
    } else {
      $res = array_merge($res, $arr);
    }
  }
  return $res;
}

/**
 * Converts a compatable argument to an actual integer type ('7' -> 7), or
 * boolean false ('010' -> false, '' -> false).
 * Totally unnecessary function but more convenient than remembering constant
 * @param scalar $arg - something to convert to an int
 * @param mixed $default - the default value if can't be converted to an int. Default: false
 * @return: integer if integer, also 0, but boolean false if not an integer
 * Examples:
 * to_int($fileObj) === FALSE;
 * to_int('7') === 7;
 * to_int('07') === FALSE;
 * to_int(['a','b']) === FALSE;
 * to_int(NULL) === FALSE;
 * to_int('0') === 0;
 * SADLY, however:
 *
 * to_int(7.45) === FALSE; - but can use intval
 * to_int('9.7') === FALSE; - but can use intval
 */
function to_int($arg, $default = false) {
  //if (is_int($arg)) return $arg; #Optimization attempt
  $arg = filter_var($arg, FILTER_VALIDATE_INT);
  if ($arg === false) $arg = $default;
  return $arg;
}

/** Implements filter_var on array variables
 *
 * @param array or string $input: The input to be filtered. Does not filter keys.
 * @param int $flag: See php filter_var docs
 * @param mixed $options: See php filter_var docs
 * @return String or Array: The filtered result.
 * @throws Exception
 */
function filter_var_recursive($input, $flag = FILTER_SANITIZE_FULL_SPECIAL_CHARS, $options = null) {
  if (is_array($input)) {
    $ret = array();
    foreach ($input as $key => $val) {
      $ret[$key] = filter_var_recursive($val, $flag, $options);
    }
    return $ret;
  } else if (is_scalar($input)) {
    return filter_var($input, $flag, $options);
  } else {
    throw new Exception("Invalid input: " . print_r($input, true));
  }
}

/**
 * Cleans a string for output. Basically, wrap all output in this function, then
 * can change method used.
 * @param string $str: The string to clean
 * @return string: The clean string.
 */
function cln_str($str, $filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS) {
  return filter_var($str, $filter);
}

#FOR ALL CUSTOM IMPORT FILTERS !!! SPECIAL HANDLING FOR INPUT ARRAYS!
#IF INPUT IS AN ARRAY, MUST USE FILTER_REQUIRE_ARRAY AS OPTION!!!

/**
 * Filters a POST input, according to the parameters
 */
function filter_post($key, $filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS, $options = null) {
  return filter_input(INPUT_POST, $key, $filter, $options);
}

/**
 * Filters a GET input, according to the parameters
 */
function filter_get($key, $filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS, $options = null) {
  return filter_input(INPUT_GET, $key, $filter, $options);
}

######### Try filtering input arrays

function filter_post_array($filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS) {
  $post = filter_input_array(INPUT_POST, $filter);
  return $post;
}

function filter_get_array($filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS) {
  $get = filter_input_array(INPUT_GET, $filter);
  return $get;
}

/** Performs the equivalent of "filter_input($type = INPUT_REQUEST,...) if that
 * existed.
 * @param string $key: The input key for GET/POST/COOKIE
 * @param integer $filter: The filter type. Defaults to FILTER_SANITIZE_FULL_SPECIAL_CHARS
 * @param mixed $options: Optional additional options for "filter_input", if any.
 * @return: The sanitized result, or null.
 */
function filter_request($var, $filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS, $options = null) {
  $res = filter_input(INPUT_GET, $var, $filter, $options);
  if ($res === null) $res = filter_input(INPUT_POST, $var, $filter, $options);
  if ($res === null) $res = filter_input(INPUT_COOKIE, $var, $filter, $options);
  return $res;
}

function filter_server_url($var, $default = '/', $filter = FILTER_VALIDATE_URL, $options = null) {
  $ret = filter_input(INPUT_SERVER, $var, $filter, $options);
  if (!$ret) $ret = $default;
  return $ret;
}

function filter_server($var, $filter = FILTER_SANITIZE_FULL_SPECIAL_CHARS, $options = null) {
  return filter_input(INPUT_SERVER, $var, $filter, $options);
}

function userIP() {
  return filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);
}

function filter_url($url) {
  if (filter_var($url, FILTER_VALIDATE_URL)) {
    return $url;
  }
  return '/';
}

function isUrl($url) {
  return filter_var($url, FILTER_VALIDATE_URL);
}


/**
 * Returns just the base string in a path - no components
 * no extensions
 * @param string $str - a path - url or file or string
 * @return string - just the base. Ex:
 * "http://something/or/other.html.exe" => "other"
 * "nothing" => "nothing"
 */
function noextbase ($str) {
 $str = basename($str);
 $off = strpos($str,'.');
 if ($off === false) return $str;
 return substr($str, 0, $off);
}

function cln_arr_val(Array $arr, $key, $filter = FILTER_SANITIZE_STRING) {
  if (!array_key_exists($key, $arr)) {
    return null;
  }
  return cln_str($arr[$key], $filter);
}

/**
 * Encodes a string with HTML special characters -- including single and double
 * quotes - mainly for use to include an arbitrary string (including HTML
 * input elements for form templates) in an HTML data-XXX attribute.
 * IDEMPOTENT!! (EXCEPT for the $enclose, of course)
 * @param String $str: The input string, which may contain special HTML chars.
 * $param string|null: Enclose the output by these characters? Like: "'".
 * @return String: The HTML Encoded string
 */
function html_encode($str, $enclose = null) {
  return $enclose . filter_var($str, FILTER_SANITIZE_FULL_SPECIAL_CHARS, ENT_QUOTES) . $enclose;
}

function html_encode_file($path, $enclose = null) {
  if (!is_readable($path)) {
    throw new Exception("Couldn't read [$path]");
  }
  return html_encode(file_get_contents($path), $enclose);
}

/** Takes a PHP array, encodes it to JSON, and then re-encodes it to HTML
 * string for inclusion in html attribute values, to be reconstituted
 * in a script as a JS Object
 * @param array $arr: data to encode
 */
function html_encode_array(Array $arr = [], $enclose = null) {
  $json = json_encode($arr);
  return html_encode($json, $enclose);
}

/**
 * Decodes a string previously encoded for HTML special characters --
 * including single and double quotes - mainly for use decoding an HTML data-XXX attribute.
 * @param String $str: The input string, which may contain special HTML chars.
 * @return String: The HTML Encoded string
 */
function html_decode($str) {
  htmlspecialchars_decode($str, ENT_QUOTES);
}

/**
 * Insert the value into the given array (or new array) at the appropriate
 * depth/key sequence specified by the array of keys. Ex:
 * var_dump(insert_into_array(array('car','ford','mustang','engine'), "351 Cleavland"));
 * Outputs:
 * array (size=1)
  'car' =>
  array (size=1)
  'ford' =>
  array (size=1)
  'mustang' =>
  array (size=1)
  'engine' => string '351 Cleavland' (length=13)
 * @param array $keys: Sequence/depth of keys
 * @param Mixed $value: Whatever value to assign to the location
 * @param array|NULL $arr -- Optional array to add to or create
 * @param boolean $unset (optional): If value is null, default is to create
 * the entry with a null value. If $unset==true, unset the key if it exists.
 * EVEN IF TRUE, however, WILL CREATE REST OF THE PATH, UP TO ENTRY
 * @return Array: Array with value set at appropriate vector. If called with
 * $retar = &insert_into_array(.... $arr);, $retar will be a reference to $arr
 */
function &insert_into_arrayBAD($keys, $value, & $arr = null, $unset = false) {
  if (!is_array($keys)) {
    $keys = [$keys];
  }
  #If there is a value, don't unset, regardless of unset option
  if ($value) {
    $unset = false;
  }
  if ($arr === null) {
    $arr = [];
  }
  $x = & $arr;
  $y = &$x;
  foreach ($keys as $keyval) {
    $y = &$x;
    if (is_array($x)) {
      $x = & $x[$keyval];
    } else if (is_object($x)) {
      $x = &$x->$keyval;
    }
  }
  if ($unset) {
    array_pop($y);
  } else {
    $x = $value;
  }
  return $arr;
}

/**
 * Insert the value into the given array (or new array) at the appropriate
 * depth/key sequence specified by the array of keys. Ex:
 * var_dump(insert_into_array(array('car','ford','mustang','engine'), "351 Cleavland"));
 * Outputs:
 * array (size=1)
  'car' =>
  array (size=1)
  'ford' =>
  array (size=1)
  'mustang' =>
  array (size=1)
  'engine' => string '351 Cleavland' (length=13)
 *
 * Can also just RETURN the value at the path without changing the array
 * @param array $keys: Sequence/depth of keys
 * @param Mixed $value: Whatever value to assign to the location
 * @param array|NULL $arr -- Optional array to add to or create
 * @param boolean $unset (optional): If value is null, default is to create
 * @param boolean $fetch (optional): If true, just try to return value
 * @param boolean $append (optional): If true, append the value to the array
      of the keys. If no value for keys, make array & append. If $keys value is
      scalar, throw exception.
 *
 * the entry with a null value. If $unset==true, unset the key if it exists.
 * EVEN IF TRUE, however, WILL CREATE REST OF THE PATH, UP TO ENTRY
 * @return Array: Array with value set at appropriate vector. If called with
 * $retar = &insert_into_array(.... $arr);, $retar will be a reference to $arr
 */
function &insert_into_array($keys,$value,&$arr=null,$unset=false,$fetch=false,$append=false) {
  if (!is_array($keys)) {
    $keys = [$keys];
  }
  #If there is a value, don't unset, regardless of unset option
  if ($arr === null) {
    $arr = [];
  }
  $x = & $arr;
  $y = &$x;
  foreach ($keys as $keyval) {
    $y = &$x;
    if (is_object($x)) {
      $x = &$x->$keyval;
    } else {
      $x = & $x[$keyval];
    }
  }
  if ($fetch) {
    return $x;
  }
  if ($unset) {
    array_pop($y);
  } else if ($append) {
    if (is_scalar($x)) {
      throw new Exception("In append, keyVal: ".
         print_r($keys,1). " is scalar: ".print_r($x,1));
    } else if (!$x) {
      $x = [];
    }
    array_push($x, $value);
  } else {
    $x = $value;
  }
  //$x = $value;
  return $arr;
}
/** Maybe better for fetching. So obviously $arr is required*/
/* @param keys, $arr as avove
 * @return: REFERENCE to the value, so like it it's an array it can
 * be modifiled.
 */
function &fetch_from_array($keys,Array &$arr) {
  if (!is_array($keys)) {
    $keys = [$keys];
  }
  $x = & $arr;
  $y = &$x;
  foreach ($keys as $keyval) {
    $y = &$x;
    if (is_object($x)) {
      $x = &$x->$keyval;
    } else {
      $x = & $x[$keyval];
    }
  }
  return $x;
}

/**
 * Intention here is to unset / pop value at keys level of array
 * OH -- BUT THIS IS SO NOT RIGHT....
 * @param array $keys: Sequence of keys to the array value to remove
 * @param array $arr: The array to pop/unset from...
 * @return
 */
/* Don't think I need this - fixed the above - but wait & see...
  function unset_array_keys($keys, &$arr) {
  if (!is_array($keys)) {
  $keys = [$keys];
  }
  $x = & $arr;
  $y = &$x;
  foreach ($keys as $keyval) {
  $y = &$x;
  $x = & $x[$keyval];
  }
  array_pop($y);
  }
 *
 */

/**
 * Examines an array and checks if a key sequence exists
 * @param array $keys: Array of key sequence, like
 * array('car','ford','mustang','engine')
 * @param array $arr: The array to examine if key sequence is set, for ex:
 * $arr['car']['ford']['mustang']['engine'] = "351 Cleavland";
 * @return boolean: True if array key chain is set, else false
 */
//function array_key_exists_depth(Array $keys, Array $arr) {
function arrayish_keys_exist($keys, $arr = null) {
  if (!$arr) return false;
  if (!is_arrayish($arr)) return false;
  foreach ($keys as $keyval) {
    if (!is_arrayish($arr) || !arrayish_key_exists($keyval, $arr)) {
      return false;
    }
    $arr = $arr[$keyval];
  }
  return true;
}

/** Returns indexed array of two - the key & value, by default first,
 *
 * @param array $arr
 * @param int $idx - which key/val pair do you want? Default 0
 */
function keyvalpair($arr, $idx=0){
  $keys = array_keys($arr);
  $key = $keys[$idx];
  return [$key, $arr[$key]];
}

/** Like array_values, but for anything iterable by foreach.
 *
 * @param iterable $arg
 * @return array of values, or null if not iterable
 */
function iterable_values($arg) {
    if (!is_iterable($arg)) {
        return null;
    }
    $ret = [];
    foreach ($arg as $val) {
        $ret[]=$val;
    }
    return $ret;
}

/** Both ArrayObject & Laravel Collections implement ArrayAccess - but they
 * they DON'T have the same array clone:
 *     ElloquentCollection->toArray()
 *     ArrayObject->getArrayCopy()
 * @param ArrayAccess $arg
 * @return type
 */
function is_arrayish($arg) {
  return (is_array($arg) || ($arg instanceOf ArrayAccess) ||
    ($arg instanceOf Generator) || (($arg instanceOf ArrayAccess) &&
        ($arg instanceOf Countable) && ($arg instanceOf IteratorAggregate)));
}

/** Similar to above (array_keys_exist()), only returns the value at the
 * location
 * @param array|scalar $keys. What to do if null? Return it all...
 * @param array $arr
 * @return mixed: The value at the location
 */
function arrayish_keys_value($keys, $arr = null) {
  if (is_scalar($keys)) {
    $keys = [$keys];
  }
  if (!is_array($keys)) {
    return $arr;
  }
  if (!$arr) return false;
  if (!is_arrayish($arr)) return false;
  foreach ($keys as $keyval) {
    if (!is_arrayish($arr) || !arrayish_key_exists($keyval, $arr)) {
      return false;
    }
    $arr = $arr[$keyval];
  }
  return $arr;
}

/**
 * Like the system "array_key_exists", except for ArrayAccess implementation
 * as well.
 * @param int|str $keyval
 * @param array|ArrayAccess $arr
 * @return boolean: True if key exists, else false
 */
function arrayish_key_exists($keyval, $arr) {
  if (is_array($arr)) return array_key_exists($keyval, $arr);
  if ($arr instanceOf ArrayAccess) return $arr->offsetExists($keyval);
  throw new Exception("Argument (2) to arrayish_key_exists is not arrayable");
}

/**
 * Returns Max int key index for array with mixed assoc/idx keys (or Max + 1
 * if $next == true).
 * @param array $arr: The array to find max int key of
 * @param boolean $next: If true, returns max+1; or 0 if no int keys.
 * @return null|int: Max int key index in array with mixed assoc/idx keys
 */
function max_idx($arr, $next = false) {
  if (!$arr || !sizeOf($arr)) {
    if ($next) return 0;
    return null;
  }

  $ret = null;
  $keys = arrayish_keys($arr);
  foreach ($keys as $key) {
    if (is_int($key)) {
      if (($ret === null) || ($key > $ret)) {
        $ret = $key;
      }
    }
  }
  if ($next) {
    if ($ret === null) return 0;
    return $ret + 1;
  }
  return $ret;
}

// Finally implemented as native function in PHP >= 7.1
if (!function_exists('is_iterable')) {

  function is_iterable($var) {
    return (is_array($var) || $var instanceof Traversable);
  }

}

/**
 * Basically, "array_keys()" for objects that implement Iterator
 * @param array $arr: The array or array-like object
 * @return Array: The keys of the array or array like object
 */
function arrayish_keys($arr) {
  if (!sizeOf($arr)) {
    return array();
  }
  $keys = array();
  foreach ($arr as $key => $val) {
    $keys[] = $key;
  }
  return $keys;
}

/**
 * Returns a list of all declared classes that are descendants/instances Of
 * the named class
 * @TODO: Should really return a hierarchy instead of a flat array..
 *
 * @param string $class: The class to check for descendants of...
 * @param Boolean $alsome: Also Me? That is, return this class itself?
 * @return boolean|array: all declared classes that are descendants of the
 * given class
 */
function get_descendants($class, $alsome = 0) {
  if (!class_exists($class)) {
    return false;
  }
  static $descendantsStat = [];
  if ($alsome) $alsomeint = 1;
  else $alsomeint = 0;
  if (array_key_exists($class, $descendantsStat)) {
    if (array_key_exists($alsomeint, $descendantsStat[$class])) {
      return $descendantsStat[$class][$alsomeint];
    } else {
      $descendantsStat[$class][$alsomeint] = null;
    }
  } else {
    $descendantsStat[$class] = [];
  }
  $classes = get_declared_classes();
  $descendants = array();
  if ($alsome) {
    $descendants[] = $class;
  }
  foreach ($classes as $aclass) {
    //if ($aclass instanceOf $class) {
    if (is_subclass_of($aclass, $class)) {
      $descendants[] = $aclass;
    }
  }
  $descendantsStat[$class][$alsomeint] = $descendants;
  return $descendants;
}

function submitted($onlyPost = false) {
  //if ((filter_server('REQUEST_METHOD') == 'POST') || (sizeof($_GET) && !$onlyPost)) {
  if (($_SERVER['REQUEST_METHOD'] == 'POST') || (sizeof($_GET) && !$onlyPost)) {
    return true;
  } else {
    return false;
  }
}

#Form CSRF NONCE functions -- two types - 2 pure low-level PHP Nonce generating
#and checking, and 2 form interactive function which generate the hidden control
#and redirect if NONCE not satisfied.

/** Just make a NONCE
 * @return String: Hex string for inclusion in hidden NONCE input, and for
 * setting session variable.
 */
function makeNonce($len = 128, $prefix = '') {
  $bytes = openssl_random_pseudo_bytes($len);
  $hex = bin2hex($bytes);
  return $prefix . $hex;
}

/**
 * Calls "makeNonce()" to generate the NONCE, create a hidden input string, and
 * set the SESSION variable with the generated NONCE value
 * @return String: The hidden NONCE input control for direct echoing in a form
 */
function formCreateNonce() {
  $nonce = makeNonce();
  $control = "<input type='hidden' name='form_nonce' value='$nonce' />\n";
  $_SESSION['form_nonce'] = $nonce;
  return $control;
}

/**
 * Checks the submitted nonce with the stored session nonce.
 * @return boolean: Whether the submitted nonce matches the SESSION (expected)
 * NONCE
 */
function checkNonce() {
  $form_nonce = $_SESSION['form_nonce'];
  $request_nonce = filter_request('form_nonce');
  if ($request_nonce == $form_nonce) {
    return true;
  } else {
    pkdebug("FORM NONCE:'$form_nonce'; REQUEST_NONCE: '$request_nonce'");
    return false;
  }
}

/**
 * Processes the form submission and redirects if NONCE requirement not met.
 * If not a post, returns successfully. If POSTed NONCE value exists but
 * doesn't match SESSSION NONCE, redirects to default NONCE mismatch page.
 * If POSTed NONCE doesn't exist, either returns "FALSE", or redirects to
 * NONCE mismatch page, depending on options. If POSTed NONCE exists and matches
 * SESSION NONCE, returns TRUE.
 */
function formProcessNonce($nonceFailPage = 'nonce_fail', $ignoreMissing = true) {
  if (!submitted(true)) {
    return true;
  }

  $noncePass = checkNonce();
  $baseUrl = getBaseUrl();
  if (!$noncePass) {
    die("<h2>NONCE failed; see log</h2>");
    header("Location: $baseUrl/$nonceFailPage");
  }
  return true;
}

/**
 * Takes a string with white-spaces, punctuation, weird characters, etc,
 * and returns a nice string w/o spaces, UC chars, etc, for URL or
 * CSS Classname
 * @param string $text: The messy text.
 * @return string: Clean String suitable for a URL or CSS Class or ID name
 */
function slugify($text) {
  // replace non letter or digits by -
  $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
  $text = trim($text, '-');
  // transliterate
  $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
  $text = mb_strtolower($text);
  // remove unwanted characters
  $text = preg_replace('~[^-\w]+~', '', $text);
  //if (empty($text)) {
  #Change from 'empty' -- allow for 0
  if ($text === '') {
    return 'n-a';
  }
  return $text;
}

/**
 * NOT FOR PASSWORDS - just to generate a short string probably unique in a
 * page for HTML 'id'-s
 * @param int $length: Length of string to return
 * @return string: Short, randomish lower case alpha string
 */
function generateShortRandomString($length = 5) {
  return substr(str_shuffle("abcdefghijklmnopqrstuvwxyz"), 0, $length);
}

/** Makes a urlencoded parameter string from an associative input array.
 *
 * @param array $args: Associative array of get keys/values
 * @return String: URL encoded string of gets
 */
#duh -- just    http_build_query($args);
/*
  function makeGets($args = null) {
  if (empty($args) || !$args || !is_array($args)) {
  return '';
  }
  $getstr = '';
  foreach ($args as $key => $value) {
  $getstr.=urlencode($key) . '=' . urlencode($value) . '&';
  }
  #Trim terminal '&'...
  return substr($getstr, 0, -1);
  }
 *
 */

/**
 * Takes two values - either of which could be scalar or array, and returns
 * an array of them combined. Ex:
 * <tt>combineAsArrays(['hello', 'goodbye'], 'yesterday');</tt> returns:
 * <tt>['hello', 'goodbye', 'yesterday']</tt>
 * @param mixed $arg1
 * @param mixed $arg2
 * @return array: Values combined as array
 */
function combineAsArrays($arg1, $arg2) {
  $arg1 = toArray($arg1);
  if ($arg2 === null) return $arg1;
  $arg2 = toArray($arg2);
  return array_merge($arg1, $arg2);
}

/**
 * Examines $arg - if not an array (eg, scaler, null or object), puts it into an array.
 * If "$ish" is true, considers an "arrayish" arg as an array.
 * @param mixed $arg
 * @param boolean $ish - consider ArrayObjects already arrays?
 */
function toArray($arg, $ish = false) {
  if (is_array($arg)) return $arg;
  if ($ish && is_arrayish($arg)) return $arg;
  return [$arg];
}

/** Takes a string and a terminator; if the terminator is present at the
 * end, returns the string with the terminator removed.
 * @param string $str
 * @param string $test
 * @return string|false The input string stripped of terminal/ending $test
 * else boolean 'FALSE' if doesn't end w. $test
 */
function removeEndStr($str, $test = '') {
  if (!$test) return $str;
  if (!(substr($str, -strlen($test)) == $test)) {
    return false;
  }
  return substr($str, 0, strlen($str) - strlen($test));
}

/** Does what perhaps the above function "removeEndStr" should do - that is,
 * returns the original string if it doesn't end with '$test' str.
 */
function removeEndStrIf($str, $test) {
  $res = removeEndStr($str, $test);
  if ($res === false) return $str;
  return $res;
}

/** Returns $str with initial $test string removed if $str starts with $test,
 * else false.
 * @param string $str - the string to examin
 * @param string $test - the string to remove if $str starts with $test
 * @return string|false - The $str with initial $test removed if it starts with $test,
 * else false if it doesn't start with $test.
 */
function removeStartStr($str, $test = '') {
  if (!$test) return $str;
  if (substr($str, 0, strlen($test)) != $test) return false;
  return substr($str, strlen($test));
}

/** Returns $str with prepended $test string removed if str starts w. test;
 * else returns just the original $str.
 * @param string $str - the string to examine and remove leading string from
 * @param string $test - the string to remove from the given string, if it starts
 * with it.
 * @ return string - the initial argument $str string, with $test removed if it exists
 */
function removeStartStrIf($str, $test = '') {
  $res = removeStartStr($str, $test);
  if ($res !== false) return $res;
  return $str;
}

/**
 * Does the haystack start with the needle? Case Sensitive?
 * @param string $haystack
 * @param string $needle
 * @param boolean $case - true for case sensitive (default), false for ci
 * @param boolean $longer false - must the haystack be longer than the needle?
 * @return boolean - does haystack start with needle?
 */
function startsWith($haystack, $needle, $case = true, $longer = false) {
  if ($case) {
    $res = (strcmp(substr($haystack, 0, strlen($needle)), $needle) === 0);
  } else {
    $res = (strcasecmp(substr($haystack, 0, strlen($needle)), $needle) === 0);
  }
  if ($longer) {
    $res = $res && (strlen($haystack) > strlen($needle));
  }
  return $res;
}

/**
 * For Apache re-writes through an index.php (or other) file - returns the
 * base URL where the reroute file is - without 'index.php' (default) or
 * whatever the reroute file name is - appended.
 * @param string $rerouteBase - default: 'index.php'.
 * The file name Apache calls are routed through
 * @return string - The URL without the appended filename.
 */
function getRootWithoutIndex($rerouteBase = 'index.php') {
  $root = pktrailingslashit(removeEndStrIf($_SERVER['PHP_SELF'], $rerouteBase));
  return getBaseUrl() . $root;
}

/**
 * Strips off 'www.', if any. Doesn't deal with subdomains - see notes if
 * this is important.
 * @return string - domain name (if simple);
 */
function getDomain() {
  $serverName = $_SERVER['SERVER_NAME'];
  $domain = removeStartStrIf($serverName, 'www.');
  return $domain;
}

/** This uses tricks like DNS lookup of IP Address - so can handle more
 * complex domains, but also more error prone. Don't use unless necessary.
 * @return string - domain name
 */
function getDomainHard($url = null) {
  if (!$url) { #Use our URL
    $url = getBaseUrl();
  }
  $dots = substr_count($url, '.');
  $domain = '';
  for ($end_pieces = $dots; $end_pieces > 0; $end_pieces--) {
    $test_domain = end(explode('.', $url, $end_pieces));
    if (dns_check_record($test_domain, 'A')) {
      $domain = $test_domain;
      break;
    }
  }
  return $domain;
}

/**
 * Returns a POST array without dots in keys converted to '_' BUT NOTE!
 * DOES NOT WORK WITH 'enctype' => 'multipart/form-data',
 * @return null|array: POSTed data without '.' converted to '_' in keys.
 */
function getRealPostArray() {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {#Nothing to do
    return null;
  }
  $neverANamePart = '~#~';
  $postdata = file_get_contents("php://input");
  $post = [];
  $rebuiltpairs = [];
  $postraws = explode('&', $postdata);
  foreach ($postraws as $postraw) { #Each is a string like: 'xxxx=yyyy'
    $keyvalpair = explode('=', $postraw);
    if (empty($keyvalpair[1])) {
      $keyvalpair[1] = '';
    }
    $pos = strpos($keyvalpair[0], '%5B');
    if ($pos !== false) {
      $str1 = substr($keyvalpair[0], 0, $pos);
      $str2 = substr($keyvalpair[0], $pos);
      $str1 = str_replace('.', $neverANamePart, $str1);
      $keyvalpair[0] = $str1 . $str2;
    } else {
      $keyvalpair[0] = str_replace('.', $neverANamePart, $keyvalpair[0]);
    }
    $rebuiltpair = implode('=', $keyvalpair);
    $rebuiltpairs[] = $rebuiltpair;
  }
  $rebuiltpostdata = implode('&', $rebuiltpairs);
  parse_str($rebuiltpostdata, $post);
  $fixedpost = [];
  foreach ($post as $key => $val) {
    $fixedpost[str_replace($neverANamePart, '.', $key)] = $val;
  }
  return $fixedpost;
}

/**
 * Attempt to implement var_export output for arrays with square brackets
 * instead of "array(...)"
 *
 * JUST EXPERIMENTAL - USE ONLY FOR FORM SUGGEST,
 * @param mixed $var - what to output
 */
function var_export_square($var, $depth = 0, $indent = '  ') {
  $totalindent = str_repeat($indent, $depth);
  if (!is_array($var)) {
    if (is_object($var)) {
      return var_export($var, 1) . ",";
    }
    switch (gettype($var)) {
      case "string":
        return '"' . addcslashes($var, "\\\$\"\r\n\t\v\f") . '", ';

      case "boolean":
        return ($var ? "TRUE" : "FALSE") . ", ";

      case "double":
      case "integer":
        return "$var" . ", ";
      case "NULL":
        return "NULL, ";
      default:
        return "/* WEIRD */ " . var_export($var, 1) . ", ";
    }
    //return $totalindent.var_export($var, 1)."\n";
  }
  //$output = "[\n" . $totalindent;
  $output = "[\n" . $totalindent;
  //$output = $totalindent."[\n";
  $depth++;
  foreach ($var as $key => $value) {
    if (is_string($key)) {
      $key = "'$key'";
    } else if (is_null($key)) {
      $key = "NULL";
    } else if (is_bool($key)) {
      $key = ($$key ? "TRUE" : "FALSE");
    } else if (is_numeric($key)) {

    }
    $output .= ("$key => " . var_export_square($value, $depth, $indent) . " ");
  }
  if ($depth > 1) {
    $output .= "],\n";
  } else {
    $output .= "\n];\n";
  }
  return $output;
}

/**
 * Displays the data in hopefully readable format.
 * @param mixed $data
 * @param int $level
 * @param int $indent
 * @return string - readable description of the data
 */
function displayData($data, $level = 0, $indent = 2) {
  $offset = str_repeat(' ', $level * $indent);
  if (is_null($data)) return "NULL";
  $type = typeOf($data);
  if (is_bool($data)) if ($data) return 'TRUE';
    else return 'FALSE';
  if (is_scalar($data)) return "$type:{{$data}}";
  if (is_array($data)) {
    if (!$data) return "[]";
    $retStr = "[\n";
    $level ++;
    $newoffset = str_repeat(' ', $level * $indent);
    foreach ($data as $key => $val) {
      $retStr .= "{$newoffset}$key=>" . displayData($val, $level, $indent) . "\n";
    }
    return "$retStr$offset]";
  }
  #Something else - obj, probably
  return $offset . $type . ':' . pkvardump($data);
}

/*
  function Xrequire_once_all($path, $ext = '.php') {
  $path = untrailingslashit($path);
  foreach (glob($path . '/*' . $ext) as $filename) {
  require_once $filename;
  }
  }
 *
 */

function require_once_all($path, $ext = 'php') {
  //$path = untrailingslashit($path);
  if (is_dir($path)) {
    foreach (glob($path . '/*') as $fileordir) {
      require_once_all($fileordir, $ext);
    }
  } else {
    if (is_file($path) && ($ext === getFileExtension($path))) {
      //if (is_file($path)){
      require_once ($path);
    }
  }
}

/**
 * Return how many dimensions an array/argument has.
 * My model - if $arg is not an array, return null.
 * If empty array, return 0.
 *
 * @param mixed $arg:
 * @param type $depth
 * @return null|integer
 */
function array_dimension($arg, $depth = 0) {
  if (!is_array($arg)) {
    return 'NULL';
  }
  if (!sizeOf($arg)) {
    return '0';
  }
  $max_sub_depth = 0;
  foreach ($arg as $subarray) {
    $max_sub_depth = max(
        $max_sub_depth, array_dimension($subarray, $depth + 1)
    );
  }
  return $max_sub_depth + 1;
}

/**
 * Takes an array mixed with integer keys with string values and string keys
 * with any values, and converts all the intKey => stringVal to stringVal => null
 * (or a passed in default value)-
 * Ex: ['id', 'name'=>'text','age','date'=>['format'=>'sql']] becomres:
 * ['id'=>$default, 'name'=>'text', 'age'=>$default, 'date'=>['format'=>'sql]]
 * @param array $array
 * @param mixed $default: default: null
 * @return array
 */
function mixedArrToAssoc($array = null, $default = null) {
  if (!$array) {
    return [];
  }
  if (is_scalar($array)) {
    return [$array => $default];
  }
  if (!is_array($array)) {
    return [$array];
  }
  $defArr = [];
  foreach ($array as $key => $value) {
    if (!is_string($key) && is_string($value)) {
      $defArr[$value] = $default;
    } else {
      $defArr[$key] = $value;
    }
  }
  return $defArr;
}

/**
 * Compares two arguments (recursively) to see if they have the same dimensions
 * and structures. Additional parameters allow true for empty arrays or non-arrays
 * @param mixed $array1
 * @param mixed $array2
 * @param boolean $allowEmpty: return true if both arrays are empty. Default False
 * @param boolean $allowNotArray: return true if both args are not arrays. Default: False
 * @param int $depth: How deep to go into the arrays. Default: -l, all the way
 * @return Boolean: True if args are comprable, false if not.
 */
function arrays_compatable($array1, $array2, $allowEmpty = false, $allowNotArray = false, $depth = -1) {
  $depth = to_int($depth);
  if ($depth === 0) {
    return true;
  }
  if (!$allowNotArray) {
    if (!is_array($array1) || !is_array($array2)) {
      return false;
    }
  } else if (!is_array($array1) && !is_array($array2)) {
    return true;
  }
  if (is_array($array1) !== is_array($array2)) {
    return false;
  }
  if (!$allowEmpty) {
    if (!sizeOf($array1) || !sizeOf($array2)) {
      return false;
    }
  } else if (!sizeOf($array1) && !sizeOf($array2)) {
    return true;
  }
  if (sizeOf($array1) !== sizeOf($array2)) {
    return false;
  }
  $sz = sizeOf($array1);
  for ($i = 0; $i < $sz; $i++) {
    $arrEl1 = array_pop($array1);
    $arrEl2 = array_pop($array2);
    if (!arrays_compatable($arrEl1, $arrEl2, true, true, $depth - 1)) {
      return false;
    }
  }
  return true;
}

/* * Quick lazy way to get a list of id's (or other values) from an array like:
 * [['id'=>1, 'name'=>'Johnny'], ['id'=>2, 'name'=>'Sally', ...]]
 * With no (deault) arg, get [1,2,..], or if use arg = 'name', get ['Johnny', 'Sally',]
 */

function getIndexedArrayOfKeyValues($array = null, $key = 'id') {
  if (!$array || !is_array($array)) {
    return [];
  }
  $resarr = array_map(function ($ar) use($key) {
    return $ar[$key];
  }, $array);
  return $resarr;
}

/**
 * CamelCase insensitive equals: Returns boolean false if two strings are NOT
 * CC equivalent, or else $str2 if they are, where CC equivalent is any of:
 * "cc_str" == "ccStr". If arg $ci == true, also match: "ccstr"
 * @param string $str1
 * @param string $str2
 * @return boolean or string:
 */
function cciEquals($str1, $str2, $ci = true) {
  if (!$str1 || !$str2 || !is_string($str1) || !is_string($str2)) {
    return false;
  }
  $cmpstr = $str2;
  $ccStr = toCamelCase($str1);
  $ucc_str = unCamelCase($str1);
  if ($ci) {
    $cmpstr = mb_strtolower($str2);
    $ccStr = mb_strtolower($ccStr);
  }
  if (($ccStr === $cmpstr) || ($ucc_str === $cmpstr)) {
    return $str2;
  }
  return false;
}

/**
 * Checks if the given name is found in the array, either in camelCased or
 * un_camel_cased form.
 * @param string $name: The name to check, both CC'd and unCC'd
 * @param Array $array: Array to check
 * @return mixed: matched name form, or boolean false
 */
function cciInArray($name, $array) {
  if (!$array || !$name) {
    return false;
  }
  $ucc_name = unCamelCase($name);
  $ccName = toCamelCase($name);
  if (in_array($ucc_name, $array, true)) {
    return $ucc_name;
  }
  if (in_array($ccName, $array, true)) {
    return $ccName;
  }
  return false;
}

/** In between in_array($a, $b) & in_array($a,$b, true) -- it will match
 * [0] && ['0'], but not 0 & null, etc...
 * @param type $needle
 * @param type $haystack
 */
function in_array_equivalent($needle, $haystack) {
  if (!is_arrayish($haystack)) return false;
  foreach ($haystack as $tst) {
    if (equivalent($needle, $tst)) {
      return true;
    }
  }
  return false;
}

/**
 * To compare POSTed values to DB values; return:
 * TRUE for: (1,'1'), (0,'0'),
 * FALSE for: (0,''), (0,null), (null,[]), (0,[]), ('',[])
 * But what about: (NULL, ''), (FALSE, null)?
 * Wait & see...
 * @param type $a
 * @param type $b
 */
function equivalent($a, $b) {
  if (is_string($a) && is_int($b)) {
    $b = "$b";
  }
  if (is_string($b) && is_int($a)) {
    $a = "$a";
  }
  /*
    if ($a === null) {
    $a = '';
    }
    if ($b === null) {
    $b = '';
    }
   *
   */
  return $a === $b;
}

/** Displays a number as dollar format - or if array arg, totals the numeric
 * values of the array and retuns them as a $ formatted string
 * @param numeric|numeric array $num - the value or array of values to represent
 * @param int|string|array $opts:
 *   if int - precision $prec.
 *   If string - $wrap_class: enclose results in div w. class; negative in "$wrap_class negative-dollar-value"
 *   If boolean true - $wrap_class: enclose results in div w. class 'dollar-format'; negative in "$wrap_class negative-value"
 *   If array, look for those keys, & 'hide0' as well, for multiple options
 * @return string - dollar formatted
 */
function d_f($num, $opts = 0, $hide0 = true) {
  return dollar_format($num, $opts, $hide0);
}

function dollar_format($num, $opts = 0, $hide0 = false) {
  if ($opts === null) $db = 1;
  else $db = 0;
  if (!is_array($opts)) {
    if (is_intish($opts) && ($opts !== true)) $opts = ['prec' => $opts];
    else if (ne_string($opts) || ($opts === true))
        $opts = ['wrap_class' => $opts];
    else $opts = [];
  }
  $prec = keyVal('prec', $opts, 0);
  $wrap_class = keyVal('wrap_class', $opts);
  if ($wrap_class === true) $wrap_class = "dollar-format";
  $hide0 = keyVal('hide0', $opts, $hide0);
  if (is_array($num)) {
    $num = array_sum($num);
  }
  //if($db)pkdebug('hide0', $hide0, 'num', $num, 'wrap-class',$wrap_class,'prec',$prec);
  if (($num === null) || ($num === '') || ($hide0 && !$num)) return '';
  $dol = '$';
  if ($num < 0) {
    $dol = '-$';
  }
  $formatted = $dol . number_format(abs($num), $prec);
  if (!$wrap_class) return $formatted;
  if ($num < 0) {
    $wrap_class .= ' negative-dollar-value';
  }
  return "<div class='pk-dollar-value $wrap_class'>$formatted</div>\n";
}

/** Returns the key value of the array if it exists, or null
 *
 * @param scalar $key: Key to test
 * @param array $array: Array to test in
 * @param boolean $emptyArray: default false; despite the function name, if
 * $emptyArray is true, will return an empty array instead of null.
 * @return mixed or null: The value, if any, for $array[$key], or null
 */
/*
  function keyValOrNull($key = '', $array = [], $emptyArray = null) {
  if ($emptyArray) {
  $emptyArray = [];
  }
  if (!is_array($array)) {
  return $emptyArray;
  }
  if (!is_scalar($key)) {
  return $emptyArray;
  }
  if (array_key_exists($key, $array)) {
  return $array[$key];
  }
  return $emptyArray;
  }
 *
 */

/** Returns the value of the key for an Object or Array, else the default (or null)
 * the array at the key, or else the default value (null), or as specified
 * in the $default parameter
 *
 * TODO: Currently,just access the values in scope (public, whatever) - add option for all attributes?
 *
 * Will also return values of class static vars, even if modified in calling instance method!
 * @param scalar $key - Key into the array or object attributed
 * @param array|object $container - array/object to check for the key
 *   #NOTE Object container STILL EXPERIMENTAL - NOT SO USEFUL SINCE ONLY FOR PUBLIC ATTRIBUTES (including static); ALSO WON'T WORK FOR MAGIC GETS!
 * @param mixed $default - the default value to return if no key value in array for key, default null
 * @param - if true & container is Object, force a get to trigger magic method.
 * @return mixed - The value of array for $key, if any, or else the default (null)
 */
function keyValOrDefault($key, $container = [], $default = null, $forceAttrGet = false) {
  if (is_object($container)) {
    if (is_arrayish($container)) { #Dereference like an array, not object
      if ($container->offsetExists($key)) return $container[$key];
      return $default;
    }
    if ($forceAttrGet) {
      return $container->$key;
    }
    $array = get_all_object_vars($container);
  } else if (is_array($container)) {
    $array = $container;
  } else {
    return $default;
  }
  if (!is_scalar($key) && ($key !== null)) {
    return $default;
  }
  if (array_key_exists($key, $array)) {
    return $array[$key];
  } else {
    return $default;
    #If using reflection to get ALL scopes...
  }
}

/** Just shorter */
function keyVal($key = '', $container = [], $default = null, $forceAttrGet = false) {
  return keyValOrDefault($key, $container, $default, $forceAttrGet);
}

/** Gets the CURRENT values of the static vars (including NULL) of a OBJECT INSTANCE, EVEN IF MODIFIED BY AN INSTANCE
 * @param object $object - the object to fetch static keys/values from, including NULLs.
 * @return array of current static keys/values for the object instance, including null.
 */
function get_static_vars($object) {
  if (!is_object($object)) return null;
  $instanceobjkeys = array_keys(get_object_vars($object)); #only returns instance attributes
  $class = get_class($object);
  $result = [];
  foreach (get_class_vars($class) as $name => $value) {
    if (!in_array($name, $instanceobjkeys)) $result[$name] = $value;
  }
  return $result;
}

/** Uses reflection to return (by default) ALL object vars and values, public, private,
 * static, but accepts parameters to limit scope. Also, can't return magic _get properties
 *
 * #NOTE! If an object is derived from a class that declares a private variable, the name
 * of the variable will be visible if call $property->setAccessible(true) - BUT THE VALUE WILL NOT
 * But if the parent declares the property as protected, both the name and value will be available
 *
 * #NOTE! Be careful of var_dump - some values may be recursive!
 *
 * @param object $object - The object to fetch properties from
 * @param INT|NULL $permissions(optional) If set, is the ORed Permissions/Protection levels of attributes to return
 *        default: null -- all constants : ReflectionProperty::IS_STATIC |  ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE
 * @return array key/value pairs of the object. Parent Private property names will be visible, but value will be null.
 */
function get_all_object_vars($object, $permissions = null) {
  if (!is_object($object)) return null;
  if ($permissions === null) {
    $permissions = ReflectionProperty::IS_STATIC | ReflectionProperty::IS_PUBLIC | ReflectionProperty::IS_PROTECTED | ReflectionProperty::IS_PRIVATE;
  }
  $reflector = new ReflectionClass($object);
  $properties = $reflector->getProperties($permissions);
  $objvars = [];
  foreach ($properties as $property) {
    $property->setAccessible(true);
    $objvars[$property->getName()] = $property->getValue($object);
  }
  return $objvars;
}

/** Returns an array of ancestor class names (including this one)
 * - already implemented as 'ancestry()'...
 * @param object|string $objclassname - either an object instance, or a class name
 * @return array hierarchy of ancestor class names, starting with this.
 *
 */
/*
  function getAncestors($objclassname){
  static $classhierarchies = []; #Cache so don't have to execute again for same class
  if (is_obect($objclassname)) {
  $class= get_class($objclassname);
  } else if (class_exists($objclassname,1)) {
  $class = $objclassname;
  } else {
  return false;
  }
  #Have a class name, now ascend hierarchy
  if (array_key_exists($classhierarchies,$class)) return $classhierarchies[$class];
  $classhier = [$class];

  }
 *
 */

/**
 * For possessionDef arrays, returns a value if exists for key, else null
 * @param array $def
 * @param string $key
 * @param $default: Default value to return if none found - defaults to null
 * @return string|$default if no value found;
 */
/*
  function getKeyValOrDefault($def, $key, $default = null) {
  if (!is_array($def)) {
  return $default;
  }
  if (array_key_exists($key, $def)) {
  return $def[$key];
  }
  return $default;
  }
 *
 */

/** Returns only those $key/$value pairs in $array which have
 * keys in $keys
 * @param array|scalar $keys: a scalar key / array of keys to check in $array
 * @param array $array: The array to check
 * @return array: Subset of the original $array, whose keys are in $keys
 */
function array_subset($keys, $array) {
  if (is_scalar($keys)) {
    $keys = [$keys];
  }
  if (empty($keys) || empty($array) || !is_array($array)) {
    return [];
  }
  $out_array = [];
  foreach ($array as $key => $value) {
    if (in_array($key, $keys, TRUE)) {
      $out_array[$key] = $value;
    }
  }
  return $out_array;
}

/** Returns the mime-type of the file in the path. NOTE! Relies on the
 * PECL/finfo / 'fileinfo' extension, which is provided with PHP after 5.3, BUT
 * NOT ENABLED BY DEFAULT ON WINDOWS! Have to uncomment the extension in php.ini
 * @param type $filePath: The filesystem path of the file
 * @return string: MIME file type, as best guessed by PHP
 */
function getFileMimeType($filePath) {
  return finfo_file(finfo_open(FILEINFO_MIME_TYPE), $filePath);
}

/** Returns the mime type if a valid image file, else false
 * Primitive ; improve some day
 * @param string $filePath - a string/path for the file
 * @return false || string - mime-type
 */
function isValidImagePath($filePath) {
  if (!$filePath || !is_string($filePath) || !file_exists($filePath))
      return false;
  $mimeType = getFileMimeType($filePath);
  if (!$mimeType || !is_string($mimeType)) return false;
  $mimeArr = explode('/', $mimeType);
  //pkdebug("mimeType: [$mimeType], mimeArr:", $mimeArr);
  if (!$mimeArr || !is_array($mimeArr) ||
      !(count($mimeArr) === 2) || !($mimeArr[0] === 'image')) return false;

  //pkdebug("Returninb: $mimeType");
  return $mimeType;
}

function lightDbg () {
  $args = func_get_args();
  $retargs = [];
  foreach ($args as $arg) {
    if (is_scaler($arg)) {
      $retargs[]=$arg;
    } else if ($arg instanceOf PkModel) {
      $retargs[] = $arg->totag();
    } else {
      $retargs[] = typeOf($arg) . " size: " . sizeOf($arg);
    }
  }
  return call_user_func_array('pkdebug', $retargs);
}
/** If the $filePath is a valid info & can get dimensions, return width/height,
 * else 0/false
 * @param type $filePath
 */
function aspectRatio($filePath) {
  if (!($mimeType = isValidImagePath($filePath))) return false;
  $exif = null;
  try {
    $exif = @exif_read_data($filePath);
  } catch (Exception $e) {
    error_log("Exception Reading EXIF for file [$filePath]: " . $e->getMessage());
    pkdebug("Exception reading EXIF for file [$filePath]:", $e);
  }
  if (($computed = keyVal('COMPUTED', $exif)) && ($width = keyVal('Width', $computed)) && ($height = keyVal('Height', $computed))) {
    return $width / $height;
  }
  #If no exif data, use GD:
  //$gdresource = imagecreatefromstring (file_get_contents($filePath));
  $sz = getimagesize($filePath);
  if (($width = keyVal(0, $sz)) && ($height = keyVal(1, $sz)))
      return $width / $height;
  return false;
}

/** Kind of hokey function that takes two arrays, and returns true if the second
 * array equals the first, up to the depth of the first. For example,
 * ['image'], ['image','gif'] would return true...
 * @param type $arr1
 * @param type $arr2
 * @return boolean: True if $arr2 == $arr1 to depth of $arr1
 */
function arrays_equal_to_depth_of_first($arr1, $arr2) {
  if (is_string($arr1)) {
    $arr1 = [$arr1];
  }
  if (is_string($arr2)) {
    $arr2 = [$arr2];
  }
  if (!is_array($arr1) || !is_array($arr2)) {
    throw new \Exception("Invalid Args: arr1: ["
    . print_r($arr1, 1) . "]; arr2: [" . print_r($arr2, 1) . "]");
  }
  $i = 0;
  foreach ($arr1 as $el) {
    if ($el !== $arr2[$i]) {
      return false;
    }
    $i++;
  }
  return true;
}

/**
 * Creates a presumably really Globally unique file name.
 * @param string $suffix: Optional file name suffix, if important...
 * @return string: Unique file name, no Dir, optionally w. suffix
 */
function uniqueName($suffix = '') {
  if ($suffix) {
    $suffix = ".$suffix";
  }
  return uniqid('upld', 1) . '_' . uniqid() . $suffix;
}

/** Good for PHP >= 7 -- alphanum, okay for URLs
 *
 * @param int $len
 * @return string - random
 */
function pkrandstr($len = 7) {
  $bytes = random_bytes($len); //random. Increase input for more bytes
  $code = bin2hex($bytes);
  return $code;
}
/**
 * Takes two arrays that have similar key structures, but then not. Makes an
 * array that combines the two input arrays up to the key parallels, then
 * adds the elements where the keys are different.
 * Example: Args:
 * <tt>['idx1' => [ 'idx2' => [ 'name' => "abilito 2 .doc", ],]</tt> and
 * <tt>['idx1' => [ 'idx2' => [ 'type' => "application/msword", ],]</tt>
 * <p>
 * Will return:
 * <pre>['idx1' => [ 'idx2' => [ 'type' => "application/msword",
 *                              'name' => "abilito 2 .doc", ]]
 * </pre>
 * @param type $arr1
 * @param type $arr2
 * @return array: Melded array
 */
function array_meld($arr1, $arr2) {
  if (!is_array($arr1) || !is_array($arr2)) {
    return combineAsArrays($arr1, $arr2);
  }
  $retarr = [];
  foreach ($arr1 as $key1 => $val1) {
    $key2 = key($arr2);
    //$val2 = $arr2[$key2];
    $val2 = keyVal($key2, $arr2);
    next($arr2);
    if ($key1 === $key2) {
      $retarr[$key1] = array_meld($val1, $val2);
    } else {
      return array_merge($arr1, $arr2);
    }
  }
  return $retarr;
}

/** Replaces or deletes any key/values in $arr1 with any comperable values in $arr2
 *
 * @param type $arr1
 * @param type $arr2
 */
#Ah, nevermind - seems I just recreated the built in PHP array_replace_recursiv
/*
  function pkarray_replace(&$arr1, $arr2) {

  if (!is_array($arr2) || !is_array($arr1)) return $arr1;
  if (!sizeOf($arr2)) { #We want to clear arr1 with an empty array
  $arr1 = $arr2;
  return $arr1;
  }
  foreach ($arr2 as $key=>$val) {
  if (!$val || !is_array($val) || !(array_key_exists($key,$arr1)) || !is_array($arr1[$key])) {
  $arr1[$key] = $val;
  } else { #$val is an array, $arr1[$key] is an array,  recurse
  array_replace($arr1[$key], $val);
  }
  }
  return $arr1;
  }
 *
 */

/**
 * Removes all values of $value from the input array. If $reindex, returns an
 * a contiguous indexed array of values, otherwise sparse
 * @param array $array
 * @param scalar $value
 * @param type $reindex: Reindex and return compacted indexed array
 * @return array - with all instances of $value removed
 */
function array_remove_by_value($array, $value, $reindex = true) {
  if (!is_array($array)) {
    return [];
  }
  if ($reindex) {
    return array_values(array_diff($array, [$value]));
  }
  return array_diff($array, [$value]);
}

/**
 *
 * @return array: Restructured $_FILES array, with the new array keys path/
 * sequence matching the control name - that is, if the file input name is:
 * <tt>input type='file' name='my_test_upload[idx1][idx2]'</tt>
 * the returned "files" array will contain:
 *
 * 'my_test_upload' => [
  'idx1' => ['idx2' => [
 *   'name' => "abilito 2 .doc",
 *   'type' => "application/msword",
 *   'tmp_name' => "C:\\Windows\\Temp\\php934C.tmp",
 *   'error' => 0,
 *   'size' => 15360,  ],
 *
 * @return array: The restructured $_FILES array.
 */
function transposeFilesUploadsArray() {
  $files = $_FILES;
  if (!$files) {
    return [];
  }
  $retarr = [];
  foreach ($files as $basename => $fileArray) {
    $retbit = [];
    foreach ($fileArray as $fileDescKey => &$subArr) {
      array_walk_recursive($subArr, function(&$item, $key, $newKey) {
        $item = [$newKey => $item];
      }, $fileDescKey);
      if ($retbit) {
        $retbit = array_meld($retbit, $subArr);
      } else {
        $retbit = $subArr;
      }
    }
    $retarr[$basename] = $retbit;
  }
  return $retarr;
}

/**
 * Converts $arg to a number with maximum $prec decimal places, or less
 * if not required. For ex: $arg = "10.57366" becomes 10.57. $arg 10 returns 10.
 * @param mixed $arg: A scalar castable to a number
 * @param int $prec: Max number of decimal places
 * @return number: A number with at most $prec decimal places, or less
 */
function minDec($arg, $prec = 2) {
  $factor = pow(10, $prec);
  return (round($factor * $arg)) / $factor;
}

/** Lifted straight from WP, but too simple and elegant not to use */

/**
 * Removes trailing forward slashes and backslashes if they exist.
 *
 * The primary use of this is for paths and thus should be used for paths. It is
 * not restricted to paths and offers no specific path support.
 *
 * @since 2.2.0
 *
 * @param string $string What to remove the trailing slashes from.
 * @return string String without the trailing slashes.
 */
function pkuntrailingslashit($string) {
  return rtrim($string, '/\\');
}

/**
 * Appends a trailing slash.
 *
 * Will remove trailing forward and backslashes if it exists already before adding
 * a trailing forward slash. This prevents double slashing a string or path.
 *
 * The primary use of this is for paths and thus should be used for paths. It is
 * not restricted to paths and offers no specific path support.
 *
 * @param string $string What to add the trailing slash to.
 * @return string String with trailing slash added.
 */
function pktrailingslashit($string) {
  return pkuntrailingslashit($string) . '/';
}

function pkunleadingslashit($string) {
  return ltrim($string, '/\\');
}

function pkleadingslashit($string) {
  return '/'.pkunleadingslashit($string);
}


/**
 * Wrapper for PHP mcrypt_encrypt, with some defaults. MUST USE the balanced
 * pk_decyrpt function to decrypt. Unlike mcrypt, $key can be any string, and
 * $data can be any PHP data - string, object, array, etc. Uses PHP serialize
 * to convert
 * @param string $key: Any arbitrary sting to use as encryption key
 * @param mixed $data: Any PHP data to encrypt
 * @return string: The string representing the encrypted data
 */
function pk_encrypt($rawkey, $data, $utf8 = true, $cipher_type = MCRYPT_RIJNDAEL_128, $mode = MCRYPT_MODE_CFB) {
  $iv = mcrypt_create_iv(mcrypt_get_iv_size($cipher_type, $mode));
  $hkey = substr(hash('sha256', $rawkey), -mcrypt_get_key_size($cipher_type, $mode));
  $serialized = serialize($data);
  $rawencrypted = mcrypt_encrypt($cipher_type, $hkey, $serialized, $mode, $iv);
  if (!$utf8) return 'UNENC' . $iv . $rawencrypted;

  return 'UTF-8' . utf8_encode($iv . $rawencrypted);
}

/**
 * Decrypts arbitrary PHP data/object encrypted by pk_encrypt.
 * @param type $rawkey
 * @param type $data
 * @param type $cipher_type
 * @param type $mode
 * @return type
 */
function pk_decrypt($rawkey, $data, $cipher_type = MCRYPT_RIJNDAEL_128, $mode = MCRYPT_MODE_CFB) {
  $encstr = substr($data, 0, 5);
  $data = substr($data, 5);
  if ($encstr == 'UTF-8') $data = utf8_decode($data);
  $ivsize = mcrypt_get_iv_size($cipher_type, $mode);
  $iv = substr($data, 0, $ivsize);
  $encdata = substr($data, $ivsize);
  $hkey = substr(hash('sha256', $rawkey), -mcrypt_get_key_size($cipher_type, $mode));
  $serialized = mcrypt_decrypt($cipher_type, $hkey, $encdata, $mode, $iv);
  $unserialized = @unserialize($serialized);
  if ($unserialized === false)
      throw new Exception("Failed to unserialize. Maybe the decrypt passphrase is wrong?");
  return $unserialized;
}

/**
 * Full array column too complicated to bother with - this is good enough until
 * upgrade to PHP 5.5, then delete
 * Returns an indexed array of all values of the array of arrays, for the given
 * column name/idx
 */
if (!function_exists('array_column')) {

  //function array_column(Array $arr, $idx='id') {
  function array_column(Array $arr, $idx) {
    if (!sizeOf($arr)) return [];
    if (!is_scalar($idx) || !isset($arr[0][$idx]))
        throw new Exception("Invalid key [$idx] for:" . print_r($arr, 1));
    $retarr = [];
    foreach ($arr as $item) {
      $retarr[] = $item[$idx];
    }
    return $retarr;
  }

}

/**
 * Formats an indexed array of associative arrays in an HTML table. If
 * $headers exists, uses them as header names, otherwise, uc field name
 * @param array $arr
 * @param array $headers
 * @param array $cssclasses
 */
function displayArraysAsTable($arr, $headers = [], $cssclasses = '') {
  if (!is_array($arr)) return '';
  if (!is_array($arr[0])) return '';
  if (!$headers) {
    $item = $arr[0];
    $keys = array_keys($item);
    foreach ($keys as $key) {
      $headers[] = ucfirst($key);
    }
  }
  if (is_array($cssclasses)) {
    $cssclasses = implode(' ', $cssclasses);
  }
  $out = '';
  $out .= "\n<table class='$cssclasses'>\n";
  $out .= "\n<tr>";
  foreach ($headers as $header) {
    $out .= "<th>$header</th>";
  }
  $out .= "</tr>";
  foreach ($arr as $row) {
    $out .= "<tr>";
    foreach ($row as $item) {
      $out .= "<td>$item</td>";
    }
    $out .= "</tr>\n";
  }
  $out .= "\n</table>\n";
  return $out;
}

/**
 * Mangages Static Cache Arrays of arbitrary depth. Initializes array down to
 * level depth-1 if no value exists.
 *
 * Functions and static methods can use a static nested array to retain values
 * that don't need to be recalculated. For example, usage in a Class (with SubClasses):
 * <pre>
 * public static function calculateSomething($param) {
 *   static $cacheArr = [];
 *   $class = get_called_class();
 *   if (!manageStaticCache($cacheArr,[$class,$param])) {
 *     $cacheArr[$class][$param] = someCalculation($class, $param);
 *   }
 *   return $cacheArr[$class][$param];
 * }
 * </pre>
 * @param type $cacheArr
 * @param type $levels
 * @return boolean true if the value has been set, even if to null, else
 * false
 */
function manageStaticCache(&$cacheArr, $levels = []) {
  $nl = sizeOf($levels);
  if (!$levels || !$nl) return false;
  $tmpArr = &$cacheArr;
  $depth = 1;
  foreach ($levels as $level) {
    if ($depth === $nl) {
      if (array_key_exists($level, $tmpArr)) {
        return true;
      }
      return false;
    } else {
      if (!array_key_exists($level, $tmpArr)) {
        $tmpArr[$level] = [];
      }
      $tmpArr = &$tmpArr[$level];
    }
    $depth ++;
  }
  return;
}

/** Converts Unix/PHP time value to SQL format.
 *
 * @param int $unixtime - If not null, the unix time to convert to SQL format.
 * If null, return current moment as SQL date/time
 * @return string The SQL date/time
 */
function unixtimeToSql($unixtime = null, $just_date = false) {
  if (!$unixtime) {
    $unixtime = time();
  }
  if ($just_date) $fmt = 'Y-m-d';
  else $fmt = 'Y-m-d H:i:s';
  $mysqldate = date($fmt, $unixtime);
  return $mysqldate;
}

/** Difference between the SQL date & Unix Date in Seconds -
 * Positive values are SQL date AFTER UNIX, negative before.
 * If Unix date is null, defaults to now.
 * @param string $sqlDate
 * @param null|int $unixDate
 */
function diffSqlDate($sqlDate, $unixDate = null) {
  if (!validSqlDate($sqlDate)) return false;
  $sqlToUnix = strtotime($sqlDate);
  if ($unixDate === null) $unixDate = time();
  return $sqlToUnix - $unixDate;
}

/** Takes any $date arg, & tries to convert & return an
 * SQL date (time).
 * @param string|int $date - if valid SQL date/time, just return it
 *    if intish, assume it's unix time, convert to SQL Date/time
 *    if another string, hope it's
 * @param boolean $andtime - return sql time part also?
 * @return string|null - an SQL Date/Time if possible, else null
 */
function asSqlDate($date, $andtime=true) {
  if (!$date) {
    return null;
  }
  if (validSqlDate($date)) {
    return $date;
  }
  if (is_intish($date) && to_int($date)) {
    return unixtimeToSql(to_int($date), !$andtime);
  }
  if (is_stringish($date)) {
    $unixTime = strtotime($date);
    if (!$unixTime) {
      return null;
    }
    return unixtimeToSql($unixTime, !$andtime);
  }
  throw new Exception ("Didn't handle date format: ".$date);
}

/** Tests if $val is valid SQL Date/Time string
 * Pulled it off the web, so who knows..?
 * @param mixed $val
 * @return boolean
 */
function validSqlDate($val) {
  if (!$val || !is_string($val)) return false;
  if (preg_match("/^([0-9]{4})-([0-9]{2})-([0-9]{2})$/", $val)) return true;
  // This seems to only work with DateTime
  $matches = [];
  if (preg_match("/^(\d{4})-(\d{2})-(\d{2}) ([01][0-9]|2[0-3]):([0-5][0-9]):([0-5][0-9])$/", $val, $matches)) {
    if (checkdate($matches[2], $matches[3], $matches[1])) {
      return true;
    }
  }
  return false;
}

/** Returns seconds diff (+future or -past) between $sqlDt1 & $sqlDt2
 *
 * @param string $sqlDt1 - an SQL date
 * @param string|null $sqlDt2 - an SQL date, or NULL for now
 * @param boolean $just_date - just use date, not date-time for now
 * @return int|null - seconds diff, or null if invalid SQL date format
 */
function sqlDateCmp($sqlDt1, $sqlDt2 = null, $just_date = false) {
  if (!$sqlDt2) $sqlDt2 = unixtimeToSql(null, $just_date);
  if (!validSqlDate($sqlDt1) || !validSqlDate($sqlDt2)) return null;
  return strtotime($sqlDt1) - strtotime($sqlDt2);
}

/** Checks if the SQL date is in the future (from Now)
 * @param string $sqlDt - SQL date formatted date to test
 * @param boolean $just_date - just use date, not date-time for now
 * @return int|boolean|null - #seconds in future if future, or 0 if NOW,
 *    or FALSE if past, or NULL if invalid SQL date format
 */
function sqlDateFuture($sqlDt, $just_date = false) {
  $diff = sqlDateCmp($sqlDt, null, $just_date);
  if (!is_numeric($diff)) return $diff;
  if ($diff < 0) return false;
  return $diff;
}

/** Checks if the SQL date is in the past (from Now)
 * @param string $sqlDt - SQL date formatted date to test
 * @param boolean $just_date - just use date, not date-time for now
 * @return int|boolean|null - #seconds in past if past, or 0 if NOW,
 *    or FALSE if past, or NULL if invalid SQL date format
 */
function sqlDatePast($sqlDt, $just_date = false) {
  $diff = sqlDateCmp($sqlDt, null, $just_date);
  if (!is_numeric($diff)) return $diff;
  if ($diff > 0) return false;
  return -$diff;
}

/**
 * Converts an SQL formatted date/time string to unix integer timestamp
 * @param string $sqldate The SQL date/time string. If null, returns current unix time.
 * @return int the unix timestamp
 */
function sqlDateToUnix($sqldate = null) {
  if ($sqldate) {
    $unixtime = strtotime($sqldate);
  } else {
    $unixtime = time();
  }
  return $unixtime;
}

// American style m/d/y:   'n/j/y'
function unixDateToFriendly($unixdate = null, $format = 'M j, Y') {
  if ($unixdate === null) $unixdate = time();
  return date($format, $unixdate);
}

function sqlDateToFriendly($sqldate = null, $format = 'M j, Y') {
  return unixDateToFriendly(sqlDateToUnix($sqldate), $format);
}

function execInBackground($cmd) {
  if (substr(php_uname(), 0, 7) == "Windows") {
    pclose(popen("start /B " . $cmd, "r"));
  } else {
    exec($cmd);
  }
}

/**
 * Wraps content in 'td' tags and applies
 * either default or given style to the td - for use in HTML formatted
 * emails
 * @param string $text Whatever content you want in your td
 * @param string $tag Default to 'td', but can be 'th' or whatever
 * @param string $style The inline CSS Style you want to apply to your td content
 * @return string HTML element wrapped by tag with inline style
 */
function elementWrap($text = '', $tag = 'td', $style = 'border: solid black 1px;') {
  return "<$tag style='$style'>$text</$tag>";
}

/** Returns the names or full paths of all files in the directory
 *
 * @param string $dirPath The Directory path to search
 * @param boolean $fullPath Should return the full path of each file, or just
 *   the file names? Default true, full file path.
 * @return boolean|array False if invalid directory path, else an array of
 * the file names or full paths.
 */
function getAllFilesInDir($dirPath, $fullPath = true) {
  if (!is_dir($dirPath)) return false;
  $entries = scandir($dirPath);
  $filePaths = [];
  foreach ($entries as $entry) {
    if (($entry === '.') || ($entry === '..')) continue;
    $tmpPath = "$dirPath/$entry";
    if (!is_file($tmpPath)) continue;
    if ($fullPath) {
      $filePaths[] = $tmpPath;
    } else {
      $filePaths[] = $entry;
    }
  }
  return $filePaths;
}

/** Like array_reverse, except also works on ArrayObjects
 *
 * @param array|ArrayObject $items
 * @return - the items in reverse order
 */
function arrayish_reverse($items) {
  if (!is_arrayish($items)) return $items;
  if (is_array($items)) return array_reverse($items);
  if (!($items instanceOf ArrayObject)) { #can't be bothered
    return $items;
  }
  #So, $items are an instance of ArrayObject or subclass...
  $itemsArray = $items->getArrayCopy();
  $revItemsArray = array_reverse($itemsArray);
  $itemClass = get_class($items);
  $revItems = new $itemClass($revItemsArray);
  return $revItems;
}

/** Allows null with is_scalar */
function is_scalar_or_null($arg = null) {
  if ($arg === null) return true;
  return is_scalar($arg);
}

function makePathToFile($filePath, $permissions = 0755) {
  if (file_exists($filePath)) return true;
  $dirname = dirname($filePath);
  if (is_dir($dirname)) return true;
  return mkdir($dirname, $permissions, true);
}

function is_windows() {
  if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') return true;
  return false;
}

function getNullPath() {
  if (is_windows()) return 'NUL';
  return '/dev/null';
}

/** Returns a string value for true/false. Default is "True" or "False", but
 *
 * @param mixed $val - truish or not
 * @param string $true - String to return if truish - default "True"
 * @param string $false - String to return if not truish - default "False"
 */
function stringboolean($val, $true = 'TRUE', $false = 'FALSE') {
  return $val ? $true : $false;
}

/**
 * (Direct copy from WordPress)
 * Test if the current browser runs on a mobile device (smart phone, tablet, etc.)
 * @return bool true|false
 */
function pk_is_mobile() {
  static $is_mobile;
  if (isset($is_mobile)) return $is_mobile;
  if (empty($_SERVER['HTTP_USER_AGENT'])) {
    $is_mobile = false;
  } elseif (strpos($_SERVER['HTTP_USER_AGENT'], 'Mobile') !== false // many mobile devices (all iPhone, iPad, etc.)
      || strpos($_SERVER['HTTP_USER_AGENT'], 'Android') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Silk/') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Kindle') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'BlackBerry') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mini') !== false || strpos($_SERVER['HTTP_USER_AGENT'], 'Opera Mobi') !== false) {
    $is_mobile = true;
  } else {
    $is_mobile = false;
  }
  return $is_mobile;
}

/**
 * Returns the base class name of the namespaced class or object or trait
 * @param string|object - the class or object to get the basename of
 * @return string - the basename of the class/object
 */
function getBaseName($class = null) {
  if (!$class) return false;
  if (is_string($class)) {
    $className = $class;
  } else if (is_object($class)) {
    $className = get_class($class);
  }
  if (strrchr($className, "\\") === false) {
    return $className;
  }
  return substr(strrchr($className, "\\"), 1);
}

/** Doesn't belong here, but quick and dirty - to assign a "name" to an HTML
 * form element - if the first argument exists, the second argument is added
 * as an array key, or a series of array keys. Either argument can be an array
 * of strings.
 * @param string|array|null $arg1 - the first argument to build the name from
 * @param string|array|null $arg2 - the second argument to build the name from
 * @return string - the HTML Form Input Element name
 * <p>
 * Examples:
 * $arg1 = null, $arg2 = 'hello', return 'hello'
 * $arg1 = 'goodbye', $arg2 = 'hello', return 'goodbye[hello]'
 * $arg1 = ['goodbye', 'all', 'my', 'old', 'friends'], $arg2 = 'hello',
 *    return 'goodbye[all][my][old][friends][hello]'
 * $arg1 = 'goodbye[my][old]',  $arg2 = [2,'friends'],
 *    return 'goodbye[my][old][2][friends]'
 *
 */
function buildFormInputNameFromSegments($arg1 = null, $arg2 = null) {
  $name = '';
  if (is_scalar($arg1)) $name = (string) $arg1;
  else if (is_array($arg1))
      foreach ($arg1 as $idx => $val) {
      if (!is_scalar($val))
          throw new Exception("Invalid value: " . print_r($val, 1));
      if (!$idx) $name = (string) $val;
      else $name .= '[' . (string) $val . ']';
    } else if ($arg1) throw new Exception("Bad arg1: " . print_r($arg1, 1));

  if (($arg2 === null) || ($arg2 === '')) return $name;
  if (!$name && is_scalar($arg2)) return (string) $arg2;
  if ($name && is_scalar($arg2)) return $name . '[' . (string) $arg2 . ']';
  if (!is_array($arg2)) throw new Exception("Bad Arg2: " . print_r($arg2, 1));
  foreach ($arg2 as $key => $val) {
    if (!is_scalar($val))
        throw new Exception("Invalid value: " . print_r($val, 1));
    if (!$name) $name = (string) $val;
    else $name .= '[' . (string) $val . ']';
  }
  return $name;
}

/**
 * Useful for converting empty strings to null for inserting null to int
 * fields in DB
 * @param mixed $value
 * @return integer|null
 */
function intOrNull($value = null) {
  $int = to_int($value);
  if ($int !== false) return $int;
  return null;
}

/** Case Insensitive "in_array" - works only with strings, of course
 *
 * @param mixed $needle
 * @param array $haystack
 * @param boolean $strict
 * @return boolean
 */
function in_array_ci($needle, $haystack, $strict = false) {
  return in_array(strtolower($needle), array_map('strtolower', $haystack), $strict);
}

/**
 * Returns a percentage, if arguments valid, else $invalid
 * @param numeric $a
 * @param numeric $b
 * @param string $invalid - what to return if invalid, like "N/A". Default: ''
 * @return string - the percentage, or $invalid
 */
function percentage($a, $b, $invalid = '') {
  if (!$b || !is_numeric($b) || !is_numeric($a)) return $invalid;
  $dr = (int) (($a * 100) / $b);
  return $dr . '%';
}

/** Just checks if the HTTP Request method was a post
 * @return boolean
 */
function isPost() {
  if (isCli()) {
    return false;
  }
  return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/* * I use lots of arrays for configuration, where I expect the form to be an
 * associative array of keys, each with a value array, like:
 * [$key1=>['p1'=>$scalar1, 'p2'=>$scalar2,..], $key2=>['p1'=>$vv1,...]]
 *
 * But I don't want to write all that if not all params are set. I write the array
 * like: $config =[$key1,$key2=>$scalar2, $key3=['p1'=>$scalar3,'p2'=>$scalar4,..]
 *
 * But in the above, $key1 is a value, not a key, and the value for $key2 is a
 * scalar, not an array. So this converts $config as written into a normalized form.
 * If $default &&
 * If $struct===null, return array will just switch indexed values to keys and
 * return [$key1=>$default, $key2=>$val2, etc.
 *
 * In the special case where $default = "#key", the default value will be the key
 *
 * if $struct is string, returns
 * [$key1=>[$struct=>null], $key2=>[$struct=>$scalar2], $key3=>[$struct=>null, 'p1'=>$scalar3...
 *
 * if $struct is an array, if $struct[0] is string, is used as above, but
 * the associative part of the array is used as defaults for the returned value arrays
 * and merged with the returned value arrays
 *
 * if $struct is assoc array with no int keys, for array values is merged as
 * above to provide defaults; but if val is scalar, stays the val
 *
 * but want make them as simple as
 * possible - like, if I have array of function names and arguments, I might
 * use the funcname as key and args as val - but if the function doesn't take
 * args, don't want to bother with [$funcname => null, ...], so just use funcname
 * as a $val and let it have a numeric index. And if there may be a single val
 * with a default name, or ...
 * @param array $arr - the array to normalize to assoc array [$keyva1=>$val1,
 * @param array|string|null $struct - if present, has defaults for the return array
 *   $struct might be: ['fieldtype','fieldtype'=>'integer','comparison'=>'numeric',...]
 *
 *   foreach key of $arr, if the key is int & $val is string, make $val new $key
 *     and set the new val to $struct
 *     if $key is string, keep as index - if no $struct, keep val as val
 *     if $struct is scalar & $val is scalar, make val array: [$struct=>$val]
 *     if $struct is array - $struct[0] contains the keyname if $val is scalar
 *   if scalar - AND $val is scalar
 * @param mixed $default: The default
 *
 * @return array of form: [$keyName => ['valkey1' => $valOrDefault, 'valkey2
 */

function normalizeConfigArray(array $arr = [], $struct = null,$default=null) {
  if (!$arr || !is_array($arr) || !count($arr)) return [];
  $defkey =  null;
  $defarr = $default;
  if (is_array($struct)) {
    $defkey = keyVal(0, $struct);
    unset($struct[0]);
    $defarr = $struct;
  } else if (is_string($struct)) {
    $defkey = $struct;
  }
  $retarr = [];
  foreach ($arr as $origkey => $origval) {
    if (is_int($origkey) && is_string($origval)) {
      //$retarr[$origval] = $defarr;
      $retarr[$origval] = ($defarr === '#key')?$origval : $defarr;
    } else if (is_string($origkey)) {
      if (is_scalar($origval) && $defkey) {
        $newval = [$defkey => $origval];
      } else {
        $newval = $origval;
      }
      if (is_array($newval) && is_array($defarr)) {
        $newval = array_merge($defarr, $newval);
      }
      $retarr[$origkey] = $newval;
    } else { #What to do?
      $retarr[$origkey] = $origval;
    }
  }
  return $retarr;
}

/** Normalize/convert an arg into an associative array if it isn't already.
 *
 * @param stringish|array $arg - The argument to make into an ass arr if it isn't
 * @param string $key - the key for the primary argument, if the value is a string
 * @param array|string $defaults - the defaults for the argument array
 *   If string, make it an array w. the same key as arg
 * @param array $addons - values to add to the argument array, if any
 * @param array $replace - if you want you just totally replace any key values
 *
 * Example: arrayifyArg(null, null, ['class'=>'col', 'tag'=>'div'])
 * returns: ['class'=>'col','tag'=>'div']
 *
 * arrayifyArg('Name',null,['tag'=>'div'])
 * returns ['value'=>'Name', 'tag'=>'div]
 */
function arrayifyArg($arg = null, $key = 'value', $defaults = null, $addons = null, $replace = null) {
  if (is_stringish($defaults) || ($defaults === null))
      $defaults = [$key => $defaults];
  $retarr = [];
  if (is_stringish($arg)) {
    $retarr = [$key => $arg];
  } else if (is_array_assoc($arg)) {
    $retarr = $arg;
  } else if (is_array_indexed($arg)) { #arg[0] is $key val, $arg[1] other atts
    $retarr = keyVal(1, $arg, []);
    #Cheat - if $retarr is string, assume it's a "class"
    if (is_string($retarr)) $retarr = ['class' => $retarr];
    $retarr[$key] = $arg[0];
  }
  else if (!$arg && !$key) $retarr = [];
  else throw new Exception("Invalid arg: " . print_r($arg, 1));
  if ($defaults && is_array($defaults)) {
    foreach ($defaults as $ddkey => $ddval) {
      if (!keyVal($ddkey, $retarr)) {
        $retarr[$ddkey] = $ddval;
      }
    }
  }
  if ($replace && is_array_assoc($replace)) {
    foreach ($replace as $rkey => $rval) {
      $retarr[$rkey] = $rval;
    }
  }
  if ($addons && is_array_assoc($addons)) {
    foreach ($addons as $akey => $aval) {
      $retarr[$akey] = keyVal($akey, $retarr, '') . ' ' . $aval . ' ';
    }
  }
  if (!isset($retarr[$key])) {
    //  pkdebug("Woops! key [$key] not set in retarr:",$retarr,'OrigArg:', $arg);
    $retarr[$key] = null;
  }
  return $retarr;
}

/**
 * For getting valid params or defaults. REMOVES $param key=>values if
 * $key not in $default - so safe to use with extract($result);
 * Takes an associative array of parameters, and associative array of
 * defaults and returns an associative array JUST WITH KEYS IN DEFAULTS,
 * but with values of $params if the key exists
 * @param type $params
 * @param type $defaults
 */
function getParamsOrDefault($params = [], $defaults = []) {
  $badkeys = array_diff(array_keys($params), array_keys($defaults));
  foreach ($badkeys as $badkey) {
    unset($params[$badkey]);
  }
  return array_merge($defaults, $params);
}

/** Takes a scalar as first arg, and unlimited other args
 * (arrays or objects) and returns the first non-empty value for that key.
 * @param scalar $key
 * @param arrayish $arg1
 * @param arrayish $argx
 */
function firstKeyVal($key, $arg1 = null, $argx = null) {
  $args = func_get_args();
  $key = array_shift($args);
  foreach ($args as $arg) {
    $test = keyVal($key, $arg);
    if ($test) return $test;
  }
  return null;
}

/** Checks these exist and are not empty */

/**
 * Checks if a value (including string) is "intish". PHP is_numeric returns
 * true for '12', but is_int returns false. <tt>is_intish('12')</tt> returns true
 * @param mixed $value - To test for intishness
 * @param boolean $nullorfalse - Should null/false/'' be considered intish?
 * @return boolean
 */
function is_intish($value, $nullorfalse = false) {
  #Objects and arrays never
  if (!is_scalar($value) && ($value !== null)) return false;
  if ($nullorfalse && !$value) return true;#Zeroish
  if (!is_numeric($value) && !is_bool($value)) return false;
  $intval = (int) $value;
  $diff = abs($value - $intval);
  //pkdebug("Value:", $value, "DIFF", $diff, 'INTVAL', $intval);
  return !$diff;
}

/** is_numeric($val) returns true for $val='17';. is_number returns true for
 * $val=17;, false for $val='17';
 * @param type $val
 */
function is_number($val) {
  if (is_int($val) || is_float($val) || is_bool($val)) return true;
  return false;
}

function ne_stringish($var) {
  return $var && is_stringish($var) && strlen($var.'');
}

function ne_string($var) {
  return $var && is_string($var) && strlen($var);
}

function ne_array_assoc($var) {
  return $var && is_array_assoc($var) && count($var);
}

function ne_array($var) {
  return $var && is_array($var) && count($var);
}

function ne_arrayish($var) {
  return $var && is_arrayish($var) && count($var);
}

function ne_intish($var) {
  return is_intish($var) && $var;
}

/** Sets the members of Object object to the corresponding key values of the
 * associative $atts array
 * @param Object $obj
 * @param array $atts
 * @return array - remaining $atts not used to set properties
 */
function setInstanceAtts($obj, array $atts = []) {
  if (!is_object($obj)) return false;
  foreach ($atts as $key => $value) {
    if (property_exists($obj, $key)) {
      $obj->$key = $value;
      unset($atts[$key]);
    }
  }
  return $atts;
}

/**
 * Like PHP unset($array[$key]), but returns the value, or $default if key not set.
 * @param array $arr
 * @param scalar $key
 * @param mixed $default
 * @return mixed
 */
function unsetret(&$arr, $key = null, $default = null) {
  if (($key === null) || !is_array($arr)) {
    pkdebug("Odd - not quite an array op: key:, arr:", $key, $arr);
    $ret = $arr;
    unset($arr);
    return $ret;
  }
  $ret = keyVal($key, $arr, $default);
  unset($arr[$key]);
  return $ret;
}

/** Takes an arbitrary number of mixed args, returns as a flattened array
 *
 */
if (!function_exists('array_flatten')) {

  function array_flatten($array = null) {
    $result = [];
    if (!is_array($array)) {
      $array = func_get_args();
    }
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $result = array_merge($result, array_flatten($value));
      } else {
        $result = array_merge($result, array($key => $value));
      }
    }
    return $result;
  }

}

/** Like Explode, but delimeter may be an ARRAY of delimiters, so all exploded.
 *
 * @param string|array $delimiter
 * @param string $string
 * @param boolean|string $trim - default TRUE, trim results. If false, don't, if
 * string, use as arg to trim.
 * @param booleain $omitempty - remove empty results
 * @return array - flat indexed array, with explode applied with all delimiters,
 * and trimmed as specified.
 */
function explode_all($delimiter, $string, $trim = true, $omitempty = true) {
  if (is_string($string)) {
    $string = [$string];
  }
  if (!is_array($string)) {
    throw new Exception("Invalid string arg: " . print_r($string, 1));
  }
  if (!is_array($delimiter)) {
    $delimiter = [$delimiter];
  }
  foreach ($delimiter as $del) {
    foreach ($string as $key => $substr) {
      unset($string[$key]);
      $pieces = explode($del, $substr);
      foreach ($pieces as $piece) {
        $string[] = $piece;
      }
    }
  }
  if (!$omitempty && !$trim) {
    return $string;
  }
  $resarr = [];
  foreach ($string as $el) {
    if ($trim) {
      if (is_string($trim)) {
        $el = trim($el, $trim);
      } else {
        $el = trim($el);
      }
    }
    if ($omitempty && ($el === '')) {
      continue;
    }
    $resarr[] = $el;
  }
  return $resarr;
}

/** Getting hierarchies, classes & methods */
/**
 * Returns an array of parent classes PLUS this class
  @param object|class_name $oorc
 */
function classHierarchy ($oorc) {
  if (is_object($oorc)) {
    $oorc = get_class($oorc);
  }
  $hierarchy = class_parents($oorc) ?:[];
  $hierarchy[] = $oorc;
  return $hierarchy;
}

/** Ascends the class hierarchy & returns all the traits used
 * inherited by this class
 * @param type $oorc
 */
function getAllTraits($oorc) {
  return getTraits($oorc, 1);
}
function getDirectTraits($oorc) {
  return getTraits($oorc,false);
}
/** $traits using traits is if using a trait that uses another trait */
function getTraits($oorc, $traitsusingtraits) {
  $hierarchy =  classHierarchy($oorc);
  $traits = [];
  foreach ($hierarchy as $class) {
    $traits = array_merge($traits, array_keys(class_uses($class)?:[]));
  }
  if ($traitsusingtraits) {
    $traits_to_search = $traits;
    while (!empty($traits_to_search)) {
      $new_traits = array_keys(class_uses(array_pop($traits_to_search))?:[]);
      $traits = array_merge($new_traits, $traits);
      $traits_to_search = array_merge($new_traits, $traits_to_search);
    }
  }
  return array_unique($traits);
}

/** Does an object/class use a trait? Like instanceOf
 *
 * @param type $oorc
 * @param string $trait
 * @root - just compare the root, not the whole namespace name
 */
/*
function hasTrait($oorc, $trait, $root = true) {
  $traits = getTraits($oorc, 1);
  if (!$root) {
    return in_array($trait, $traits, 1);
  }
 *
 */
  /** Checks if class "instanceOf" $traitname, or all of [traitnames]
   * $target - default null, so for the current class. Else,
   * can be a classname or object instance
   * $useBaseName - default true - remove Namespace components
   */
function usesTrait($traitName, $target, $useBaseName=true) {
  if (is_object($target)) {
    $target = get_class($target);
  }
  $targetTraits = getTraits($target, 1);
  if (!$targetTraits) {
    return false;
  }
  if (ne_string($traitName)) {
    $testTraits = [$traitName];
  } else if (ne_array($traitName)) {
    $testTraits = $traitName;
  } else {
    return false;
  }
  foreach ($testTraits as $key=>$testTrait) {
    if ($useBaseName) {
      $testTrait = getBaseName($testTrait);
    }
    $testTraits[$key] = strtolower($testTrait);
  }
  foreach($targetTraits as $key=>$targetTrait) {
    if ($useBaseName) {
      $targetTrait = getBaseName($targetTrait);
    }
    $targetTraits[$key] = strtolower($targetTrait);
  }
  foreach ($testTraits as $testTrait) {
    if (!in_array($testTrait, $targetTraits, 1)) {
        return false;
    }
  }
  return true;
}

/** Returns the trait methods - if $trait is an array,
 * all the methods for all the traits
 * @param string|array $trait
 * @param array of traits to ignore/remove
 * @param int - any filters to apply, like only static, etc. See ReflectionClass
 * @return array of NAMES of methods implemented by the named traits
 */
function traitMethods($traits, $omittraits=[],$methodFilter = 0) {
  if (!$traits) {
    return [];
  }
  if (is_string($traits)) {
    $traits = [$traits];
  }
  $traits = array_diff($traits, $omittraits);
  $traitmethods = [];
  foreach ($traits as $trait) {
    $reflection = new ReflectionClass($trait);
    $traitmethods = array_merge($traitmethods,
        array_map(function($ar) {return $ar->name;},($reflection->getMethods($methodFilter))));
  }
  //pkdebug("Trait Methods:", $traitmethods);
  return array_unique($traitmethods);
}
function isJson($string) {
 json_decode($string);
 return (json_last_error() == JSON_ERROR_NONE);
}
/*
  175=>  ReflectionMethod:object(ReflectionMethod)#426 (2) {
  ["name"]=>
  string(17) "fillableFromArray"
  ["class"]=>
  string(54) "Illuminate\Database\Eloquent\Concerns\GuardsAttributes"
 *
 */


/** If argument is non-jason, just returns it - if it is valid
 * JSON string, restores it to structured arr
 * @param type $arg
 * @return type
 */
function restoreJson($arg) {
  if (!is_scalar($arg) || !$arg) return $arg;
  $res = json_decode($arg,1);
  if (!$res) return $arg;
  return $res;
}

/**   Look like dupe of above */
function usesTraits($class, $trait) {
  return in_array($trait, allTraits($class), 1);
}

/**** From the manual - all the traits a class (or object) uses */
function allTraits($class, $autoload = true) {
    if (is_object($class)) {
      $class = get_class($class);
    }
    $traits = [];
    // Get traits of all parent classes
    do {
        $traits = array_merge(class_uses($class, $autoload), $traits);
    } while ($class = get_parent_class($class));

    // Get traits of all parent traits
    $traitsToSearch = $traits;
    while (!empty($traitsToSearch)) {
        $newTraits = class_uses(array_pop($traitsToSearch), $autoload);
        $traits = array_merge($newTraits, $traits);
        $traitsToSearch = array_merge($newTraits, $traitsToSearch);
    };

    foreach ($traits as $trait => $same) {
        $traits = array_merge(class_uses($trait, $autoload), $traits);
    }
    return array_unique($traits);
}

/** Compares objects or classes or class names - returns true if they
 * are identical - not subclasses, else false. Can take class names or 
 * objects
 * @param mixed $var1 - 
 * @param mixed $var2
 * @return boolean - true if identical class, else false
 */
function is_exactly_a($var1, $var2) {
  if (is_object($var1)) $var1 = get_class($var1);
  if (is_object($var2)) $var2 = get_class($var2);
  return is_a($var1,$var2,1) && is_a($var2,$var1,1);
}

function &arref(&$arr, $key) {
   return $arr[$key];
}

/** PHP "class_implements($mixed) returns an array of all interfaces implemented
 * by $mixed (which can be a class name or object) - does implement checks if
 * $mixed implements (the fully namespaced) interface
 * @param object|string classname : $mixed
 * @param string $interface - interface name
 */
function does_implement($mixed, $interface) {
  $interfaces = class_implements($mixed);
  if (!is_array($interfaces)) return false;
  if (in_array($interface, $interfaces, 1)) {
    return true;
  }
}