<?php
/* This file is INCLUDED in all installs, so we don't have to use different
 * files for every little class. This has a collection of classes, in the
 * PkExtensions namespace
 */
namespace PkExtensions;

class checkBox implements PkDisplayValueInterface {
  public static function displayValue($value = null) {
    if ($value) return '&#9745;';
    return  '&#9744;';
  }
}
