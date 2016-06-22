<?php
namespace PkExtensions\Formatters;
use PkExtensions\PkDisplayValueInterface;
class PkFormatPrecision implements PkDisplayValueInterface{
  public static function displayValue($value=null, $prec=2) {
    if (is_numeric($value)) return number_format($value, $prec);
  }
}
