<?php
namespace PkExtensions\Traits;
use Carbon\Carbon;
use ReflectionClass;
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
  
  public static function getCached($key) {
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

  /** To add dynamic closure methods to classes. Have to implement actually 
   * calling them in the __call() method of the classes themselves, though, like
   * 
      public function __call($method, $args = []) {
        #First see if it's a dynamic method in this class...
        if (static::getDynamicMethod($method)) {
          return $this->callDynamicMethod($method, $args);
        }....
   *   
   * @var type 
   */
  public static $dynamicMethods=[];

  /**
  @param Array $methods - assoc array ['name1'=>$closure1, 'name2'=>closure2,.]
   */
  public static function addDynamicMethod(Array $methods) {
    foreach ($methods as $name=>$closure) {
      static::$dynamicMethods[static::class][$name]=$closure;
    }
  }


  public static function getDynamicMethod($name) {
    $class=static::class;
    if (array_key_exists($class, static::$dynamicMethods) &&
        array_key_exists($name, static::$dynamicMethods[$class])) {
      return static::$dynamicMethods[$class][$name];
    }
    return false;
  }

  public function callDynamicMethod($name, $args) {
    array_unshift($args, $this);
    return call_user_func_array([static::getDynamicMethod($name),'call'],$args);
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
   * Recurse up through inheritance hierarchy and merge static arrays of
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

  /** Like above, only works for instance properties, not just static
   * @param string $arrayName - the instance property name
   * @param boolean $idx (default: false) - Is the property array indexed? In 
   * which case new values are added. If false/assoc, the child/descendent key
   * values REPLACE the ancestor key values
   */
  //  DO I NEED THIS?
  public function getInstanceAncestorArraysMerged($arrayName, $idx=false) {
    $thisClass = $class = static::class;
    #First, build array of arrays....
    $refClass = new ReflectionClass($class);
    $defAtts = $refClass->getDefaultProperties();
    //$retArr[] = $class::$$arrayName; #Deliberate double $$
    $attArr = keyVal($arrayName,$defAtts,[]);
    if (!is_array( $attArr)) {
      return $attArr;
    }
    $retArr[] = $attArr;
    while ($par = get_parent_class($class)) {
      $refClass = new ReflectionClass($par);
      $defAtts = $refClass->getDefaultProperties();
      $parAtt = keyVal($arrayName,$defAtts,[]);
      if ($parAtt === false) {#If a parent wants stop accension
        break;
      }
      if (($tstArr = $parAtt) && is_array($tstArr) ) {
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
    return $mgArr;
  }


  /** Gets the time difference between 2 dates, using defaults
   * 
   * @param scalar|array $args:
   *   If scalar, just tries to figure how many years between now and then
   *   If array:
   * 'from': Required
   * 'to' : Default: now()
   * 'units': 'years',
   * 
   */
  public static function timeDifference($args=[]) {
    if (!$args) return null;
    if (is_scalar($args)) {
      $from = new Carbon($args);
      $to = new Carbon();
    } else if (is_array($args)) {
      if (empty($args['from'])) return null;
      $from = new Carbon($args['from']);
      if (!empty($args['to'])) $to = new Carbon($args['to']);
      else $to =  Carbon::now();
    }
    $units = keyVal('units', $args, 'years');
    $diffMethod = "diffIn".$units;
    return $from->$diffMethod($to);
  }

  /**
   * json_encodes & html_encodes a data array for inclusion as an HTML 
   * element attribute value in a web page
   * @param mixed $arg - a JSON encodable value
   */
  public static function encodeData($arg) {
    $jsenc = json_encode($arg, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $htenc = html_encode($jsenc);
    return $htenc;
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


  /** Sets the properties of the object to the array key/values of $$propertiesName
   * Typical Use: In <tt>__construct($properties=[])</tt>: Use key/value pairs of
   * "$properties" array to set the properties of the object.
   * @param string $propertiesName: The name of the variable array of property values
   */
   public function setProperties($propertiesName = 'properties',$properties=null){
     //TODO: Implement. 
   }
}
