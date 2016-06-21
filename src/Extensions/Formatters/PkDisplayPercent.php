<?php
/**
 * Displays values in Percentage
 */
class PkDisplayPercent implements PkDisplayValueInterface {
  public static function displayValue($value = null) {
    return (100 * $value).'%';
  }
}
