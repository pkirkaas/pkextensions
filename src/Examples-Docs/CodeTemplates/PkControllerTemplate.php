<?php
namespace App\Http\Controllers;
use PkExtensions\PkController;
use Auth;
use App\Models\User;
use App\Models\Client;
use Request;
use DB;
/** Common functions/actions for Lenders and Borrowers -  UserController */
class ClientController extends PkController {
  public function viewprofile(Client $client) {
    $user = Auth::user();
    if ($client->user->is($user)) return view('client.viewprofile',['client'=>$client]);
    return $this->error("Not your client!");
  }
  public function editprofile(Client $client) {
    $user = Auth::user();
    if (!$client->user->is($user)) return $this->error("Not your client!");
    $this->processSubmit(['pkmodel'=>$client]);
    return view('client.editprofile',['client'=>$client]);
  }
  public function viewhistory(Client $client) {
    $user = Auth::user();
    if ($client->user->is($user)) return view('client.viewhistory',['client'=>$client]);
    return $this->error("Not your client!");
  }
}
