<?php
/** Helper functions for use with Laravel/PkExtensions
 * Depends on function file pklib.php
 * 26 Feb 2016
 * Paul Kirkaas
 */

//use PkLibConfig; #Defined in pklib

#Test update

function setAppLog() {
  PkLibConfig::setSuppressPkDebug(false);
  $logDir = realpath(storage_path().'/logs');
  $logPath = $logDir.'/pkapp.log';
  appLogPath($logPath);
}

##View Helpers
function pk_showcheck($val = null, $checked = "&#9745;", $unchecked ="&#9744;", $styles = []) {
  $defaultstyles = ['font-size'=>'xx-large','margin'=>'auto','color'=>'black', 'text-align'=>'center'];
  $stylestr = '';
  foreach ($defaultstyles as $key => $value) {
    $stylestr.="$key:$value; ";
  }
  //$stylestr = implode (';',$defaultstyles);
  if ($val) return "<div style='$stylestr'> $checked </div>";
   return "<div style='$stylestr'>$unchecked</div>";
}


