<?php
namespace PkExtensions\Formatters;
use PkExtensions\PkDisplayValueInterface;
class PkFormatPercent implements PkDisplayValueInterface {
  public static function displayValue($value = null) {
    if (is_numeric($value)) return ((int)(100 * $value)).'%';
  }
}
