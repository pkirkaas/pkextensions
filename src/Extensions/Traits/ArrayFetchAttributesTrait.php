<?php
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
/** For PkModel & PkCollection fetchAttributes - if the value is an array,
 * iterate through the array and fetchAttributes for any value that is not scalar
 */

namespace PkExtensions\Traits;
use PkExtensions\Models\PkModel;
use PkExtensions\PkCollection;

/**
 *
 * @author pkirkaas
 */
trait ArrayFetchAttributesTrait {
  public function arrayFetchAttributes($array,$keys=[],$extra=[]) {
    $ret = [];
    foreach ($array as $key=>$value) {
      if (is_object($value) && method_exists($value, 'fetchAttributes')) {
        $value = $value->fetchAttributes();
      } else if (is_arrayish($value)) {
        $value = $this->arrayFetchAttributes($value);
      }
      $ret[$key] = $value;
    }
    return $ret;
  }
}
