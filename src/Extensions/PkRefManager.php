<?php
namespace PkExtensions;
/**
 * Abstract class mapping keys to descriptions
 * Subclasses generally just need to create a static $refArray of integers to descriptions
 * @author Paul Kirkaas
 */
abstract class PkRefManager {
  /**
   * Initialize in implementing classes
   * @var array key=>value; key to store in DB, value to display
   */
  public static $refArr = [];
  public static $label = ['','',''];

  /** Obviously, so implementing classes can get / generate their refArray other
   * than relying on static $refArray
   * @return array
   */
  public static function getRefArr() {
    return static::$refArr;
  }
  public static function displayValue($key = null) {
    $refArr = static::getRefArr();
    return keyValOrDefault($key,$refArr,'');
  }
  public static function notEmpty() {
    $refArr = static::getRefArr();
    if (!key($refArr)) unset($refArr[key($refArr)]);
    //reset($refArr);
    return $refArr;
  }
  public static function keys() {
    $refArr = static::getRefArr();
    return array_keys($refArr);
  }

  public static function baseToFieldname($base) {
    return strtolower($base).'_ref';
  }
  public static function baseToModelname($base) {
    return "Ref".ucfirst($base);
  }

  public static function tableFieldName() {
    $baseModel = getBaseName(static::class);
    $base = removeStartStr($baseModel,'Ref');
    if (!$base) return false;
    $lcbase = strtolower($base);
    return $lcbase.'_ref';
  }

  
}
