<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/** Adds the possibility of processing phtml & html files (with the PHP engine) as
 * views; and can also take parameters. 
 * Add in your web middleware group om Http/kernel.php
 */
namespace PkExtensions\Middleware;
use Closure;
use Auth;
class PkAdmin {
  public function handle($request, Closure $next) {
    $user = Auth::user();
    if (!$user || !$user->isAdmin() ) {
      return redirect()->route('/')->with('errors',"Must be an admin");
    }
    return $next($request);
  }
}
