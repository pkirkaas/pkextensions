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

  public $datarow;
  public function __construct($datarow = []) {
    $this->datarow = $datarow;
  }
  public function key() {
    $local = $this->datarow;
    return key($local);
  }
  public function value() {
    $local = $this->datarow;
    return current($local);
  }

  public static $refcache = [];

  /** If !$key, returns array of all ref objs of this type, 
   * else if $key, returns the instance for $key
   */
  public static function getRefObjs($key = null) {
    $class = static::class;
    if (!array_key_exists($class, static::$refcache)) {
      $refs = [];
      $refArr = static::getRefArr();
      foreach ($refArr as $refKey => $refValue) {
        $refs[$refKey] = new static ([$refKey=>$refValue]);
      }
      static::$refcache[$class] = $refs;
    }
    $refInstances = static::$refcache[$class];
    if (!is_arrayish($refInstances)) throw new Exception ("No valid ref instance array");
    if ($key) {
      return keyVal($key, $refInstances);
    }
    return static::$refcache[$class];
  }

  /** 
   * Return a random instance, or array (collection) of random instances
   * @param integer $num: If -1 (default), return single instance. 
   *   if ($num >= 0) return array/collection of $num instances
   * @param array $params - Can be used by subclasses to filter
   * @return instance|array instances - 
   */
  public static function getRandomRefs($items = -1, $params = []) {
    return PkTestGenerator::getRandomData(static::getRefObjs(), $items);
  }

  /*
   * @param array $params - Can be used by subclasses to filter
   * 
   */
  public static function getRandomValues($items = -1, $params = []) {
    return PkTestGenerator::getRandomData(static::getRefArr(), $items);
  }
  public static function getRandomKeys($items = -1, $params = []) {
    return PkTestGenerator::getRandomData(static::keys(), $items);
  }


  public function __toString() {
    return $this->value();
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
