<?php
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
/** Finds a reference array somehow (RefManager, but should be
 *  usable for DB model refs as well)
 */

namespace PkExtensions\Traits;

/**
 *
 * @author pkirkaas
 */

////////////   DEPRECATED !!! DO NOT USE !!!!!!!!!!!!!!
trait TraitDataToRefs {
  /**Largely for Vue select structure trivially
   * Creates an array for attributes that are ref_id keys
   * @param array $data - data from the object to incorporate, for
   * all the info a select needs, including current selection, and 
   * allowing null results. Simplest: [$name=>$value], but maybe
   * ['name'=>$aname,'value'=>$aval] is better. 
   * Optionally, [Array 'params'=>$params] - just send them along
   *   Could have 'label', display classes, attributes, multiselect=true
   *   input-type: regular, ajaxinit, ajaxasync
   *   So $aval could also be json or array
   * 
   * @param Boolean|string|array- $defaults - Overloaded. If
   *   scalarish, just determines whether to show null option,
   *   If array, it's an array of default values to be applied
   *   to "params". In this case, "$null could be one of the fields
   *     
   *   if falsy, don't allow emtpy choice
   *   if True, use the devaufault "None" as the choice label
   *   If string, use $null as the empty choice label
   * 
   */
 /** The return is like:
  * {
  "name": "industry_id",
  "value": 360,
  "params": [],
  "options": {
    "10": "Accountants",
    "20": "Advertising/PR",
    "30": "Aerospace, Defense",
  }
}
  * @param type $data
  * @param type $null
  * @return type
  */ 
  /*
  public static function dataRefSelectArr($data=[], $defaults=false){
    $null=0;
    $name=$data['name'] ?? null;
    $value = $data['value'] ?? null;
    $params = $data['params'] ?? [];
    if (is_array($defaults)) {
      $params= array_merge($defaults,$params );
      $null = $params['null'] ?? null;
    } else {
      $null = $defaults;
    }

    $options = static::mkVueSelectArray($null); #Array of keys to disp vals
    return [
        'name'=>$name,
        'value'=>$value, //The $ref index
        'params' =>$params, //(label, input & label & wrap classes
        'options'=>$options,
        ];
  }
   * 
   */
  
  //put your code here
}
