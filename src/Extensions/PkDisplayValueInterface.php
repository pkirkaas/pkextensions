<?php
namespace PkExtensions;
/**
 * Just requires the Class to implement the static::displayValue($value) method,
 * for outputing values
 *
 * @author KIRKP010
 */
interface PkDisplayValueInterface {
  public static function displayValue($value=null);
}
