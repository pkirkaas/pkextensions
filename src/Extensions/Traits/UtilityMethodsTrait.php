<?php
namespace PkExtensions\Traits;
/** Common methods that might be useful in many class hierarchies. Like a 
 * common base class for Laravel
 * June 2016 Paul Kirkaas
 */
trait UtilityMethodsTrait {

  /** For caching results of methods that don't change. Keyed by 'key' & class
   *
   * @var array 
   */
  public static $_cache = []; 
  
  public function getCached($key) {
    $class = static::class;
    if (array_key_exists($class,static::$_cache)) {
      return keyVal($key, static::$_cache[$class]);
    }
    return null;
  }

  public static function setCached($key,$value=null) {
    $class = static::class;
    static::$_cache[$class][$key] = $value;
    return $value;
  }

  /** If any method wants to stop static::getAncestorMethodResultsMerged() from
   * continuing up the ancestor chain, it sets this flag TRUE. 
   * static::getAncestorMethodResultsMerged() will stop accending ancestors, 
   * but will reset it to FALSE for the next call.
   * 
   * @var boolean
   */
  public static $stopAccensionFlag = false;
  /**
   * Recurse up through inheretence hierarchy and merge static arrays of
   * the given arrayName name.
   *
   * This static function is used by the various "getPossessionXXX()" functions, and
   * returns a merged array, with child definitions overriding base defs.
   * //@param String class: The calling class
   * @param $arrayName String: the name of the static attribute array
   * 
   * @param $idx Boolean: is the array type indexed or associative? Used for 
   *   merging strategy - $possessionDirectDefs are indexed, others assoc.
   * @return Array: Merged array of hierarchy
   */
  public static function getAncestorArraysMerged($arrayName, $idx = false) {
    //$thisClass = $class = get_called_class();
    $thisClass = $class = static::class;
    #All static, so cache results...
    static $fullRetArr = [];
    if (!array_key_exists($class, $fullRetArr)) {
      $fullRetArr[$class] = [];
    }
    if (!array_key_exists($arrayName, $fullRetArr[$class])) {
      $fullRetArr[$class][$arrayName] = null;
    }
    if (is_array($fullRetArr[$class][$arrayName])) { #Already made it, it's empty
      return $fullRetArr[$class][$arrayName];
    }
    //$fullRetArr[$class] = null;
    #First, build array of arrays....
    $retArr = [];
    $retArr[] = $class::$$arrayName; #Deliberate double $$
    while ($par = get_parent_class($class)) {
      if (!property_exists($par, $arrayName) ||
         ($par::$$arrayName === false) ) {#If a parent wants stop accension
        break;
      }
      if (($tstArr =$par::$$arrayName) && is_array($tstArr) ) {
        //$retArr[] = $par::$$arrayName;
        $retArr[] = $tstArr;
      }
      $class = $par;
    }
    #Now merge. Reverse order so child settings override ancestors...
    $retArr = array_reverse($retArr);
    $mgArr = call_user_func_array('array_merge', $retArr);
    #Mainly to save the developer who respecifies 'id' in the derived direct
    if ($idx && is_array($mgArr)) { #Indexed array, return only unique values. For 'possessionDirectDefs'
      $mgArr = array_unique($mgArr);
    }
    $fullRetArr[$thisClass][$arrayName] = $mgArr;
    return $mgArr;
  }

  /** Just like 'getAncestorArraysMerged' above, but uses methods to climb
   * the hierarchy - gives more flexibility.
   * In particular, thinking of field definitions in PkModels - some might want 
   * special handling when building the DB Table defs.
   * 
   * The $methodName must return an array or false - but as above, the array can
   * be indexed or associative, specified by $idx, default false meaning assoc.
   * If an ancestor method returns Boolean FALSE (rather than an empty array),
   * STOP climbing up the hierarchy!
   * @param string $methodName
   * @param mixed - any args the method might want
   * @param boolean $idx
   * @return array - the resulting merged arrays.
   */
  /*
  public static function getAncestorMethodResultsMerged($methodName, $args=null, $idx=false) {
    $thisClass = $class = static::class;
    #All static, so cache results...
    static $fullRetArr = [];
    if (!array_key_exists($class, $fullRetArr)) {
      $fullRetArr[$class] = [];
    }
    if (!array_key_exists($methodName, $fullRetArr[$class])) {
      $fullRetArr[$class][$methodName] = null;
    }
    if (is_array($fullRetArr[$class][$methodName])) { #Already made it, it's empty
      return $fullRetArr[$class][$methodName];
    }
    //$fullRetArr[$class] = null;
    #First, build array of arrays....
    $retArr = [];
    $retArr[] = $class::$methodName($args); 
    while ($par = get_parent_class($class)) {
      if (!method_exists($par, $methodName) ||
       !is_array($res = $par::$methodName($args))) {
        break;
      }
      $retArr[] = $res;
      if( static::$stopAccensionFlag) {
        static::$stopAccensionFlag = false;
        break;
      }
      $class = $par;
    }
    #Now merge. Reverse order so child settings override ancestors...
    $retArr = array_reverse($retArr);
    $mgArr = call_user_func_array('array_merge', $retArr);
    #Mainly to save the developer who respecifies 'id' in the derived direct
    if ($idx && is_array($mgArr)) { #Indexed array, return only unique values. For 'possessionDirectDefs'
      $mgArr = array_unique($mgArr);
    }
    $fullRetArr[$thisClass][$methodName] = $mgArr;
    return $mgArr;
  }
   * 
   */
}