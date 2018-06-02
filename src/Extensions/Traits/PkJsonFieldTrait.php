<?php
/*
 * Adds a single text field to a model, but used as JSON & provides some methods
 * for using it.
 */
namespace PkExtensions\Traits;
/**
 * @author pkirkaas
 */
trait PkJsonFieldTrait {
  public static $table_field_defs_JsonFieldTrait = [
      'structured' => ['type' => 'mediumText', 'methods'=>['nullable'],'conversion'=> 'array'],
      //'nosql' => ['type' => 'mediumText'],
      //'tstatt'=>['type'=>'string', 'methods'=>['default'=>"'hello'"]],
      //'keys' => ['type' => 'text', 'methods' => ['nullable']],
    ];
  #If $this->keys exists, only those are allowed for __set/__get

  public function ExtraConstructorJsonField($atts = []) {
    pkdebug("In the extra constructor?");
  //  $this->casts = ['structured'=>'array', 'keys'=>'array']; 
    //$keys=keyVal('keys',$atts);
    //$this->initializeArray($keys);
  }

  /*
  public function setStructuredAttribute($value) {
    if (!$value) $value = [];
    $this->attributes['structured'] = json_encode($value, static::$jsonopts);
  }

  public function getStructuredAttribute($value) {
    $decoded = json_decode($value,true);
    if (!$decoded) $decoded =[];
    return $decoded;
  }
   * 
   */
  /*
   * 
   */
    
  public function initializeArray($keys) {
    if (!ne_array($keys)) {
      return;
    }
    #Keys can be a mixed array, index w. key as value, AND associative with
    #key as key & set to an initial value.
    # ['title','weight','gender'=>'female','nationality'=>'american', 'race',..
    foreach ($keys as $idx=>$val) {
      if (is_int($idx) && ne_string($val)) {
        $this->structured[$val]=null;
      } else if (ne_string($idx)) {
        $this->structured[$idx]=$val;
      } else {
        throw new \Exception("Something wrong with key initialization");
      }
    }

  }

  public function getArrayVal($key, $default = null) {
    return keyVal($key, $this->structured, $default);
  }
  public function setArrayVal($key,$value) {
    $this->structured[$key] = $value;
    return $value;
  }

  /*
  public function __get($name) {
    if ($name === 'structured') {
      return parent::__get($name);
    }
    if (in_array($name, array_keys($this->structured), 1)) {
      $structured = $this->structured;
      return keyVal($name,$structured);
    //  return $this->structured[$name];
    }
    return parent::__get($name);
  }

  public function __set($name, $value) {
    if ($name === 'structured') {
      return parent::__set($name,$value);
    }
    /*
    if (ne_array($this->keys) &&  !in_array($name,$this->keys,1)) {
      return parent::__set($name, $value);
    }
     * 
    $structured = $this->structured;
    $structured[$name] = $value;
    $this->structured = $structured;
    return  $this->structured;
    //return  ($this->structured)[$name]=$value;
  }
  */
}
