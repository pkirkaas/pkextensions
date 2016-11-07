<?php
namespace App\Http\Controllers;
use PkExtensions\PkController;
use Auth;
use App\Models\User;
use Request;
use DB;
/** Common functions/actions for Lenders and Borrowers -  UserController */
class UserController extends PkController {

  public function editprofile() {
    $user = Auth::user();
    $this->processSubmit(['pkmodel'=>$user]);
    return view('user.editprofile',['user'=>$user]);
  }
  public function viewprofile() {
    $user = Auth::user();
    return view('user.viewprofile',['user'=>$user]);
  }

}
