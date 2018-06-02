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
      'array' => ['type' => 'mediumText', 'methods' => ['nullable']],
      //'keys' => ['type' => 'text', 'methods' => ['nullable']],
    ];
  #If $this->keys exists, only those are allowed for __set/__get
  protected $casts = ['array'=>'array', 'keys'=>'array']; 

  public function ExtraConstructorJsonField($atts = []) {
    $keys=keyVal('keys',$atts);
    $this->initializeArray($keys);
  }
  public function inintializeArray($keys) {
    if (!ne_array($keys)) {
      return;
    }
    #Keys can be a mixed array, index w. key as value, AND associative with
    #key as key & set to an initial value.
    # ['title','weight','gender'=>'female','nationality'=>'american', 'race',..
    foreach ($keys as $idx=>$val) {
      if (is_int($idx) && ne_string($val)) {
        $this->array[$val]=null;
      } else if (ne_string($idx)) {
        $this->array[$idx]=$val;
      } else {
        throw new \Exception("Something wrong with key initialization");
      }
    }

  }

  public function getArrayVal($key, $default = null) {
    return keyVal($key, $this->array, $default);
  }
  public function setArrayVal($key,$value) {
    $this->array[$key] = $value;
    return $value;
  }

  public function __get($name) {
    if (in_array($name, array_keys($this->array), 1)) {
      return $this->array[$name];
    }
    return parent::__get($name);
  }

  public function __set($name, $value) {
    if (ne_array($this->keys) &&  !in_array($name,$this->keys,1)) {
      return parent::__set($name, $value);
    }
    return  $this->array[$name]=$value;
  }
}
