<?php
namespace App\Http\Controllers;
use PkExtensions\PkController;
use PkExtensions\References\ZipRef;
use App\Models\User;
use App\Models\Contact;
use Request;
use DB;
use Auth;
use Mail;
/**
 * Description of IndexController
 *
 * @author Paul Kirkaas
 */
class IndexController extends PkController{
  public function index() {
    return view('index.index');
  }

  public function contact() {
    $me = Auth::user();
    if ($me) $inits=['user_id'=>$me->id];
    else $inits = [];
    if ($this->processSubmit(['pkmodel'=> new Contact(), 'inits'=>$inits])) {
      return $this->message("<h1>Thank you for your message!</h1><h2>We'll get back to you shortly if you asked for a response.</h2>");
    }
      
    return view('index.contact');
  }


  public function about() {
    return view('index.about');
  }

}
