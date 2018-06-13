<?php
namespace PkExtensions;
use PkRenderer;
### Danger Will Robinson! static::getRefArr() only works if static::$refArr is set!
### Have to fix that....
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
  #ONE of these should be set, 
  public static $refArr;# Simplest: [$key1=>$val1,$key2=>$val2,...
  public static $idxRefArr;# indexed array of arrays: [['key'=>$key1,'value'=>$val1,],['key'=>$key2,'value'=>$val2,...
  public static $keyRefArr; #keyed array of arrays: [$key1=>['value'=>$val1],$key2=>['value'=>$val2,]...
  public static $labels = ['', '', ''];

  /** Obviously, so implementing classes can get / generate their refArray other
   * than relying on static $refArray
   * @return array
   */
  public static function getLabels() {
    return static::$labels;
  }

  /** Used to return a simple array - for use in Laravel Form Select boxes, etc.
   * And to use in PkModel methods '->getDisplayValue()
   * still does for pre-existing ref classes-
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

/** See function getOptionList below for more option definition
  * makes a full select box control for the current reference item.
  @param $name - string or array of "select" attributes. If just string, assumed
  to be ['name'=>$name]
  @return - Render wrapped HTML select box for this refence class
  */
  public static function makeSelect($name,$null=false,$current=false) {
    if (ne_stringish($name)) {
      $name = ['name'=>$name];
    }
    if (!ne_array($name)) {
      throw new PkException ("Invalid name arg for making select control");
    }

    $options = static::getOptionList($null, $current);
    $selectbox = PkRenderer::tagged('select',$options,$name,true);
    return $selectbox;
  }

  /** Returns the $refArr as an array of <option>s - 
   * The key of the refarr will the the option value,
   * if the target of the key is scalar, that will be displayed
   * in the option - BUT if the target is an array, the first
   * value of the array will be the "option" contents, the second
   * value of the array will be the tooltip/data-tootik
   *@param boolean|string $null -
     Include null as an option? TRUE for default, or string for custom message
    @param $current - the current value if one is selected 
   *@return array of strings like "<option value='1' title='A tip'>First</option>"
   */
  public static function getOptionList($null = false, $current = false) {
    if ($null && !is_string(null)) {
      $null = "None";
    }

    $ret = [];
    if ($null) {
      $ret[]="<option>$null</option>\n";
    }
    foreach (static::$refArr as $key => $target) {
      if (is_array_indexed($target)) {
        $display=$target[0];
        $tip = " title='$target[1]' ";
      } else if (is_array_assoc($target)) {
        $i=0;
        foreach ($target as $tkey => $tval) {
          if ($i === 0) {
            $display = $tval;
          } else if ($i===1) {
            $tip = " $tkey='$tval' ";
          } else {
            break;
          }
        }
      } else {
        $display = $target;
        $tip = '';
      }
      if ($current == $key) {
        $selected = ' selected ';
      } else {
        $selected = '';
      }
      $ret[] = "<option value='$key' $selected $tip>$display</option>\n";
    }
    return $ret;
  }

