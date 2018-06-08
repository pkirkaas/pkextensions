<?php
/*
 * Until I do a better implementation - including this trait provideds an
 * array property "structuredArr", which can be used as a persistent array.
 * It maps to a table field "structured", mediumText, JSON encoded.
 * So, get/set/save "structured" as an array. But access by $this->nosql().
 * Adds a single text field to a model, but used as JSON & provides some methods
 * for using it.
 * 
 * This seems to work -- access the JSON array by '$this->nosql()' - & can
 * assign by:
    $tstjson->nosql()['tomoorowpple']="Horay desery";
 * Can even get array as local var & assign by:
 * 
 *   $prat = &$tstjson->nosql();
 *   $prat['akey'] = "A-Value";
 *   $tstjson->save();
 * 
 *   Will save the array as JSON in the field 'structured'
 */
namespace PkExtensions\Traits;
/**
 * @author pkirkaas
 */
trait PkJsonFieldTrait {
  //public $structuredArr;
  public static $table_field_defs_JsonFieldTrait = [
      'structured' => ['type' => 'mediumText', 'methods'=>['nullable']],
    ];

  /**
   * Get/Set the array from the underlying 'structured' string attribute.
   * @param string|array|null $val - if null, return existing array
   *    if string, try to json-encode to array & return
   *    if array, set to 'structured' to array value & return (also empty array)
   * @return array - 
   */
  public function &nosql(&$val = null) {
    if ($val) { #Set structured
      if (ne_string($val)) {
        $newval = json_encode($val, 1);
        $this->attributes['structured'] = $newval;
      } else {
        $this->attributes['structured'] = $val;
      }
    } else if (($val === []) ||
       empty($this->attributes['structured']) || 
        !$this->attributes['structured']) {
    #Otherwise, clear it (if $val === null) or return it as array
        $this->attributes['structured'] = [];
    } else if (ne_string($this->attributes['structured'])) {
      $this->attributes['structured'] = 
          json_decode($this->attributes['structured'],1);
    } 
    return $this->attributes['structured']; #Should be an array
  }

  /** Can take a json encoded string or array as 'nosql' & initialize
   * @param array $atts
   */
  /*
  public function ExtraConstructorJsonField($atts = []) {
    //$this->structuredArr = json_decode($this->structured,1)?:[];
    $nosql = keyVal('nosql', $atts);
    if ($nosql) {
      $this->nosql($nosql);
    }
    return $atts;
  }
   * 
   */
  public function save(array $opts = []) {
    /*
    if (array_key_exists('structured', $this->attributes) ) {
     if (is_array($this->attributes['structured'])){
      $this->attributes['structured'] = 
          json_encode($this->attributes['structured'],static::$jsonopts);
     } else if (ne_string($this->attributes['structured'])) 
    }
     * 
     */
    $this->structured = 'Goodbye';
    $this->attributes['structured'] = 'Hello';
    unset($opts['structured']);
    pkdebug("Class: ".static::class, "Object:", $this);
    $opts['structured'] = 'In Opts';
    return parent::save($opts);
  }
  /** Initialize or add keys/vals to 'nosql'/'structured', & return 
   * array keys
   * @param string|array $keyArray - array of keys (w. opt values) to set/add
   * @param boolean - default false - add keys/vals. Else, replace all.
   * @return array - existing & new keys
   * @throws \Exception
   */
  public function arrayKeys($keyArray = [], $replace = false) {
    #Keys can be a mixed array, index w. key as value, AND associative with
    if ($replace) {
      $val = [];
      $this->nosql($val);
    }
    if (ne_string($keyArray)) {
      $keyArray = [$keyArray];
    }
    if (ne_array($keyArray)) {
      #key as key & set to an initial value.
      # ['title','weight','gender'=>'female','nationality'=>'american', 'race',..
      foreach ($keyArray as $idx=>$val) {
        if (is_int($idx) && ne_string($val)) {
          $this->nosql()[$val]=null;
        } else if (ne_string($idx)) {
          $this->nosql()[$idx]=$val;
        } else {
          throw new \Exception("Something wrong with key initialization");
        }
      }
    }
    return null;
    return array_keys($this->nosql());
  }

  /** These can set/get array key vals, even if the keys don't already exists*/
  public function getArrayVal($key, $default = null) {
    return keyVal($key, $this->nosql(), $default);
  }
  public function setArrayVal($key,$value) {
    $this->nosql()[$key] = $value;
    return $value;
  }

  /** These can only get/set key values if they already exist */
  public function __get($name) {
    if (!in_array($name, $this->arrayKeys(),1)) {
      return parent::__get($name);
    }
    return $this->nosql()[$name];
  }

  public function __set($name, $value) {
    if ( !in_array($name,$this->keyvals))

    if (!is_array($this->arrayKeys()) || !in_array($name, $this->arrayKeys(),1)) {
      return parent::__set($name, $value);
    }
    return $this->nosql()[$name] = $value;
  }
}
