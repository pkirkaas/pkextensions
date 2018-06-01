<?php
namespace PkExtensions\Traits;
use Undefined;
use Carbon\Carbon;
use ReflectionClass;
/** Common methods that might be useful in many class hierarchies. Like a 
 * common base class for Laravel
 * June 2016 Paul Kirkaas
 */
trait UtilityMethodsTrait {
  public static $jsoncodes =  [
JSON_ERROR_NONE => "No error has occurred",
JSON_ERROR_DEPTH => "The maximum stack depth has been exceeded",
JSON_ERROR_STATE_MISMATCH => "Invalid or malformed JSON",
JSON_ERROR_CTRL_CHAR => "Control character error, possibly incorrectly encoded",
JSON_ERROR_SYNTAX => "Syntax error",
JSON_ERROR_UTF8 => "Malformed UTF-8 characters, possibly incorrectly encoded",
JSON_ERROR_RECURSION => "One or more recursive references in the value to be encoded",
JSON_ERROR_INF_OR_NAN => "One or more NAN or INF values in the value to be encoded",
JSON_ERROR_UNSUPPORTED_TYPE => "A value of a type that cannot be encoded was given",
JSON_ERROR_INVALID_PROPERTY_NAME => "A property name that cannot be encoded was given",
JSON_ERROR_UTF16 => "Malformed UTF-16 characters, possibly incorrectly encoded",
];

//Default json encode opts:
  public static $jsonopts = JSON_PRETTY_PRINT |
     JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_SLASHES ;
  public static $jopts_inatts = JSON_HEX_APOS | JSON_HEX_QUOT;

  /** Success will always return false.
   * 
   * @param boolean $text  if true, returns the text description on error,
   * else the number code.
   */
  public static function json_error($text = true) {
    $res = json_last_error();
    if ($res === JSON_ERROR_NONE) return false;
    if ($text) return static::$jsoncodes[$res];
    return $res;
  }

  /** Checks if class "instanceOf" $traitname
   * @param $triaitname string|array of strings
   * If several trait, must match all
   * $target - default null, so for the current class. Else,
   * can be a classname or object instance
   * $useBaseName - default true - remove Namespace components
   */
  public static function usesTrait($traitName, $target = null, $useBaseName=true) {
    if (ne_string($traitName)) {
      $traits = [$traitName];
    } else if (is_array($traitName)) {
      $traits = $traitName;
    } else {
      throw new PkExceptionResponsable("Need valid Trait names");
    }

    if (!$target) {
      $target = get_called_class();
    } else if (is_object($target)) {
      $target = get_class($target);
    }
    return usesTrait($traits, $target, $useBaseName);
  }

  /** Return all traits used by class */
  public static function getAllTraits($target =null) {
    if (!$target) {
      $target = get_called_class();
    } else if (is_object($target)) {
      $target = get_class($target);
    }
    return getAllTraits($target);
  }

  public static function getTraitMethods($trimtraits=[],$target = null) {
    if (!$target) {
      $target = get_called_class();
    } else if (is_object($target)) {
      $target = get_class($target);
    }
    $traits = getDirectTraits($target);
    $traits = array_diff($traits, $trimtraits);
    return traitMethods($traits);
  }
    

/** Does THIS SPECIFIC CLASS or used TRAIT define the method?
 * NOT an Ancestor!
 * @param string $method
 * @return boolean
 */
  public static function defines($method) {
      $class= static::class;
      if (!method_exists($class,$method)) {
        return false;
      }
      $rm = new \ReflectionMethod($class, $method);
      if ($rm->getDeclaringClass()->name === $class) {
        return true;
      } else {
        return false;
      }
    }
  /** For caching results of methods that don't change. Keyed by 'key' & class
   * @var array $_cache - by class, & key
   */
  public static $_cache = []; 
  
  /** Get cached value for class/key - if key isn't set & 
   * callable provided,  run & set cache class key value 
   * @param string $key
   * @param callable $callable
   * @return mixed
   */
  public static function getCached($key, $callable=null) {
    if (!array_key_exists(static::class,static::$_cache)) {
      static::$_cache[static::class] = [];
    }
    if (array_key_exists($key,static::$_cache[static::class])) {
      return static::$_cache[static::class][$key];
    }
    if (is_callable($callable)) {
      return static::setCached($key, call_user_func($callable));
    }
    return null;
  }

  public static function setCached($key,$value=null) {
    return static::$_cache[static::class][$key] = $value;
  }

  /** Automate all the caching - for "getting" functions 
   * If keyval exists, return it - else set via closure &
   * return
   */
  /*
  public static function getSetCached($key,$closure) {
    if (!array_key_exists(static::class,static::$_cache)) {
      static::$_cache[static::class] = [];
    }
    if (!array_key_exists($key,static::$_cache[static::class])) {
       return static::setCached($key,$closure());
    }
    //return keyVal($key, static::$_cache[static::class]);
    return static::getCached($key);
  }
   * 
   */

