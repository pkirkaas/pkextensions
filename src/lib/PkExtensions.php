<?php
/* This file is INCLUDED in all installs, so we don't have to use different
 * files for every little class. This has a collection of classes, in the
 * PkExtensions namespace
 */
namespace PkExtensions\DisplayValue;
use PkExtensions\PkDisplayValueInterface;

class checkBox implements PkDisplayValueInterface {
  public static function displayValue($value = null) {
    if ($value) return '&#9745;';
    return  '&#9744;';
  }
}

class DollarFormat implements PkDisplayValueInterface {
  public static function displayValue($value = null) {
    return dollar_format($value);
  }
}
class PercentFormat implements PkDisplayValueInterface {
  public static function displayValue($value = null) {
    if (!$value) return '';
    $value = to_int($value * 100);
    return "$value%";
  }
}

class DateFormat implements PkDisplayValueInterface {
  public static function displayValue($value = null, $fmt = null) {
    if (!$fmt) $fmt = 'M j, Y';
    return friendlyCarbonDate($value, $fmt);
  }
}

/** Returns "Yes" for true value, "No" or "" otherwise */
class YesNo implements PkDisplayValueInterface {
  /**
   * 
   * @param mixed $value
   * @param boolean $nullisno - if true, return empty string for null value
   * @return string 'Yes','No', ''
   */
  public static function displayValue($value = null, $nullisno=null) {
    if ($value) return "Yes";
    if (!$nullisno && (is_null($value) || ($value === ''))) return '';
    return "No";
  }
}
