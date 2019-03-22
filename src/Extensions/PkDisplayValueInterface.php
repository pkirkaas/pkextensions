<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions;
/**
 * Just requires the Class to implement the static::displayValue($value) method,
 * for outputing values
 * 
 * Implementing classes can have additional args to displayValue, but the default
 * should be null.
 *
 * @author Paul Kirkaas
 */
interface PkDisplayValueInterface {
  public static function displayValue($value=null);
}
