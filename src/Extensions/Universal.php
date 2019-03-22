<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions;
class Universal {
  public $attributes = [];
  public function __get($key) {
    if (array_key_exists($key, $this->attributes)) return $this->attributes[$key];
    return null;
  }
  public function __set($key, $val) {
    $this->attributes[$key]=$val;
  }

  public function __call($method, $args=null) {
    return null;
  }
  public static function __callStatic($method, $args=null) {
    return null;
  }
}
