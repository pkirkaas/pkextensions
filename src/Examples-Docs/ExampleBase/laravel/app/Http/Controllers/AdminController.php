<?php
namespace App\Http\Controllers;
use PkExtensions\PkController;
use Request;
use DB;
use \Auth;
class AdminController extends PkController{ //put your code here
  public function tools() {
    $user = Auth::user();
    if (!$user->isAdmin()) throw new \Exception("Not an admin!");
    return view('admin.tools');
    
  }
}
