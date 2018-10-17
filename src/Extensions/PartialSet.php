<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace PkExtensions;

/**
 * Description of PartialSet
 *
 * @author Paul
 */

/** Make an array of partials to pass to the view, or in fact ANY array of
 * providers that have a String representation (that is __toString if obj) --
 * AND CAN BE ECHOED IN ANY CONTEXT, output text, and beauty part is, the 
 * objects are ONLY EVALUATED WHEN OUTPUT -- so can add an object here, change
 * the content elsewhere, and only evaluated when echoed. I LOVE THIS SIMPLE
 * CLASS I INVENTED!
 */
class PartialSet extends \ArrayObject {
  public $separator = '';
  public $arrayseparator = '';
  /** Settings that are not echoed but can be used to hold values, set opts, etc
   *
   * @var array 
   */
  public $custom_opts = [];
  /*
  public function opt_set($name,$value=null) {
    $this->custom_opts[$name] = $value;
    return $value;
  }
  public function opt_get($name) {
    return keyVal($name,$this->custom_opts);
  }
   * 
   */

  #$something = $this->opt_something;
  /*
  public function __get($name) {
    $optName = removeStartStr($name,'opt_');
    if (!$optName) return parent::__get($name);
    return keyVal($optName,$this->custom_opts);
  }

  #$this->opt_something = $value;
  public function __set($name,$value=null) {
    $optName = removeStartStr($name,'opt_');
    if (!$optName) return parent::__set($name, $value);
    return $this->custom_opts[$optName] = $value;
  }
   * 
   */


  /*
  public function __construct($arg = []) {
    parent::__construct($arg);
    $this->separator = ' ';
  }
   * 
   */

  /** Don't trust native "count()"
   */
  public function sizeOf() {
    $i = 0;
    foreach ($this as $item) {
      $i++;
    }
    return $i;
  }
  public function getArrayCopy() {
    $ret = [];
    foreach ($this as $key=>$val) {
      if ($val instanceOf \ArrayObject) {
        $val = $val->getArrayCopy();
      }
      $ret[$key] = $val;
    }
    return $ret;
  }

  public function array_keys() {
    return array_keys($this->getArrayCopy());
  }

  /** Gets the current VALUE */
  public function current() {
    return $this->getIterator()->current();
  }

  public function key() {
    return $this->getIterator()->key();
  }

  public function debugInfoStr() {
    return print_r($this->debugInfo(),1);
  }

  public function debugInfo() {
    return ['key'=>$this->key(),
        'count'=>$this->count(),
        'sizeOf'=>$this->sizeOf(),
        'array_keys'=>$this->array_keys(),
          ];
  }

  /** Unsets the key (current if null) &
   * returns the value
   * @param type $key
   * @return type
   */
  public function release($key = null) {
    if ($key === null) $key=$this->key();
    if ($key === null) return;
    if ($this->offsetExists($key)) {
      $val = $this->offsetGet($key);
      $this->offsetUnset($key);
      return $val;
    }
  }

  public function decomposeArray($x = null) {
    $type=typeOf($x);
    //pkdebug("TYPE: $type");
    if (!$x) return '';
    if (!is_array($x)) {
      if (is_scalar($x) || (is_object($x) && method_exists($x,'__toString'))) {
        return $x . $this->separator;
      }
      return '';
    }
    $str = $this->separator;
    foreach ($x as $y) {
      $str .= $this->decomposeArray($y).$this->arrayseparator;
    }
    return $str;
  }

  public function __toString() {
    //$todump = [];
    $str = $this->separator;
    if (count($this) && is_arrayish($this)) {
      foreach ($this as $key => $item) {
        if ($key === 'custom_opts') continue;
        $str.= '' . $this->decomposeArray($item) . $this->separator;
      }
    }
    return $str . $this->separator;
  }

  /** Deep object copy. Can do something more clever with __clone() at some
   * point, but for now...
   * @return static: (that is, static in the sense of current class): copy of $this
   */
  public function copy() {
    return unserialize(serialize($this));
  }

  public function prepend($value) {
    $array = (array) $this;
    array_unshift($array, $value);
    $this->exchangeArray($array);
    return $this;
  }
  /** Dump current copy of the string, the key values of THIS partial set
   *  replaced by the key/value pairs in the arguments. THIS CHANGES THE VALUE
   * OF THIS OBJECT - but it can still be used as a template in a loop...
   * @param type $key
   * @param type $val
   */
  public function ds($key,$val) {
    $this[$key] = $val;
    return $this->__toString();
  }

  /** Return a new static, with the keys initialized
   * This is to allow a user to later insert items at arbitrary locations in the
   * arrayObject
   * @param simple|arrayish $keys
   * @return static 
   */
  public static function initKeys($keys=null) {
    $new = new static();
    if (is_simple($keys)) $keys = [$keys];
    if (is_arrayish($keys)) {
      foreach ($keys as $key) {
        $new[$key]=null;
      }
    } 
    return $new;
  }

}
