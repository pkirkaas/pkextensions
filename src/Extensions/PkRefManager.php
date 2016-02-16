<?php
namespace PkExtensions;
/**
 * Abstract class mapping keys to descriptions
 * @author Paul Kirkaas
 */
abstract class PkRefManager {
  public static $refArr = [];
  public static function displayValue($key = null) {
    return keyValOrDefault($key,static::$refArr,'');
  }
  public static function notEmpty() {
    $refArr = static::$refArr;
    if (!key($refArr)) unset($refArr[key($refArr)]);
    //reset($refArr);
    return $refArr;
  }
  public static function keys() {
    return array_keys(static::$refArr);
  }
}
