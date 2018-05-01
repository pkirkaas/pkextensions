<?php
namespace PkExtensions\Middleware;
use Closure;
class PkAddPhtml {
  public function handle($request, Closure $next) {
    app()['view']->addExtension('html', 'php');
    app()['view']->addExtension('phtml', 'php');
    return $next($request);
  }
}
