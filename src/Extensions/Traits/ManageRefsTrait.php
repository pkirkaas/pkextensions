<?php

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
  public static $phase = 1; #1,2,or 3, whether the questions are viewed, edited or searched
  public function getAllRefs($refBase = 'App\\Models\\') {
    /*
    $myclass = static::class;
    if (array_key_exists($myclass,static::$allrefs)) {
      return static::$allrefs[$myclass];
    }
     */
    $refmap = [];
    $attributenames = static::getStaticAttributeNames();
    pkdebug($attributenames);
    foreach ($attributenames as $attributename) {
      if (!($base = removeEndStr($attributename, '_ref'))) continue;
          $modelname= static::baseToModelname($base);
          $namespacedmodelname = $refBase . $modelname;
      $row = ['base' => $base,
          'fieldname' => $attributename,
          'modelname' => $modelname,
          'namespacedmodelname' => $namespacedmodelname,
          'display' => $namespacedmodelname::displayValue($this->$attributename),
          'label' => $namespacedmodelname::displayLabel(static::$phase),

      ];
      $refmap[] = $row;
    }
    //static::$allrefs[$myclass]=$refmap;
    return $refmap;
  }
  
  public static function getDetails($model, $key, $idxlabel) {
    
  }

  public static function baseToModelname($base) {
    return "Ref" . ucfirst($base);
  }

}
