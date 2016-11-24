<?php
namespace PkExtensions\Formatters;
use PkExtensions\PkDisplayValueInterface;
class PkFormatDollar implements PkDisplayValueInterface{
  /** See docs for dollar_format for $opts */
  public static function displayValue($value=null, $opts = null) {
    return dollar_format($value, $opts);
  }
}
