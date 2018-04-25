<?php
/*
 * Implement this in your Ajax controller to login w. Ajax.
 * Two keys are required - ['ident'=>$name, 'password'=>$password]
 * 'remember'=>true is optional.
 * Returns false if failed, else the ID of the new logged in user.
 */

namespace PkExtensions\Traits;
use Auth;
use Request;
use App\User;

/**
 *
 * @author paulk
 */
trait AjaxLoginTrait {
  public function login() {
    $data = Request::all();
    $user = User::tryLogin($data);
    if (!$user || !$user instanceOf User) {
      $result = ['status'=>false,'user_id'=>0, 'msg'=>"Didn't match"];
    } else {
      $result = ['status'=>true,'user_id'=>$user->id, 'msg'=>"Welcome"];
    }
    return $this->jsonsuccess($result);
  }

  public function loggedin() {
    $id = User::id();
    if ($id) {
      $result = ['status'=>true,'user_id'=>$id];
    } else {
      $result = ['status'=>false,'user_id'=>false];
    }
    return $this->jsonsuccess($result);
  }
}
