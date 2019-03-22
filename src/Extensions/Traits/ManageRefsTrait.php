<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions\Traits;

use PkExtensions\Models\PkModel;
use PkExtensions\PkRefManager;

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of ManageRefsTrait
 *
 * @author Paul
 */
trait ManageRefsTrait {
  public static $allrefs = [];
  public $phase = 1; #1,2,or 3, whether the questions are viewed, edited or searched
  public function getAllRefs($refBase = 'App\\Models\\') {
    $refmap = [];
    $attributenames = static::getStaticAttributeNames();
    //pkdebug($attributenames);
    foreach ($attributenames as $attributename) {
      $row = $this->getAttributeDetails($attributename);
      if (!is_array($row)) continue;
      $refmap[$attributename] = $row;
    }
    return $refmap;
  }
  public function getAttributeDetails($attributename, $refBase =  'App\\Models\\') {
    if (!($base = removeEndStr($attributename, '_ref'))) return false;
    $modelname= static::baseToRefClassname($base);
    $namespacedmodelname = $refBase . $modelname;
    if (!class_exists($namespacedmodelname)) return false;
    $row = ['base' => $base,
          'fieldname' => $attributename,
           'value' => $this->$attributename,
          'refclassname' => $modelname,
          'namespacedmodelname' => $namespacedmodelname,
          'display' => $namespacedmodelname::displayValue($this->$attributename),
          'label' => $namespacedmodelname::displayLabel($this->phase),
          'refArr' => $namespacedmodelname::getRefArr(),

      ];
    return $row;
  }

  public static function fieldNameToBase($fieldname) {
     return removeEndStr($fieldname, '_ref');
  }
  public function getDisplay($fieldname) {
    $refClass = static::attributeToRefClassname($fieldname);

    
  }
  
  public static function getDetails($model, $key, $idxlabel) {
    
  }

  public static function attributeToRefClassname($attribute) {
    $base = static::fieldNameToBase($attribute);
    if (!$base) return false;
    return static::baseToRefClassName($base);
  }
  public static function baseToRefClassname($base) {
    return "Ref" . ucfirst($base);
  }

}
