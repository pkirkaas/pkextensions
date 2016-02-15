<?php
/** Add to Http/Kernel.php */
namespace App\Http\Middleware;
/** Use this middlware to do any environment specific setup */
use Closure;
use PkLibConfig; #Defined in pklib
class EnvBasedInitialization {
  public function handle($request, Closure $next) {
    $debug = env('APP_DEBUG');  
    $appEnv = env('APP_ENV');  
    if (isHttps()) {
      $_SERVER['HTTPS'] = 'on';
    }
    if ($debug && (($appEnv === 'local') || ($appEnv === 'dev'))) { #Enable writing pkdebug to log file
      PkLibConfig::setSuppressPkDebug(false);
      $logDir = realpath(__DIR__.'/../../../../logs');
      $logPath = $logDir.'/app.log';
      appLogPath($logPath);
    }
    return $next($request);
  }
}
