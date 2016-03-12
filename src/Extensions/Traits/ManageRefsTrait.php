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
   public function getAllRefNames($refBase='App\\Models\\') {
     $refmap = [];
      $attributenames = static::getStaticAttributeNames();
      pkdebug($attributenames);
      foreach ($attributenames as $attributename) {
        if (!($base = removeEndStr($attributename,'_ref'))) continue;
        $row = ['base' => $base,
                'fieldname' => $attributename,
                 'modelname' => PkRefManager::baseToModelname($base),
                 'namespacedmodelname' => $refBase.PkRefManager::baseToModelname($base),
            ];
        $refmap[] = $row;
      }
      return $refmap;
   }
}
