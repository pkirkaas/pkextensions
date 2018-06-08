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
  //public $keys = []; #Set in implementing methods
  protected $casts_JsonField = ['structured'=>'array'];
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
  /** Think I have to keep $this->structured a string...
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
   * 
   */
  /*
  public function nosql($val = null) {
    if ($val === []) { #Set structured
      $this->attributes['structured'] = '{}';
    } else if (ne_array($val)) {
      $this->attributes['structured'] = json_encode($val,static::$jsonopts);
    } else if (ne_string($val)) {
      $this->attributes['structured'] = $val;
    }
    return $this->getStructuredAsArray();
  }

  public function getStructuredAsArray() {
    $structured = keyVal('structured', $this->attributes);
    if (ne_string($structured)) {
      return json_decode($structured, 1); #Should be an array
    }
    return [];
  }
   * *
   */
  public function ExtraConstructorJsonField($atts = []) {
    $this->initStructured();
    //pkdebug("This is:", $this);
    $keys = keyVal('keys',$atts,$this->keys);
    $structuredatt = keyVal('structured',$atts,[]);
    

    //$structured = $this->structured();
    $this->setStructured(["From the Constructor"=>"add a bit"]);
    $this->setStructured($structuredatt);
    //$structured['Always'] = "Now";
    print_r(['Constructured'=>$this->structured]);
    if (ne_array($keys)) {
      $this->arrayKeys($keys);
    }
    return $atts;
  }

  public function initStructured() {
    $av = $this->getAttributeValue('structured');
    if (!is_array($av)) {
      $av = [];
    }
    $this->setAttribute('structured',$av);
    print_r (['av'=>$av]);
    /*
    if (!$this->getAttributeValue('structured')) {
      $this->structured = [];
    }
     * 
     */
    return $this->getAttributeValue('structured');;
  }

   
  public function setStructured(Array $value = [], $merge=true) {
    if (!is_array($value) || (!ne_array($value) && $merge)) {
      return $this->structured;
    }
    $struct = $this->getAttributeValue('structured');
    print_r(["In SetStructured, struct"=>$struct,"value"=>$value]);
    if (!$merge || !ne_array($struct)) {
       $this->structured = $value;
    } else if (ne_array($struct)) {
        $this->structured = 
          array_merge($struct, $value);
        print_r(["Merged:"=>$this->structured, 'underlying:'=>$this->attributes['structured']]);
    } else {
        //print_r (['this->structured:'=>$this->getAttributeValue('structured')]);
        $this->structured = [];
    }
    return $this->structured;
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
    $keys = keyVal('keys',$atts,$this->keys);
    if (ne_array($keys)) {
      $this->arrayKeys($keys);
    }
    return $atts;
  }
   * 
   */
  /*
   * 
   */
  //public function save(array $opts = []) {
    /*
    if (array_key_exists('structured', $this->attributes) ) {
     if (is_array($this->attributes['structured'])){
      $this->attributes['structured'] = 
          json_encode($this->attributes['structured'],static::$jsonopts);
     } else if (ne_string($this->attributes['structured'])) 
    }
     * 
     */
  /*
    //$this->structured = 'Goodbye';
    pkdebug("OPTS:", $opts, "\nATTS", $this->attributes);
    //$this->attributes['structured'] = 'Hello';
    //unset($opts['structured']);
    pkdebug("Class: ".static::class, "Object:", $this);
    //$opts['structured'] = 'In Opts';
    return parent::save($opts);
  }
   * 
   */
  /** Initialize or add keys/vals to 'nosql'/'structured', & return 
   * array keys
   * @param string|array $keyArray - array of keys (w. opt values) to set/add
   * @param boolean - default false - add keys/vals. Else, replace all.
   * @return array - existing & new keys
   * @throws \Exception
   */
  /*
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
    return array_keys($this->nosql());
  }
   * 
   */

  //public function &getStructured($val = null) {
  /*
  public function structured($val = null) {
    if ($val === [] || !keyVal('structured',$this->attributes)) { #Set structured
      $this->structured = [];
    }
    if (ne_array($val)) {
      $this->structured = $val;
    } 
    if (ne_string($this->structured)) {
      $this->structured = json_encode($this->structured,1);
    }
    //return $this->structured;
    if (ne_string($this->attributes['structured'])) {
      return json_encode($this->attributes['structured'],1);
    } else if (ne_array($this->attributes['structured'])) {
      return json_encode($this->attributes['structured'],1);
    } else {
      return [];
    }
    return $this->attributes['structured'];
    $attributes = &$this->attributes;
    print_r(['attributes:', $attributes]);
    return $attributes['structured'];
    //return $this->attributes['structured'];;
  }
   * 
   */

  /*
  public function structured() {
    return $this->structured;
  }
   * 
   */


  public function arrayKeys($keyArray = [], $replace = false) {
    $this->initStructured();
    #Keys can be a mixed array, index w. key as value, AND associative with
    if ($replace || !keyVal('structured',$this->attributes)) {
      $this->structured = [];
    }
    if (ne_string($keyArray)) {
      $keyArray = [$keyArray];
    }
    $addArr = [];
    if (ne_array($keyArray)) {
      #key as key & set to an initial value.
      # ['title','weight','gender'=>'female','nationality'=>'american', 'race',..
      foreach ($keyArray as $idx=>$val) {
        if (is_int($idx) && ne_string($val)) {
          /*
          $structured = $this->structured ?: [];
          $structured[$val]= null;
          $this->structured = $structured;
           * *
           */
          $addArr[$val]=null;
        } else if (ne_string($idx)) {
          /*
          $structured = $this->structured ?: [];
          $structured[$idx]= $val;
          $this->structured = $structured;
           * 
           */
          $addArr[$idx]=$val;
        } else {
          throw new \Exception("Something wrong with key initialization");
        }
        $this->setStructured($addArr, ! $replace);
      }
    }
    $struct = $this->getAttributeValue('structured');
    if (!$struct) {
      print_r(["Struct:",$struct]);
    }
    /*
    if (!keyVal('structured',$this->attributes)) {
      $this->structured = [];
    }
     * 
     */
    return is_array($struct)? array_keys($struct):[];
  }
  /** These can set/get array key vals, even if the keys don't already exists*/
  public function getArrayVal($key, $default = null) {
    return keyVal($key, $this->getAttributeValue('structured'), $default);
  }

  public function setArrayVal($key,$value) {
    if (!$this->getAttributeValue('structured')) {
      $this->structured = [];
    }
    $this->structured[$key] = $value;
    return $value;
  }
  /*
   * 
   */

  /** These can only get/set key values if they already exist */
  public function __get($name) {
    if (!in_array($name, $this->arrayKeys(),1)) {
      return parent::__get($name);
    }
    return keyVal($name,$this->getAttributeValue('structured'));
  }
  /*
   * 
   */

  public function __set($name, $value) {
    //if ( !in_array($name,$this->arrayKeys(),1))

    if (!is_array($this->arrayKeys()) || !in_array($name, $this->arrayKeys(),1)) {
      return parent::__set($name, $value);
    }
    return $this->structured[$name] = $value;
  }
  /*
   * 
   */
  /*
  */
}
