<?php
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
namespace PkExtensions\Traits;
use PkForm;
/** * * @author pkirkaas */
/** Maps id's to texts, for select & output. Assumes a static array like
 *public static $refArrays = 
 *  ['reason_id'=>[1=>"Didn't like it", 2=>"Didn't Fit"], 'brand_id'=>[1=>"Levi's".....
 * Of course uses public static function getRefArrays() 
 * 
 * But it should be changed to be implementable for reference tables
 * as well. Can support abstract methods, so like an interface with some
 * implemented methods
 */
trait RefDisplayTrait {
  public static function getRefArrays($el = null) {
    $merged = static::getArraysMerged("refArrays");
    if ($el) return $merged;
    return $merged[$el] ?? null;
  }
  public static function getRefKeys() {
    return array_keys(static::getRefArrays());
  }
  public function  __getFrefArrVal($key) {
    $el = $key."_id";
    $arr = static::getRefArrays($el);
    if (!$arr) return $this->fail();
    return $arr[$this->$el] ?? null;
  }

  public function dv($el) {
    $arr = static::getRefArrays($el);
    return $arr[$this->$el] ?? null;
  }

  public function selinp($el, $selatts = []) {
    $arr = static::getRefArrays($el);
    if (!$arr || !is_array($arr)) {
      throw new \Exception("Blew it w. RefDisplayTrait & el [$el]");
    }
    return PkForm::select($el,$arr,$this->$el,$selatts);
  }

  //put your code here
}
