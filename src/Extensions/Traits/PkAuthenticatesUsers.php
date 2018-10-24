<?php
namespace PkExtensions\Traits;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Auth;

/**
 *
 * @author pkirkaas
 */
trait PkAuthenticatesUsers {
  use AuthenticatesUsers {
    login as traitLogin;
  }

  public function login (Request $request) {
    Auth::logoutall();
    return $this->traitLogin($request);
  }
  /*
   * 
   */
  //put your code here
}
