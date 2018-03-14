<?php
namespace PkExtensions;
/**
 * Try to see if I can usefully extend the php-console tools
 *
 * @author pkirkaas
 */
use PhpConsole\Helper;
class PkPC extends Helper {
  public static $starttime = 0;
  public static $endtime = 0;
  public static $startmsg = '';
  public static $endmsg = '';
  public static $startframe = null;
  public static $endframe = null;
  public static function startTimer($startmsg = '') {
    static::$startmsg = $startmsg;
    static::$starttime = time();
    static::$startframe = callingFrame(['file'=>__FILE__,'line'=>__LINE__,
        'class'=>__CLASS__, 'function' => __FUNCTION__]);
  }
  public static function endTimer($endmsg = '') {
    static::$endmsg = $endmsg;
    //static::$endtime = time();
    $interval = time() - static::$starttime;
    static::$endframe = callingFrame(['file'=>__FILE__,'line'=>__LINE__,
        'class'=>__CLASS__, 'function' => __FUNCTION__]);
    $data = [
        "Starting at:" => ['startmsg'=>static::$startmsg]+static::$startframe,
        "Finising at:" => ['endmsg'=>static::$endmsg]+static::$endframe,
        "Interval:" => $interval . " seconds",
        ];
    static::timer($data);
  }

  //put your code here
}
