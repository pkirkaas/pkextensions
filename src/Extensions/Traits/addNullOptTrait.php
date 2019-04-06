<?php
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
namespace PkExtensions\Traits;

/**
 * Optionally adds a null option to select options
 */
trait addNullOptTrait {

  /** For select options - 
   * @param boolean|string $null
   *   false - don't add null option
   *   true | 1 | '1' - Add "None" as null option
   *   string - add the string as null option
   * @param null|array - if array, prepend the result of the _null
   * option to the array
   * @return false|string|array
   */
  
  public static function _null($null=false, $optarr=null) {
    if (($null === 1) || ($null === true) || ($null === '1')) {
      $null= "None";
    } else if (!is_string($null)) {
       $null = false;
    }
    if (!is_array($optarr)) {
      return $null;
    }
    if (!$null) {
      return $optarr;
    }
    return [null=>$null] + $optarr;
  }
}