/*Just like above, except returns just idx array of assoc arrays, for Vue select
 * @return array like: [
 *   ['value'=>$value1, 'label'=>$label1],
 *   ['value'=>$value2, 'label'=>$label2],

 *  */
  public static function mkVueSelectArray($null=true) {
      return static::mkIdxRefArr($null);
  }





  /**Defaults to getRefArr, but if $merged, combines the
   * key & value in the select display, with $merged as the separator
   * @param boolean $null - include null value?
   * @param boolean|string $merged - if 'true', combine w. ':' , if
   * string, use that.
   * 
   */
  public static function getSelectList($null=false,$merged = false) {
    if (!$merged) return static::getKeyValArr($null);
    if (!is_string($merged)) $merged = ':';

    $refArr = [];
    foreach ( static::getKeyValArr($null) as $key => $val) {
      $refArr[$key] = $key." $merged ".$val;
    }
    if (!$null) return $refArr;
    return [null=>'None'] + $refArr;

  }




  /** Really should ONLY use this, NOT getRefArr()!
   * In this case, static::$refArr is NOT ['key'=>$value], but
   * EITHER: 
   * ['key'=>['value'=>$value,'other'=>$other]],
   *  OR:
   * [['key'=>$key,'value'=>$value,'other'=>$other,],]
   * but THIS will return
   * in the normalized form of ['key'=>$value] 
   * @return array of arrays of key=>value
   */
  public static function getKeyValArr($null = false) {
    if (is_array(static::$refArr)) return static::getRefArr($null);

    #TODO: Restore the caching later...
    if (is_array(static::$idxRefArr)) {
      if ($null) $refArr = [null=>'None'];
      else $refArr = [];
      foreach (static::$idxRefArr as $refRow) {
        $refArr[keyVal('key', $refRow)] = keyVal('value', $refRow);
      }
      return $refArr;
    }
    if (is_array(static::$keyRefArr)) {
      if ($null) $refArr = [null=>'None'];
      else $refArr = [];
      foreach (static::$keyRefArr as $key => $refRow) {
        $refArr[$key] = keyVal('value', $refRow);
      }
      return $refArr;
    }
    //if (!static::$multival) return static::getRefArr($null);
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
    $retArr =  static::$cache[$class][$kvk];
    if ($null) $retArr = [null=>'None'] + $retArr;
    return $retArr;
    //return static::$cache[$class][$kvk];
  }

  public static function displayLabel($idx = null) {
    return keyval($idx, static::getLabels());
  }

  /** Returns the value for the key.
   * @param $key - the key
   * @param $raw - if true & the $value is an array, return the array
   *   if false, just the first element of the value array.
   * @return the value matching the key.
   */
  public static function displayValue($key = null, $raw = false) {
    $refArr = static::getKeyValArr(true);
    $value = keyVal($key, $refArr);
    if (is_array($value) && !$raw) {
      $value = keyVal(0,array_values($value));
    }
    return $value;
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

  /** Takes a subset of keys & returns in array w. values.
   * If $indexed == false, returns [$key1=>$val1,$key2=>$val2..
   * else [['key'=>$key1, 'value'=>$val2],...
   * @param type $keys
   * @param type $indexed
   * @return array of matching $keys/$values
   */
  public static function keyValues($keys,$indexed=false) {
    //foreach($keys as $key) pkdebug("Key",$key);
    if (is_simple($keys)) $keys = [$keys];
    $refArr = static::getKeyValArr();
    //pkdebug("refar",$refArr);
    $retArr = [];
    foreach ($keys as $key) {
      if (array_key_exists($key,$refArr)) {
        if ($indexed) {
          $retArr[]=['key'=>$key,'value'=>$refArr[$key]];
        } else {
          $retArr[$key] = $refArr[$key];
        }
      }
    }
    return $retArr;
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

  /**
   * $datarow one of:
   * [$key => $value] for $refArr
   * [$key => ['value' => $value, 'extra'=>$extra] for $keyRefArr
   * [$idx => ['key'=>$key, 'value' => $value, 'extra'=>$extra] for $idxRefArr
   * @param array $datarow
   */
  public function __construct($datarow = []) {
    $this->datarow = $datarow;
  }

  public function key() {
    $local = $this->datarow;
    reset($local);
    if (is_array(static::$idxRefArr)) { #idxRefArr
      return keyVal('key', $local);
    }
    return key($local); 
    }

  public function value() {
    $local = $this->datarow;
    reset($local);
    if (is_array(static::$refArr)) { #refArr
      return current($local);
    }
    return keyVal('value', current($local));
    //return keyVal('value', $local);
  }

  /** Returns whatever is at $key -
   * if $refArr, just $value;
   * if $idxRefArr or $keyRefArr, the data row
   * 
   * @param scalar $key
   */
  public static function getRow($key) {
    if (is_array(static::$refArr)) return keyVal($key,static::$refArr);
    if (is_array(static::$keyRefArr)) return keyVal($key,static::$keyRefArr);
    if (is_array(static::$idxRefArr)) return keyVal($key,static::$idxRefArr);
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

  /** Only works if there is a refArr. */
  public static function mkIdxRefArray($null=1, $keylabel='value', $valuelabel='label') {
    $refarr = static::getRefArr($null);
    $ret = [];
    foreach ($refarr as $key=>$value) {
      $ret = [$keylabel=>$key, $valuelabel=>$value];
    }
    return $ret;
  }

}
