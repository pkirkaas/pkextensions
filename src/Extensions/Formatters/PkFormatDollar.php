<?php
namespace PkExtensions\Formatters;
use PkExtensions\PkDisplayValueInterface;
class PkFormatDollar implements PkDisplayValueInterface{
  public static function displayValue($value=null) {
    return dollar_format($value);
  }
}
