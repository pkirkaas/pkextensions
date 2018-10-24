<?php
namespace PkExtensions\Traits;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use PkExtensions\Models\PkModel;
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
    PkModel::allowall(true);
    Auth::logoutall();
    PkModel::allowall(false);
    return $this->traitLogin($request);
  }
  /*
   * 
   */
  //put your code here
}
