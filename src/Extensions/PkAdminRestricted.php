<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/** Restrict to Admins */
namespace PkExtensions;
use Closure;
use Illuminate\Support\MessageBag;
use Auth;
class PkAdminRestricted {
  public function handle($request, Closure $next) {
    $user = Auth::user();
    if ( !is_object($user) || !$user->isAdmin()) {
      session()->flash('errors',new MessageBag(['error'=>"You need to be an admin to do that"]) );
      return redirect('/');
    }
    return $next($request);
  }
}
