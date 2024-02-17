<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions\Traits;
use Undefined;
use Failure;
use PkExtensions\Models\PkModel;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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


  /** Does this class/object implement the full qualified interface name? */
  public static function implements($interface) {
    //$interface = $interface::class;
    return does_implement(static::class, $interface);
  }

  // God, really thought I did this. Take a associative array of names -> closures,
     //assing the closures to this, key them by name & put them the array, then
     ////implement __call
  //public $newmethods = [];
  /** $closures has to an associatiave array of names to closures. Assign $this
   * to the closure each time. Also has to be called on each construction, 
   * because can't be persistested.
   * @param assoc array $closures
   */
  //public $closures;
  /*
  public function assignMethods($closures = null) {
    if (!$closures) {
      $closures = $this->closures;
    }
    if ($closures instanceOf PkExtenstions\PkClosuresInterface) {
      if (is_class($closures)) {
        $closures = new $closures($this);
      }
      $closures = $closures->generate($this);
    }
    foreach ($closures as $name=>$closure) {
      $this->newmethods[$name]= $closure->bindTo($this,$this);
    }
  }
  public function __call($method, $args) {
    if (in_array($method, array_keys($this->newmethods),1)) {
      return call_user_func_array($this->newmethods[$method],$args);
    }
    return parent::__call($method, $args);
  }
   * 
   */
  /** Checks if class "instanceOf" $traitname
   * @param $triaitname string|array of strings
   * If several trait, must match all
   * $target - default null, so for the current class. Else,
   * can be a classname or object instance
   * $useBaseName - default true - remove Namespace components
   */
  ## 2018 - Uh, don't think we need to worry about target - being called class!
  public static function usesTrait($traitName, $target = null, $useBaseName=true) {
    if (ne_string($traitName)) {
      $traits = [$traitName];
    } else if (is_array($traitName)) {
      $traits = $traitName;
    } else {
      throw new PkExceptionResponsable("Need valid Trait names");
    }

    if (!$target) {
      $target = static::class;
    } 
    return usesTrait($traits, $target, $useBaseName);
  }

  /** Return all traits used by class */
  public static function getAllTraits($target =null) {
    if (!$target) {
      $target = static::class;
    }
    return getAllTraits($target);
  }

  public static function fail($msg=null) {
    return Failure::get($msg);
  }
  public static function failed($res) {
    return $res instanceOf Failure;
  }
  public static function getTraitMethods($trimtraits=[],$target = null) {
    if (!$target) {
      $target = static::class;
    } 
    $traits = getDirectTraits($target);
    $traits = array_diff($traits, $trimtraits);
    return traitMethods($traits);
  }

  /** Returns true if $el is a Builder or a Relation - quite similar
   * Esp. when we add "getModel() via Macro in PkServiceProvider()
   */
  public static function Builderish($el) {
    if (($el instanceOf Builder) || ($el instanceOf Relation)) {
      return true;
    }
    return false;
  }

  /** Returns all trait methods starting with start. This is a great way to use
   * trait methods in implementint classes without overriding them or other 
   * inconveniences. If "plus", must be more than just the prefix
   * @param string $start
   * @param boolean $plus
   */
  /*** Oh, I already did this..
  public static function traitMethodsStarting($start,$plus=true) {
    $methods=[];
    $methods = static::getTraitMethods();
  }
   * *
   */

  /** Returns all methods of this class beginning with the string
   * 'pre'. If $longer is true, (default), the method names have to be 
   * 1 character longer than $pre
   * @param string $pre
   * @param boolean $longer
   * @return array of matching method names
   */
  public function methodsStartingWith($pre,$longer=true) {
    $mtds = get_class_methods($this);
    if (!$mtds || !is_array($mtds)) return [];
    $res = [];
    foreach ($mtds as $mtd) {
       if (startsWith($mtd, $pre,  true, $longer)) {
         $res[] = $mtd;
       }
    }
    return $res;
  }


  /** Methods starting with '__getF' (the F) are a promise
   * to return an instance of \Failure if they can't fulfill it
   * @param string $key - what to get
   * @param boolean $parent - if true, call parent __get, else
   * return failure;
   */
  public function tryTraitFGets($key, $parent = true) {
    $gets = $this->methodsStartingWith('__getF');
    foreach ($gets as $get) {
      if (!$this->failed($res=$this->$get($key))) {
        return $res;
      }
    }
    if ($parent) {
      return parent::__get($key);
    }
    return $this->failure();
  }
    

