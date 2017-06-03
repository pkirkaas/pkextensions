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
  /**Returns an optionally DIV wrapped formatted dollar string 
   * @param numeric|numeric array $value
   * 
   * Confusingly, $wrap_class can be a class string or true, then the
   * result is wrapped in a div, with 'negative-dollar-value' if negative
   * If $wrap_class is integer, the precision/decimal places
   * If $wrap_class is array, should be keyed with any/all of:
   *   'prec', 'wrap_class', 'hide0'
   * @param int|boolean|array|string $wrap_class
   * @return string dollar format
   */
  public static function displayValue($value = null, $wrap_class = null, $ifempty=null) {
    if (!$value) {
      return $ifempty;
    }
    return dollar_format($value, $wrap_class);
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
  public static function displayValue($value = null, $fmt = null,$ifempty='') {
    //if (!$fmt) $fmt = 'M j, Y';
    if (!$fmt) $fmt = 'y M j';
    if (!$value) {
      return $ifempty;
    }
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
