<?php
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
namespace PkExtensions\Traits;
use PkExtensions\PkException;
/** Modfies the app config dynamically, during execution, to use the Apache 
 * SetEnv VARIANT environment variable (or, if from Artisan, the passed value
 *
 */
trait VariantConfigTrait {
  public function variantConfig($variant = null)  {
    if (!$variant) {
      return;
    }
    if (!is_string($variant)) {
      throw new PkException(["Illegal Type for Variant:", $variant]);
    }
    $vcnfnm = base_path("env.$variant.php");
 #a PHP array of config keys to values ['app.name'=>"Custom Name", ...
    $map = require($vcnfm);
    foreach ($map as $ckey =>$val) {
      config([$ckey=>$val]);
    }
  }
}
