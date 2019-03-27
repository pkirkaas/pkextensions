<?php
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
namespace PkExtensions\Traits;
use PkExtensions\PkException;
/** Modfies the app config dynamically, during execution, to use the Apache 
 * SetEnv VARIANT environment variable (or, if from Artisan, the passed value
 * The variant file can execute code, & return an array to cusomize config.
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
    //echo "The path: [$vcnfnm]\n";
 #a PHP array of config keys to values ['app.name'=>"Custom Name", ...
    $map = require($vcnfnm);
    foreach ($map as $ckey =>$val) {
      config([$ckey=>$val]);
    }
//pkecho("The New Con? ",config("database.variant"));
  }
}
