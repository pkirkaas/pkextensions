<?php
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
namespace PkExtensions\Traits;
use PkExtensions\Interfaces\KeyValInterface;
use PkForm;
/** To avoid making a separate PkReference class for EACH little reference lookup - 
 * Models can have static $refArray arrays that map id's to texts,
 * for select & output. Assumes a static array like
 * public static $refArrays = 
 *  ['reason_id'=>[1=>"Didn't like it", 2=>"Didn't Fit"], 'brand_id'=>[1=>"Levi's".....
 * Of course uses public static function getRefArrays() 
 * 
 * So if "$this->reason_id === 2", "$this->reason" returns "Didn't Fit"

 * 
 * THIS DEPENDS ON THE NEW IMPLEMENTATION OF TRAIT __gets THAT GUARANTEE return
 * of Failure class to allow parent "get" 
 * 
 * But it should be changed to be implementable for reference tables
 * as well. Can support abstract methods, so like an interface with some
 * implemented methods
 * 
 * 
 */
trait RefDisplayTrait {
  use addNullOptTrait;

/*Returns an assoc array of assoc arrays for Vue selects
 * @param boolean|string|array_idx|array_assoc $keys:
 *   false: No Null option, all keys
 *   true|1 : null option == "None", all keys
 *   string: null option == string, all keys
 *   array_idx - array of keys/att names, no null option
 *   array_assoc: keyed by att/key name => $null
 * @param - if $keys array_idx, the default for all the null option displays
 * @param boolean: return json encoded?
 * @return array like: 
 *   ['method_id' => [
 *   ['value'=>$value1, 'label'=>$label1],
 *   ['value'=>$value2, 'label'=>$label2],
 *   ]

 *  */
  public static function mkVueSelectArrays($keys=false,$null=false, $json=false) {
      //$options = static::mkIdxRefArr($null);
      //$refArrs = static::getDvRefArrays(true);
      $refArrs = static::getAllRefsMerged(true);
      if (!$keys) {
         if(!$json) return $refArrs;
         return json_encode($refArrs,UtilityMethodsTrait::$jsonopts);
      } 
      $ret = [];
      if (is_scalarish($keys)) {
        $null = static::_null($keys);
        $keys = array_keys($refArrs);
      }
      if (!$keys || !is_array($keys)) {
         if(!$json) return [];
         return json_encode([],UtilityMethodsTrait::$jsonopts);
      }
      if (is_array_idx($keys)) {
        $assocKeys = [];
        foreach ($keys as $key) {
          $assocKeys[$key] = $null;
        }
      }
      if (is_array_assoc($keys)) {
        $assocKeys = $keys;
      }
      foreach ($assocKeys as $key => $anull) {
        if (array_key_exists($key,array_keys($refArrs))) {
          $ret[$key]=static::_null($anull,$refArrs[$key]);
        }
      }
      if (!$json) return $ret;
      return json_encode($ret,UtilityMethodsTrait::$jsonopts);
  }

  /** Just above, but already JSON's it */
  public static function vueSelectOptsArr($keys=false,$null=false) {
    return static::mkVueSelectArrays($keys, $null, true);
  }

  /** Gets the subset of the Model's DisplayValue fields that are mapped to
   * Reference classes (actually, anything that implements KeyValInterface)
   * Keyed by field name => RefModel name - do another call to get the values
   * @return associative array [att1 => RefCls1, att2=>RefCls2...]
   */ 
  public static function getRefDvs() {
    if (!property_exists(static::class,'display_value_fields')){
      return [];
    }
    $dvfs = static::getArraysMerged('display_value_fields');
    $keyValDVs = [];
    foreach ($dvfs as $att => $dv) {
      //if (is_string($dv) && class_exists($dv) && $dv::implements(KeyValInterface::class)) {
      if (is_string($dv) && class_exists($dv) && does_implement($dv,KeyValInterface::class)) {
        $keyValDVs[$att] = $dv;
      }
    }
    return $keyValDVs;
  }

  /** Takes the result of getRefDvs above with the ref class names, & actually
   * returns the reference values = 2 ways: either as refIdxs - like:
   * if $idx TRUE:
   * ['method_id'=>[['key'=>$key1,'value'=>$val1],['key'=>$key2,'value'=>$val2],...
   * if $idx false:
   * ['method_id'=>[$key1=>$val1, $key2=>$val2,...
   */
  public static function getDvRefArrays( $idx = false,  $keylabel='value', $valuelabel='label') {
    $ret = [];
    $refDvs = static::getRefDvs();
    foreach ($refDvs as $key => $refCls) {
      $ret[$key] = $idx ? $refCls::mkIdxRefArr(false, $keylabel, $valuelabel)
          : $refCls::getKeyValArr(false);
    }
    return $ret;
  }



  /** Gets the local static::$refArrays defined in the model itself, keyed by
   * by field name
   */
  public static function getLocalRefArrays($idx = false, $keylabel='value',
      $valuelabel = 'label') {
    if (!property_exists(static::class,"refArrays")) {
      return [];
    }
    $merged = static::getArraysMerged("refArrays");
    if (!$idx) return $merged;
    $ret = [];
    foreach ($merged as $key => $refArr) {
      $idxarr = [];
      foreach ($refArr as $rkey => $rval) {
        $idxarr[] = [$keylabel=>$rkey, $valuelabel=>$rval];
      }
      $ret[$key] = $idxarr;
    }
    return $ret;
  }

  public static function getAllRefsMerged($idx=false,$keylabel='value',$valuelabel='label'){ 
    $merged = array_merge(static::getLocalRefArrays($idx, $keylabel, $valuelabel),
        static::getDvRefArrays($idx, $keylabel, $valuelabel));
    return $merged;
  }

  /** Takes an attribute name, like 'method_id' & returns the value array
   * 
   * @param string $el - attribute name 'xxxx_id'
   * @param boolean|string $null - whether to return a null option - 
   *   if false, no
   *   if true || 1, [null => "None"]
   *   if string ("No Selection") [null => $null]
   *    
   * @return type
   */
  public static function getRefValArr($el, $null=false) {
    $merged = static::getAllRefsMerged();
    $optarr = $merged[$el] ?? null;
    if (!$optarr || !is_array($optarr)) return null;
    return static::_null($null, $optarr);
  }




  public static function getRefKeys() {
    return array_keys(static::getAllRefsMerged());
  }


  public function  __getFrefArrVal($key) {
    //return $this->fail();
    $el = $key."_id";
    //$arr = static::getAllRefsMerged($el);
    $arr = static::getAllRefsMerged()[$el] ?? null;
    if (!$arr) return $this->fail();
    return $arr[$this->$el] ?? null;
  }
  /*
   * 
   */

  public function dv($el) {
    $arr = static::getAllRefsMerged($el);
    return $arr[$this->$el] ?? null;
  }

  public function selinp($el, $null=false, $selatts = []) {
    $arr = static::getRefValArr($el,$null);
    if (!$arr || !is_array($arr)) {
      throw new \Exception("Blew it w. RefDisplayTrait & el [$el]");
    }
    return PkForm::select($el,$arr,$this->$el,$selatts);
  }

  //put your code here
}
