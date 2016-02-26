<?php
/** Helper functions for use with Laravel/PkExtensions
 * Depends on function file pklib.php
 * 26 Feb 2016
 * Paul Kirkaas
 */

//use PkLibConfig; #Defined in pklib

function setAppLog() {
  PkLibConfig::setSuppressPkDebug(false);
  $logDir = realpath(storage_path().'/logs');
  $logPath = $logDir.'/pkapp.log';
  appLogPath($logPath);
}
