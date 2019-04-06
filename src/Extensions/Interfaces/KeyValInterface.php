<?php
namespace PkExtensions\Interfaces;
/** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
/**
 * Promises that the class can return key/value pairs
 */
interface KeyValInterface {
  public static function mkIdxRefArr($null=false, $keylabel='value', $valuelabel='label');
  public static function getKeyValArr($null=false);
}