  public static function getArraysMerged($prefix, $idx=false) {
  //public static function combineAncestorAndSiblings($prefix, $idx=false) {
    $akey = $prefix.'_combined';
    $closure = function () use ($prefix, $idx) {
      $ancestors = static::getAncestorArraysMerged($prefix,$idx);
      $siblings = static::getSiblingArraysMerged($prefix,$idx);
      //print_r(['siblings'=>$siblings, 'ancestors'=>$ancestors]);
      //echo "For Class ".static::class."; [$prefix] returning: ";
      /*
      pkdebug('Merging ancsibs: Class: ', static::class, "Prefix:",
          $prefix,"Ancestors: ", $ancestors, "Siblings: ",$siblings);
       * *
       */
      if (ne_array($ancestors) && ne_array($siblings)) {
        //print_r(['merged'=>array_merge($ancestors,$siblings)]);
        /*
         pkdebug("Both ancestors & siblings exist - Merged: ",
             array_merge($ancestors, $siblings));
         * 
         */
          return array_merge($ancestors,$siblings);
        }
        if (ne_array($ancestors)) {
        //print_r(['ancestors'=>$ancestors]);
          return $ancestors;
        }
        if (ne_array($siblings)) {
        //print_r(['siblings'=>$siblings]);
          return $siblings;
        }
        //echo "Empty Array: \n";
        return [];
    };
    static::getCached($akey,$closure);
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
   * @param $idx Boolean or 'normalize': is the array type indexed or associative or mixed?
   * Used for merging strategy - 
   * if $idx = true, indexed array, and duplicate values are removed, but indexed 
   * keys aren't over-written.  * Like $possessionDirectDefs are indexed, others assoc.
   * Associative arrays won't eliminate duplicated values, but WILL overrwrite if
   * descendent classes have different values for the same keys.
   * 
   * FINALLY: If 'Normalize' or 'config' (string) - the arrays can be mixed, like
   * ['key1','key2'=>'val2',....
   * In this case, 'key1' is a value, but will be turned into 'key1'=>NULL
   * 
   * Why would you use this? For example, to specify fields that are just required
   * to exist, vs fields that have to exist AND meet requirements - like:
   * $reqArr = ['key1', 'key2'=>function($val) {if ($val < 10) return false; return true;},....
   * @return Array: Merged array of hierarchy
   */
  public static function getAncestorArraysMerged($arrayName, $idx = false) {
    $akey = $arrayName."_ancestor";
    $closure = function() use($arrayName, $idx, $akey) {
      //pkdebug("Making Ancestor Arrays Merged for ".static::class.", akey: $akey");
      //echo ("\nMaking Ancestor Arrays Merged for ".static::class.", akey: $akey\n\n");
      $retArr = [];
      $convert = function($arr) use ($idx) {
        if (is_string($idx)) {
          return normalizeConfigArray($arr); 
        } else {
          return $arr;
        }
      };
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
      if (property_exists($class, $arrayName) && is_array($class::$$arrayName)) {
        $retArr[] = $convert($class::$$arrayName); #Deliberate double $$
      }
      while ($par = get_parent_class($class)) {
        if (!property_exists($par, $arrayName) ||
          ($par::$$arrayName === false) ) {#If a parent wants stop accension
          break;
        }
        if (($tstArr =$par::$$arrayName) && is_array($tstArr) ) {
          //$retArr[] = $par::$$arrayName;
          $retArr[] = $convert($tstArr);
        }
        $class = $par;
      }
      if (!count($retArr)) {
        return [];
      }
      #Now merge. Reverse order so child settings override ancestors...
      $retArr = array_reverse($retArr);
      $mgArr = call_user_func_array('array_merge', $retArr);
      #Mainly to save the developer who respecifies 'id' in the derived direct
      if (($idx === true) && is_array($mgArr)) { #Indexed array, return only unique values. For 'possessionDirectDefs'
        $mgArr = array_unique($mgArr);
      }
      $fullRetArr[$thisClass][$arrayName] = $mgArr;
      pkdebug("After Ancestor Arrays Merged, RESULT:", $mgArr);
      return $mgArr ?: [];
    };
    return static::getCached($akey, $closure);
  }

  /** Similar to get ancestor arrays merged, but this merges static arrays that
   * start with the same prefix - like $methodsAsAttributeNamesUploadTrait...
   * @param string $prefix - the property has to start with that prefix
   * @param boolean $idx - combine as indexed array or assoc.
   * @param boolean $longer - does the property name have to be longer than prefix?
   */
  public static function getSiblingArraysMerged($prefix, $idx=false, $longer=1) {
    //echo "Trying to get SIBLING arrays merged for: ".static::class."\n\n";
    $akey = $prefix."_sibling";
    $closure = function() use($prefix, $idx, $longer) {
      //pkdebug("Making Sibling Arrays merged for class; ".static::class.", with prefix: '$prefix'");
      //echo ("\nMaking Sibling Arrays merged for class; ".static::class.", with prefix: '$prefix'\n\n");
      //echo "\nTrying to get reflection class...\n";
      $ref = new ReflectionClass(static::class);
      //echo "\nGot reflection class, getting properties?...\n";
      //pkdebug("Trying to get properties..");
      $staticprops = $ref->getStaticProperties();
      //echo "\nDid I get the properties? Why Not?\n";
      //pkdebug("Still waiting for the properties...");
      /*
      pkdebug("The static props from reflection are: ", $staticprops,"
        Maybe this is where I made my mistake? I didn't keep the keys
        with the values? Or maybe I was really bad at combining the values...");
       * 
       */
      //pkdebug("Not trying to print the static props");
      //echo("\nNot trying to print the static props\n");
      $tomerge = [];
      foreach ($staticprops as $key => $val) {
       // echo "Key:"; var_dump($key); echo "VAL"; var_dump($val);
        if (startsWith($key, $prefix, false, $longer) && $val && count($val)) {
          $tomerge[]=$val;
        }
      }
      //echo "\n\nArray to merge: ";print_r($tomerge); echo "  count of :".count($tomerge). " \n\n";
      
      if (!count($tomerge)) {
        return [];
      }
      $merged = array_merge_array($tomerge);
    //echo "\n\nSURVIVED THAT! Array to merge: ";print_r($tomerge);
    //echo "  Merged Result: "; print_r($merged); echo "  ". static::class ."\n\n";
      //if ($idx) {
      // $merged = array_unique($merged);
      //}
      return $merged ?: [];
    };
    return static::getCached($akey, $closure);
  }

  #Not doing it this way
  public static $requiredAtts = [];
  public static function getRequiredAtts() {
    return getAncestorArraysMerged('requiredAtts','config');
  }

  /**Gets the basename of the class, if $foreign key is true,
   * returns the default foreign key table name used in the "many" side.
   */
  public static function basename($foreignkey = false) {
    $bn = class_basename(static::class);
    if ($foreignkey) $bn = Str::snake($bn)."_id";
    return $bn;
  }


  /** Does the data provided meet the requirements to create an instance
   * of this class?  Recurses up the ancestor tree to confirm.
  # canCreate() function shouldn't be over-ridden except in exceptional cases.
  # Instead, it calls the current and all the ancestor 
  # static extensionCheck($args) methods which should be implemented in all
   * concerned descendent classes and
  #only returns true if all ancestor methods return true.
  *@param array $args - whatever information the extensionCheck methods require
   */

  /** Has to explicitly return FALSE if check fails, otherwise
   * return $args - gives the checker the opportunity to modify the args
   * @param mixed $args
   * @return false | $args
   */
  public static function extensionCheck($args) {
    return $args;
  }

  public static function canCreate($args) {
    return $args;
    $class = static::class;
    while($class) {
      if (!method_exists($class,'extensionCheck')) {
        return $args;
      }
      if ($class::defines('extensionCheck')) {
        $pra = $args;
        $args = $class::extensionCheck($args);
        if ($args === false) {
          pkdebug("Failed exchck in [$class] w. args:", $pra);
          return false;
        }
      }
      $class = get_parent_class($class);
    }
    return $args;
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

   /** Takes a relative path, checks various possible paths, returns full path
    * if found, else false.
    * @param string $path
    * @return boolean|string path
    */
   public function fileExists($path, $checkdir=false) {
     if (!ne_string($path) && !$checkdir) {
       return false;
     }
     $fullpath = base_path('storage/app/public/' . $path);
     if (file_exists($fullpath)) {
       //pkdebug("The file exists at: [$fullpath]");
       return $fullpath;
     } else {
       return false;
     }
   }

  #Managing uploaded files
  /**
   * Returns a URL based on the filename and Laravel default public upload dir
   * Override in site specific controllers to make a default, eg,
   * <pre>
   * public static function getUrlFromUploadedFilename($filename, $default = '/mixed/img/default-avatar.png') {
   *   return parent::getUrlFromUploadedFilename($filename, $default);
   * }
   * </pre>
   * @param string/UploadedFile $filename - the base uploaded filename, or path relative to doc root/storage.
   * @param string $default - the relative URL from root of a default URL if no file
   * @return string URL
   */

  public static function getUrlFromUploadedFilename($filename, $default = null) {
    return urlFromUploadedFilename($filename, $default);
  }

  /** Given a filename, returns the default upload path
   * 
   * @param string $filename - the uploaded filename or path, AFTER storage & rename
   * @param boolean $symlink - return the hard path, or the path in the symlink directory
   * default: false; the hard path
   * @return string - the filesystem path, or false if not found.
   */
  public static function getPathFromUploadedFilename($filename, $symlink = false) {
    if (!$filename) return false;
    if ($symlink) {
      $path = base_path("/public/storage/$filename");
    } else {
      $path = storage_path("app/public/$filename");
    }
    if (file_exists($path)) return $path;
    return false;
  }

}
