<?php
namespace PkExtensions;
use App\Models\User;
use \Request;
use \Auth;

/**
 * PkAjaxController - Base Ajax Controller - Doesn't do much...
 * @author pkirk
 */
abstract class PkAjaxController extends PkController {
  public $data;
  public $me;
  public function __construct() {
    if (method_exists(get_parent_class(),'__construct')) {
      parent::__construct();
    }
    header('content-type: application/json');
    $this->data = request()->all();
    if (class_exists('App\Models\User')) {
      $this->me = Auth::user();
      if (!User::instantiated($this->me)) {
        $this->me = null;
      }
    }
  }

  /** An AJAX controller just has to call $this->success($msg);
   * If it's a complicated msg, send an array; else a string. This
   * will arrify it, json it, & die
   * @param string|array $msg
   */
  public function success($msg = []) {
    if (!is_array($msg)) {
      $msg=['success'=>$msg];
    }
      die(json_encode($msg));
  }

  /** If msg is just a string, makes an array ['error'=>$msg], BUT ALSO 
   * sets the response code to 499 - my custom error code, handled by jQuery
   * @param string|array $msg
   */
  public function error($msg = null) {
    //http_response_code(499);
    //http_response_code(401);
    header('HTTP/1.1 499 Custom AJAX Request Error Message');
    if (!is_array($msg)) {
      $msg=['error'=>$msg];
    }
    die(json_encode($msg));
  }

}
