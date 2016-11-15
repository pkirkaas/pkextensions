<?php
namespace PkExtensions;
/**
 * Abstract class mapping keys to descriptions
 * Subclasses generally just need to create a static $refArray of integers to descriptions
 * Adding support for indexed key/value/details
 * @author Paul Kirkaas
 */
abstract class PkRefManager  implements PkDisplayValueInterface{

  public static $multival = false; #Default is simple key/value pairs
  #But if true, ref array of form: [['key'=>$key,'value'=>$value, 'details'=>$details], ['key'=>....
  /**
   * Initialize in implementing classes
   * @var array key=>value; key to store in DB, value to display
   */
  public static $cache = [];
  public static $refArr = [];
  public static $labels = ['', '', ''];

  /** Obviously, so implementing classes can get / generate their refArray other
   * than relying on static $refArray
   * @return array
   */
  public static function getLabels() {
    return static::$labels;
  }

  /** Used to return a simple array - still does for pre-existing ref classes-
   * but for complex ref classes, returns the complex array. To guarantee
   * always getting a simple array of key=>value, use static::getKeyValArr()
   * instead
   * @param boolean $null - if true, adds key null=>'None' to start of array
   * @return array of key=>value
   */
  public static function getRefArr($null=false) {
    if (!$null) return static::$refArr;
    return [null=>'None'] + static::$refArr;
  }

  /**Defaults to getRefArr, but if $merged, combines the
   * key & value in the select display, with $merged as the separator
   * @param boolean $null - include null value?
   * @param boolean|string $merged - if 'true', combine w. ':' , if
   * string, use that.
   * 
   */
  public static function getSelectList($null=false,$merged = false) {
    if (!$merged) return static::getRefArr($null);
    if (!is_string($merged)) $merged = ':';

    $refArr = [];
    foreach ( static::getRefArr() as $key => $val) {
      $refArr[$key] = $key." $merged ".$val;
    }
    if (!$null) return $refArr;
    return [null=>'None'] + $refArr;

  }


  public static function getKeyValArr() {
    if (!static::$multival) return static::getRefArr();
    $kvk = 'key-val-cache-key';
    $class = static::class;
    if (array_key_exists($class, static::$cache)) {
      if (array_key_exists($kvk, static::$cache[$class])) {
        return static::$cache[$class][$kvk];
      }
    } else {
      static::$cache[$class] = [];
    }
    $refArr = static::getRefArr();
    foreach ($refArr as $refRow) {
      static::$cache[$class][$kvk][$refRow['key']] = $refRow['value'];
    }
    return static::$cache[$class][$kvk];
  }

  public static function displayLabel($idx = null) {
    return keyval($idx, static::getLabels());
  }

  public static function displayValue($key = null) {
    $refArr = static::getKeyValArr();
    return keyValOrDefault($key, $refArr, '');
  }

  public static function giveLabelAndValue($key, $idx) {
    return ['lablel' => static::displayLabe($idx),
        'value' => static::displayValue($key)
    ];
  }

  public static function notEmpty() {
    $refArr = static::getRefArr();
    if (!key($refArr)) unset($refArr[key($refArr)]);
    //reset($refArr);
    return $refArr;
  }

  /**
   * 
   * @param int|null $first - return just the first N keys, if present
   * @return array - the keys, or first $first keys
   */
  public static function keys($first = null) {
    //$refArr = static::getKeyValArr();
    //$keys = array_keys($refArr);
    $keys = array_keys(static::getKeyValArr());
    if ($first && ($first < count($keys))) {
      $keys = array_slice($keys,0,$first);
    }
    return $keys;
    //return array_keys($refArr);
  }
  public static function values($key=null) {
    $refArr = static::getKeyValArr();
    if ($key !== null) return keyVal($key,$refArr);
    return array_values($refArr);
  }

  public static function baseToFieldname($base) {
    return strtolower($base) . '_ref';
  }

  public static function baseToModelname($base) {
    return "Ref" . ucfirst($base);
  }

  public static function tableFieldName() {
    $baseModel = getBaseName(static::class);
    $base = removeStartStr($baseModel, 'Ref');
    if (!$base) return false;
    $lcbase = strtolower($base);
    return $lcbase . '_ref';
  }

  public $datarow;

  public function __construct($datarow = []) {
    $this->datarow = $datarow;
  }

  public function key() {
    $local = $this->datarow;
    if (!static::$multival) return key($local);
    return keyVal('key', $local);
  }

  public function value() {
    $local = $this->datarow;
    if (!static::$multival) return current($local);
    return keyVal('value', $local);
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
        $refs[$refKey] = new static([$refKey => $refValue]);
      }
      static::$refcache[$class] = $refs;
    }
    $refInstances = static::$refcache[$class];
    if (!is_arrayish($refInstances))
        throw new Exception("No valid ref instance array");
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
  public static function randRefs($items = -1, $params = []) {
    return PkTestGenerator::randData(static::getRefObjs(), $items);
  }

  /*
   * @param array $params - Can be used by subclasses to filter
   * 
   */

  public static function randValues($items = -1, $params = []) {
    return PkTestGenerator::randData(static::getKeyValArr(), $items);
  }

  /**
   * 
   * @param integer $items - if -1, single scalar key, else array of keys
   * @param array $params - ['first'=>null | int - select from the first N keys
   * @return scalar|array - single scalar key if $items == -1, else array of $items keys
   */
  public static function randKeys($items = -1, $params = []) {
    $first = keyVal('first',$params);
    return PkTestGenerator::randData(static::keys($first), $items);
    //return PkTestGenerator::randData(static::keys(), $items);
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
  public static function numberRange($start = 0, $end = 100, $min_label = null, $max_label = null, $step = 1) {
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
