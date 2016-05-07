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
  public static $labels = ['','',''];

  /** Obviously, so implementing classes can get / generate their refArray other
   * than relying on static $refArray
   * @return array
   */
  public static function getLabels() {
    return static::$labels;
  }
  public static function getRefArr() {
    return static::$refArr;
  }
  public static function displayLabel($idx = null) {
    return keyval($idx,static::getLabels());
  }
  public static function displayValue($key = null) {
    $refArr = static::getRefArr();
    return keyValOrDefault($key,$refArr,'');
  }
  public static function giveLabelAndValue($key,$idx) {
    return ['lablel'=> static::displayLabe($idx),
        'value' => static::displayValue($key)
        ];
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

  /** Generates a key/value array for select box, with range of numbers
   * If $max_label is string, uses the string for the max label, and max-int for value
   * If $min_label is string, uses the string for the max label, and min-int for value
   * @param integer $start - starting integer (except for minint)
   * @param integer $end - ending integer (except for maxint)
   * @param string $min_label
   * @param string $max_label
   * @param integer $step - the incremental step
   */
  public static function numberRange($start = 0, $end = 100, $min_label = null, $max_label = null, $step =1) {
    $result = [];
    if ($min_label && is_string($min_label)) $result[-PHP_INT_MAX] = $min_label;
    if (!$step) $step = 1;
    $i = $start;
    while ($i <= $end) {
      $result[$i] = $i;
      $i += $step;
    }
    if ($max_label && is_string($max_label)) $result[PHP_INT_MAX] = $max_label;
    return $result;
  }

  
}