/** Does THIS SPECIFIC CLASS or used TRAIT define the method?
 * NOT an Ancestor of the Class or used Traits!
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
   * @param mixed $value - if callable, execute it & set
   * @param boolean|string $keyBase - if false, use the current class name
   * as the "root" of the cache entry. But if string, use that as key.
   * This is so different classes can share the same cache data
   * @return mixed
   */
  public static function getCached($key, $value=false,$args=[],$keyBase=false) {
    if (!$keyBase) {
      $keyBase = static::class;
    }
    //pkdbgtm("Getting cached: key: [$keyBase][$key]");
    if (!array_key_exists($keyBase,static::$_cache)) {
      static::$_cache[$keyBase] = [];
    }
    if (array_key_exists($key,static::$_cache[$keyBase])) {
      //pkdbgtm("Found in xlcache: [$keyBase][$key]");
      return static::$_cache[$keyBase][$key];
    }
    if ($value === false) {#To cache "false", setCached($key,false)
      return false;
    }
    return static::setCached($key,$value,$args,$keyBase);
  }

  public static function setCached($key,$value,$args, $keyBase=false) {
    if (is_callable($value)) {
      $value = call_user_func_array($value,$args);
    }
    if (!$keyBase) {
      $keyBase = static::class;
    }
    if (!array_key_exists($keyBase,static::$_cache)) {
      static::$_cache[$keyBase] = [];
    }
    return static::$_cache[$keyBase][$key] = $value;
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

  /**
   * Ascends the ancestor tree to merge static array definitions. Since re-declaring a static variable 
   * causes problems, each parent/trait needs to have a different variable name - with a common prefix -
   * like `cprefix` - each variant would be `cprefix_foo``
   * 
   */
  public static function getArraysMerged($prefix, $idx=false,$default=null) {
    //pkdebug("Entering garmr for class: ".static::class."; prefix: [$prefix]");
  //public static function combineAncestorAndSiblings($prefix, $idx=false) {
    $akey = $prefix.'_combined';
    $ret = static::getCached($akey);
    if ($ret !== false) {
      return $ret;
    }
    
    $closure = function () use ($prefix, $idx, $default) {
      $ancestors = static::getAncestorArraysMerged($prefix,$idx,$default);
      $siblings = static::getSiblingArraysMerged($prefix,$idx,1,$default);
      //pkdebug(['class'=>static::class, 'prefix'=>$prefix, 'siblings'=>$siblings, 'ancestors'=>$ancestors]);
      //echo "For Class ".static::class."; [$prefix] returning: ";
      /*
      pkdebug('Merging ancsibs: Class: ', static::class, "Prefix:",
          $prefix,"Ancestors: ", $ancestors, "Siblings: ",$siblings);
       * *
       */
      if (ne_array($ancestors) && ne_array($siblings)) {
        //pkdebug(['merged'=>array_merge($ancestors,$siblings)]);
        /*
         pkdebug("Both ancestors & siblings exist - Merged: ",
             array_merge($ancestors, $siblings));
         * 
         */
          return array_merge($ancestors,$siblings);
        }
        if (ne_array($ancestors)) {
        //pkdebug(['ancestors'=>$ancestors]);
          return $ancestors;
        }
        if (ne_array($siblings)) {
        //pkdebug(['siblings'=>$siblings]);
          return $siblings;
        }
        //pkdebug( "Empty Array: \n");

        return [];
    };
    return static::getCached($akey,$closure);
  }
  
  public static function getInstanceArraysMerged($prefix, $idx=false) {
    //pkdebug("Entering garmr for class: ".static::class."; prefix: [$prefix]");
  //public static function combineAncestorAndSiblings($prefix, $idx=false) {
    $akey = $prefix.'_combined';
    $closure = function () use ($prefix, $idx) {
      $ancestors = static::getInstanceAncestorArraysMerged($prefix,$idx);
      $siblings = static::getInstanceSiblingArraysMerged($prefix,$idx);
      if (ne_array($ancestors) && ne_array($siblings)) {
          return array_merge($ancestors,$siblings);
      } else if (ne_array($ancestors)) {
        return $ancestors;
      } else if (ne_array($siblings)) {
          return $siblings;
      }
      return [];
    };
    return static::getCached($akey,$closure);
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
  public static function getAncestorArraysMerged($arrayName, $idx = false,$default=null) {
    $akey = $arrayName."_ancestor";
    $ret = static::getCached($akey);
    if ($ret !== false) {
      return $ret;
    }
    $closure = function() use($arrayName, $idx, $akey, $default) {
      //pkdebug("Making Ancestor Arrays Merged for ".static::class.", akey: $akey");
      //echo ("\nMaking Ancestor Arrays Merged for ".static::class.", akey: $akey\n\n");
      $retArr = [];
      /*
      $convert = function($arr) use ($idx, $default) {
        if (is_string($idx)) {
          return normalizeConfigArray($arr,null,$default); 
        } else {
          return $arr;
        }
      };
       * 
       */
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
        $tmar = $class::$$arrayName;
        $retArr[] = is_string($idx)?  normalizeConfigArray($tmar,null,$default):$tmar; 
            //$convert($class::$$arrayName); #Deliberate double $$
      }
      while ($par = get_parent_class($class)) {
        if (!property_exists($par, $arrayName) ||
          ($par::$$arrayName === false) ) {#If a parent wants stop accension
          break;
        }
        if (($tstArr =$par::$$arrayName) && is_array($tstArr) ) {
          //$retArr[] = $par::$$arrayName;
          //$retArr[] = $convert($tstArr);
          $retArr[] = is_string($idx)?normalizeConfigArray($tstArr,null,$default):$tstArr; 
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
      //pkdebug("After Ancestor Arrays Merged, RESULT:", $mgArr);
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
  public static function getSiblingArraysMerged($prefix, $idx=false, $longer=1, $default=null) {
    //echo "Trying to get SIBLING arrays merged for: ".static::class."\n\n";
    $akey = $prefix."_sibling";
    $ret = static::getCached($akey);
    if ($ret !== false) {
      return $ret;
    }
    $closure = function() use($prefix, $idx, $longer, $default) {
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
          $tomerge[] = is_string($idx)?normalizeConfigArray($val,null,$default):$val; 
          //$tomerge[]=$val;
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
   * which case new values are added & duplicate values removed.
   * If false/assoc, the child/descendent key values REPLACE the ancestor key values
   */
  //  DO I NEED THIS?
  public static function getInstanceAncestorArraysMerged($arrayName, $idx=false) {
    $akey = $arrayName."_InstanceAncestors";
    $closure = function() use($arrayName, $idx, $akey) {
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
    };
    return static::getCached($akey, $closure);
  }

  /** Mostly for casts */
  /*
  public static function getInstanceSiblingArraysMergedX($arrayName, $idx = false) {
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
   * 
   */
  public static function getInstanceSiblingArraysMerged($prefix, $idx=false, $longer=1) {
    //echo "Trying to get SIBLING arrays merged for: ".instance::class."\n\n";
    $akey = $prefix."_siblingInstance";
    $closure = function() use($prefix, $idx, $longer) {
      $ref = new ReflectionClass(static::class);
      $propsArr = $ref->getDefaultProperties();
      $tomerge = [];
      foreach ($propsArr as $key => $val) {
        if (startsWith($key, $prefix, false, $longer) && $val && count($val)) {
          $tomerge[]=$val;
        }
      }
      if (!count($tomerge)) {
        return [];
      }
      $merged = array_merge_array($tomerge);
      return $merged ?: [];
    };
    return static::getCached($akey, $closure);
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


  /** Calls all special trait additional methods, which start with
   * ExtraConstructor[TraitName]
   * @param string $fnpre - the prefix of the extra trait methods to call, like ..
   * "ExtraConstructors" or "_save"
   */
  public static function getExtraMethods($fnpre) {
    if ($fnpre === '__get') {
      //echo "In gem for key: $fnpre";
    }
    $cacheclosure = function() use ($fnpre) {
     // pkdebug("Building constructors for : ".static::class);
     // echo "\nIn The extra constructors closure...\n";
      #Assume only from Traits
      $traitmethods = static::getTraitMethods(static::$trimtraits);
      //$fnpre = 'ExtraConstructor';
      $methods = [];
      foreach ($traitmethods as $traitmethod) {
        if (startsWith($traitmethod, $fnpre, false,true)) {
          $methods[] = $traitmethod;
        }
      }
      //echo "Returning\n";
      //print_r($methods);
      return $methods;
    };

    //static::getCached('ExtraMethods'.$fnpre, $cacheclosure);
    return static::getCached('ExtraMethods'.$fnpre, $cacheclosure);
  }





/** Great way to add trait methods to default methods - just call the
 * the trait methods like "_saveSpecialTrait()"
 * Then in the class that implements the trait, and already has a save method,
 * just call "$this->RunExtraMethods('_save', $opts);"
 * Need to do many versions of this, including one that returns the first non
 * failure - especially for __getTrait() & __setTraits() - next one
 * @param string $fnpre - the prefix of the methods to call
 * @param mixed $attributes - argument to send to methods
 * @return mixed - the attributes
 */
  public function RunExtraMethods($fnpre, ...$args) {
    $methods = static::getExtraMethods($fnpre);
    if ($methods) {
      //pkdebug("Constructors? ", $constructors, "This Class:", get_class($this));
      foreach ($methods as $method) {
        $this->$method(...$args);
      }
    }
    return $args;
  }

  public function saves(...$args) {
    return $this->RunExtraMethods('save',...$args);
  }

  /** These methods must return "failure()", which is an instance of Failure,
   * if they don't succeed - like trait __getSpecTrait($key) {
   *     // if match key, return value, but if not don't call parent, return failure(); 
   * @param type $fnpre
   * @param type $args
   */
  public function firstSuccessExtraMethods($fnpre, ...$args) {
    $methods = static::getExtraMethods($fnpre);
    if ($methods) {
      foreach ($methods as $method) {
        $res = $this->$method(...$args);
        if (!didFail($res)) {
          return $res;
        }
      }
    }
    return failure();
  }

  /** ... but __set doesn't return anything, so the __setTrait methods will have to
   * throw exceptions if no match...
   */
  public function firstSuccessExtraMethodsExceptions($fnpre, ...$args) {
    $methods = static::getExtraMethods($fnpre);
    if ($methods) {
      foreach ($methods as $method) {
        try {
          $this->$method(...$args);
          return;
        } catch (\Exception $e) {
        }
      }
    }
    throw new \Exception("No method succeeded");
  }
  public function __xgets($key) {
    //echo "In ___gest: key $key\n";
    return $this->firstSuccessExtraMethods('__get',$key);
  }

  public function __calls($key,$args) {
    return $this->firstSuccessExtraMethods('__call',$key,$args);
  }

  /** #NO? All the trait __set methods must throw an exception if they
   *  #No? don't succeed
   * Same as w. get - just return failure() if they don succeed
   * @param string $key
   * @param mixed $val
   */
  public function __sets($key,$val) {
    //$this->firstSuccessExtraMethodsExceptions('__set',$key,$val);
    return $this->firstSuccessExtraMethods('__set',$key,$val);
  }
}
