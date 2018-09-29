<?php
/** Adds the possibility of processing phtml & html files (with the PHP engine) as
 * views; and can also take parameters. 
 * Add in your web middleware group om Http/kernel.php
 */
namespace PkExtensions\Middleware;
use Closure;
class PkAddPhtml {
  public function handle($request, Closure $next) {
    app()['view']->addExtension('html', 'php');
    app()['view']->addExtension('phtml', 'php');

    $segs = $request->segments();
    if ($segs && is_array($segs) && ($segs[0]==='ajax')) {
      appLogReset(false);
    }
    return $next($request);
  }
}
