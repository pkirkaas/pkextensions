<?php
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
namespace PkExtensions\Traits;
use PkExtensions\PkException;
use PkExtensions\VHandler;
/** Modfies the app config dynamically, during execution, to use the Apache 
 * SetEnv VARIANT environment variable (or, if from Artisan, the passed value
 * The variant file can execute code, & return an array to cusomize config.
 * env.VARIANT.php can do whatever it wants, but some conventions:
 *   It uses the config key "variant" to set custom values; like:
 *   config(['variant.db'=>"vendor_db"]); -- and the config trait will
 *   create a custom Schema/DB connection to the new DB
 *
 */
trait VariantConfigTrait {
  public function variantConfig($variant = null)  {
    variant($variant);
    VHandler::set($variant);
    if (!$variant || !file_exists(base_path("env.$variant.php"))) {
      return;
    }
    $map = require(base_path("env.$variant.php"));
    if ($map && is_array($map)) {
      foreach ($map as $ckey =>$val) {
        config([$ckey=>$val]);
      }
    }
    $this->setDb();
  }

  public function setDb() {
    if ($db = config('variant.db')) {
      $def = config('database.default');
      config(["database.connections.$def.database"=>$db]);
    }
  }
}
