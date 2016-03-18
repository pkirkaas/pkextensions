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
  public function __construct($arg = []) {
    parent::__construct($arg);
    $this->separator = ' ';
  }

  /** Don't trust native "count()"
   */
  public function sizeOf() {
    $i = 0;
    foreach ($this as $item) {
      $i++;
    }
    return $i;
  }

  public function array_keys() {
    return array_keys($this->getArrayCopy());
  }

  public function current() {
    return $this->getIterator()->current();
  }

  public function decomposeArray($x = null) {
    $type=typeOf($x);
    //pkdebug("TYPE: $type");
    if (!$x) return ' ';
    if (!is_array($x)) {
      if (is_scalar($x) || (is_object($x) && method_exists($x,'__toString'))) {
        return $x . $this->separator;
      }
      return ' ';
    }
    $str = $this->separator;
    foreach ($x as $y) {
      $str .= $this->decomposeArray($y).$this->separator;
    }
    return $str;
  }

  public function __toString() {
    //$todump = [];
    $str = $this->separator;
    if (count($this) && is_arrayish($this)) {
      foreach ($this as $key => $item) {
        $str.= ' ' . $this->decomposeArray($item) . $this->separator;
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

}
