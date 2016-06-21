<?php
namespace PkExtensions\Formatters;
use PkExtension\PkDisplayValueInterface;
class PkDisplayDollar implements PkDisplayValueInterface{
  public static function displayValue($value=null) {
    return dollar_format($value);
  }
}
