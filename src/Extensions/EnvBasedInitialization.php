<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/** Add to Http/Kernel.php */
namespace PkExtensions;
/** Use this middlware to do any environment specific setup */
use Closure;
use PkLibConfig; #Defined in pklib
class EnvBasedInitialization {
  public function handle($request, Closure $next) {
    $debug = config('app.debug', true);  
    $appEnv = config('app.env','local');  
    if (isHttps()) {
      $_SERVER['HTTPS'] = 'on';
    }
    if ($debug && (($appEnv === 'local') ||
      ($appEnv === 'dev') || ($appEnv === 'development'))) { #Enable writing pkdebug to log file
      PkLibConfig::setSuppressPkDebug(false);
      $logDir = realpath(storage_path().'/logs');
      $logPath = $logDir.'/pkapp.log';
      appLogPath($logPath);
    }
    return $next($request);
  }
}
