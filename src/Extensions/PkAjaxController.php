<?php
namespace PkExtensions;
use App\Models\User;
use PkExtensions\PkFileUploadService;
use \PkExtensions\Models\PkModel;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use PkExtensions\PkCollection;
use Illuminate\Database\Eloquent\Builder;
use \Request as RequestFacade;
//use \Illuminate\Http\Response;
use Carbon\Carbon;
use \Auth;

/**
 * PkAjaxController - Base Ajax Controller - Performs MANY generic AJAX methods,
 * tuned by params. success, error, submit, delete, fetchattributes, query,
 * toggle - 
 * ALSO - uses custom ExceptionHandler which intercepts exceptions from /ajax/
 * routes & returns them as error responses, so don't need to handle exceptions
 * in any AJAX controller.
 * 
 * TO USE EXCEPTION HANDLER:  Modify default app/Exceptions/Hander.php:
use PkExtensions\Traits\AjaxExceptionTrait;
class Handler extends ExceptionHandler {
  use AjaxExceptionTrait;
  ...
  public function render($request, Exception $exception) {
    return $this->traitRender($request, $exception);
  }
  ...

 * 
 * @author pkirk
 */
abstract class PkAjaxController extends PkController {
  public $data;
  public $me;
  public function __construct($args=null) {
    if (method_exists(get_parent_class(),'__construct')) {
      parent::__construct($args);
    }
    $this->data = request()->all();
    if (class_exists('App\Models\User')) {
      $this->me = Auth::user();
      if (!User::instantiated($this->me)) {
        $this->me = null;
      }
    }
  }

  /** An AJAX controller just has to call $this->jsonsuccess($resp);
   * jsonsucess will return an assoc array, typically:
   * ['success'=>true, 'msg'=>$msg, 'data'=>$data]
   * If $resp is a string, it will be the msg. If $resp is array,
   * success=>true will be added, unless it already is a key. Any 
   * data the caller wants should be keyed by 'data'
   * If it's a complicated msg, send an array; else a string. This
   * will arrify it, json it, & die
   * @param string|array $resp
   * 
   * This way, we can set up automatic handlers if we like
   */


  ################  HAVE TO STANDARDIZE AJAX RESPONSES!  ############
  /**
   * Laravel response()->json($data, $status, $headers, $options):
     * @param  mixed  $data
     * @param  int    $status
     * @param  array  $headers
     * @param  int    $options
   */

  /** Both ->success & ->error call jsonresponse, which returns Laravel/Symfony
   * JSON response object
   * 
   * @param mixed $data - JSONABLE - appears as response.data: if string,
   *    response.data: "string", else the json_encoded value
   * @param int|string|boolean|array $status - default: 200/OK. If true, 200.
   *    if false, 499. If int, that value. If array, [statusCode=>statusText]
   * @param array $headers
   * @param int|boolean $options - json_encode integer options. If true/1, make
   * default JSON Opts as defined in UtilityMethodsTrait
   * @return Laravel/Symfony Response Object - if Axios, if successful,
   *   in then(response) : if error, in catch(error=>error.response)  - if it's
   *   an AJAX error. If it's a JS error, just "error"
   * {
        data: The JSON encoded Response, or just string if just string
        status: integer- the status
        statusText: the status text (can customize as above)
        config: object - Configuration, including XSRF token
        request: the original request
        headers: the response headers
    }
   */
  public function index() {
    $this->data = request()->all();
    $action=keyVal('action',$this->data);
    switch ($action) {
      case 'execute':
          $model = keyVal('model',$this->data);
          $id = keyVal('id',$this->data);
          $method = keyVal('method',$this->data);
          $args = keyVal('args',$this->data);
          $results = keyVal('results',$this->data);
          $obj = $model::find($id);
          if (!is_array($args)) {
            $args = [$args];
          }
          /*
          if (!$obj instanceOf PkModel) {
            return $this->error(["No instance found for data:", $this->data]);
          }
           * 
           */
          $res = call_user_func_array([$obj,$method], $args);
          if ($results) {
            return $this->success($res);
          } else {
            return $this->success();
          }
        default: return $this->error(["Nothing to do with:",$this->data]);
    }
    return $this->error("Unspecified Ajax Error - no matching action  w data:".
        print_r($this->data,1));

  }

  public function jsonresponse($data=null,$status=200,$headers=[],$options=true) {
    return static::sjsonresponse($data, $status,$headers,$options);
  }


  public static function sjsonresponse
      ($data=null,$status=200,$headers=[],$options=true) {
    if (($options === true) || ($options === 1)) { #Default JSON opts - PrettyPrint
      $options = static::$jsonopts;
    }
    $statusText = false;
    //header('content-type: application/json');
    if ($status === true) {
      $statusCode = 200;
    } else if ($status === false) {
      $statusCode = 499;
    } else if (is_array($status)) { # [statusCode=>statusString]
      $statusCode = key($status);
      $statusText = $status[$statusCode];
    } else {
      $statusCode = $status;
    }
    if ($data instanceOf PkModel) {
      //$data = $data->getTableFieldAttributes();
      $data = $data->toArray();
    }
    $response = response()->json($data,$statusCode,$headers, $options);
    if ($statusText) {
      $response->setStatusCode($statusCode, $statusText);
    }
    return $response;
  }

  ///////////  Only return $this->sucess or $this->error - 
  public function success($data="",$status=200,$headers=[],$options=true) {
    return $this->jsonresponse($data,$status,$headers,$options);
  }

  public function error($data='',$status=499,$headers=[],$options=true) {
    return $this->jsonresponse($data,$status,$headers,$options);
  }

  /**
   * Returns an error but also pops up a message to the user
   * @param string|array $data - the message to the user. If an array, can contain
   *   title as well. ['title'=>$title,'msg'=>$msg]
   * @param type $status
   * @param type $headers
   * @param type $options
   * @return type
   */
  public function errormsg($data='',$status=499,$headers=[],$options=true) {
    if (!is_array($data)) {
      $data=['systemmsg'=>"error", "title"=>"Error", "msg" => $data];
    } else {
      $data['systemmsg'] = 'error';
    }
    return $this->error($data, $status, $headers, $options);
  }

  /** Toggles a PkModel boolean field
   * @param id = the instance ID
   * @param model = the model class
   * @param modfield - the field to toggle
   * @return - the instance attributes, with $instance[togglefield] = $togglefield;
   */
  public function toggle() {
    $model = $this->data['model'];
    $id = $this->data['id'];
    $modfield =  $this->data['modfield'];
    $instance = $model::find($id);
    $instance->$modfield = !$instance->$modfield;
    $instance->save();
    $instance->modfield = $modfield;
    return $this->success($instance);
  }

  /** 
   * Sets an instance field (or array of fields?)
   * @param id = the instance ID
   * @param model = the model class
   * @param modfield - the field to set
   * @param value - the value to set it to
   * @return - the instance attributes, with $instance[setfield] = $setfield;
   * 
   */
  public function set() {
    $model = $this->data['model'];
    $id = $this->data['id'];
    $modfield =  $this->data['modfield'];
    $value = keyVal('value',$this->data,'');
    $instance = $model::find($id);
    $instance->$modfield = $value;
    $instance->save();
    $instance->modfield=$modfield;
    return $this->success($instance);
  }

  /** Simpler than attributes (below) - just for 1 model, 1 instance, uses
   * eloquent
   * Can take the same arguments as set/toggle above, with modfield, so can 
   * use in same AJAX function to set & get
   * $id can be a single id or comma-separated list of ids
   */
  public function modelattributes() {
    $model = $this->data['model'];
    $id = $this->data['id'];
    $modfield = keyVal('modfield',$this->data);
    $instance = $model::find($id);
    $instance->model = $model;
    if ($modfield) {
      $modfieldval = keyVal($modfield,$this->data);
      $instance->$modfield = $modfieldval;
    }
    return $this->success($instance);
  }

  /** Could be new or update, 
   * When fetching, should be keys. When submitting, must be field(s) as assoc
   * array of keyname=>value, as "fields"
   */
  public function submit() {
    pkdebug("Enter Submit with:",$this->data);
    console("Enter Submit with:",$this->data);
    $fields = $this->data['fields'] ?? null; //Should be an array
    if (!$fields || !is_array($fields)) {
      throw new PkException(["No fields submitted to submit. Data:",$this->data]);
    }
    $model = $this->data['model'];
    $id = $this->data['id'] ?? null;
    if ($id) { #Updating existing object
      $obj = $model::find($id);
    } else { #It's new - does have an owner?
      $obj = new $model();
    }
    foreach ($fields as $key=>$value) {
      $obj->$key = $value;
    }
    if (!$id) { #Does it have an owner?
      $ownermodel = $this->data['ownermodel'] ?? null; //Optional
      $ownerid = $this->data['ownerid'] ?? null; //Optional
      $owneratt = $this->data['owneratt'] ?? null; //What is relationship name in the owner?
      $foreignkey = $this->data['foreignkey'] ?? null; //Optional
      if ($ownermodel && $ownerid && $attribute) {
        $owner = $ownermodel::find($ownerid);
        $owner->$attribute()->save($object);
      } else {
        if ($ownerid & $foreignkey) {
          $object->$foreignkey = $ownerid;
        }
        $obj->save();
      } #Object should be saved now - return attributes
    } else { #It already exists, just save it
      $obj->save(); 
    }
    $keys = array_keys($fields);
    //return $this->success($obj->fetchAttributes($keys));
    //$ret = $obj->fetchAttributes($keys);
    if ($obj instanceOf Builder) {
      $obj = $obj->get();
    }
    if (($obj instanceOf Collection) && (! $obj instanceOf PkCollection)) {
      $obj = new PkCollection($obj);
    }
    $ret = $obj->fetchAttributes();
    //pkdebug("Leaving Submit with ttsL ",$obj->fetchAttributes());
    //$ret = $this->success($obj->fetchAttributes($keys));
    return $this->success($obj->fetchAttributes());
  }

  /** Fetch attributes as specified by the model, instance id (or IDS), and 
   * model keys
   * @param model -string- the model to search
   * @param id - single id for single instance, list for collection, empty for all - UNLESS
   * @param searchkeys: if set, array of searchkeys=>values for "where"
   * @param orderby array of fields=>asc or desc
   * @param array keys: array of key atts to return, could include relationships. If empty, the default.
   * @param array extra - extra atts, like relationships, so don't have to specify all keys to add a few.
   *   --- that is, if @keys is empty, return all default keys, PLUS those here 
   * 
   * If we have model/id(s) it's easy. Let's extend to finding objects "owned" by others - 
   * Only need 'ownermodel', 'ownerid', & 'attribute'(relationship)
   * @return array - results
   */
  public function fetchattributes() {
    //PkDebug("Enter Fetchattrures with data:", $this->data, "Request Method:", $_SERVER['REQUEST_METHOD']);
    $obj=null;
    $extra = restoreJson(keyVal('extra',$this->data));
    $keys = restoreJson(keyVal('keys',$this->data));
    if (ne_string($keys)) {
      $keys=[$keys];
    }
    if (is_array($keys) && !in_array('id',$keys,1)) {
      $keys[]='id'; #Could be one for an instance, or list for PkCollection
    }
    $orderby = keyVal('orderby',$this->data);
    $model = keyVal('model',$this->data);
    $searchkeys  = keyVal('searchkeys',$this->data);
    if ($model) {
      $id = keyVal('id',$this->data);
      if ($id) {
        $obj = $model::find($id);
      } else if ($searchkeys){
        $obj = $model::multiWhere($searchkeys);
      } else {
        $obj = $model::all();
      }
      if ($obj instanceOf Builder) {
        $obj = $obj->get();
      }
      if ((! $obj instanceOf PkModel) && $orderby) {
        $obj = $obj->multiOrderby($orderby);
      }
    } else { #Don't know this model - search by owner & relationship
      $ownermodel=keyVal('ownermodel', $this->data);
      $ownerid = keyVal('ownerid',$this->data);
      $attribute=keyVal('attribute',$this->data);
      $obj = $ownermodel::find($ownerid)->$attribute;
      $keys[]='totag';
    }
    //pkdebug("TypeOF obj:",typeof($obj));
    if ($obj) {
      if ($obj instanceOf Builder) {
        $obj = $obj->get();
      }
      if (($obj instanceOf Collection) && (! $obj instanceOf PkCollection)) {
        $obj = new PkCollection($obj);
      }
      //pkdebug("TypeOF obj AFTER Conv:",typeof($obj));
      $atts = $obj->fetchAttributes($keys,$extra);
      //pkdebug ("Rerting got atts of: ",$atts);
      return $this->success($atts);
    }
    return $this->success([]);
  }

/** 
 * DEPRECATED - Use "fetchattributes" ideally
 * For Vue or JS or jQuery calls to request model attribute values to 
   * populate templates.
   * Params: 
   * model: the PkModel Name
   * id: The ID for the model OR ARRAY OF IDS
   * method - opt - to call on the model
   * arg - opt - to call w. method
   * method2 - opt
   * arg2 - opt
   * tpl array - optional - a template for the key/values of the attributes
   *   to return, rather than everything - NOT IMPLEMENTED
   * 
   */
  public function attributes() {
    $data = request()->all();
    $model = keyVal('model', $data);
    $method = keyVal('method', $data);
    $arg = keyVal('arg', $data);
    $method2 = keyVal('method2', $data);
    $arg2 = keyVal('arg2', $data);
    $id = to_int(keyVal('id', $data));
    $ids = keyVal('ids', $data); //A comma separated set of instance ids

    $getinstance = function($id) use ($model, $method,$arg,$method2, $arg2) {
      if (!($res=$model::find($id))) {
        return $this->error("Couldn't find an instance of $model with id $id");
      }
      if ( ($res instanceOf PkModel) && !$res->authRead()) {
        return $this->error("Not allowed to see this data");
      }
      if ($method) {
        $res = $res->$method($arg);
      }
      if ($method2) {
        $res = $res->$method2($arg2);
      }
      return $res;
    };

    #The onlything we really need is the model
    if (!$model || !is_subclass_of($model, PkModel::class, 1)) {
      pkdebug("Error: Model: '$model', ID:",$id, "PkModel:", PkModel::class);
      return $this->error("Invalid Model [$model]");
    }
    if ($ids && is_string($ids)) {
      $ids = explode(',',$ids);
      $res = new PkCollection();
      foreach ($ids as $id) {
        $res[]=$getinstance($id);
      }
    } else if ($id) { # $res is a model; if we have $id, we're looking for an instance
      $res = $getinstance($id);
    } else {  //We have a model, not an instance
      $res = $model::$method($arg); #This should have given us an instance
      if ($method2) {
        $res = $res->$method2($arg2);
      }
    }
    if (!$res) {
      return $this->success();
    }
    #$res should be a PKModel or PkCollection of PkModels
    return $this->success($res->getCustomAttributes());
  }

  public function delete() { //Delete anything you own
     // return $this->error("You cant do that!");
    $data = request()->all();
    $model = keyVal('model', $data);
    $id = to_int(keyVal('id', $data));
    $cascade = keyVal('cascade', $data);
    $me = Auth::user();
    $item = $model::find($id);
    /*
    if (!$me->owns($item)) {
      return $this->error("Can't delete that $model");
    }
     */
    $item->delete($cascade);
    return $this->success("Deleted");
  }


  /** Returns key/value reference sets for selects, etc, like {10:"Happy",20:"Sad"}
   * In the simplest case, just pass the refclass as refclass
   * 
   */
  public function refinfo() {
    $data = request()->all();
    $refclass = keyVal('refclass', $data); #Just the base Model name
    $namespace = keyVal('namespace', $data, "App\\References\\"); #Just the base Model name
    $method = keyVal('method', $data,'getKeyValArr');
    $arg  = keyVal('arg', $data);
    $fullclass = $namespace.$refclass;
    $res = $fullclass::$method($arg);
    $json = json_encode($res, $jsonopts );
    $fail = static::json_error();
    if (!$fail) {
      return $this->success($res);
      //return $this->jsonsuccess(['refs'=>$json]);
    }
    return $this->error($fail);
  }

  /** Takes a JSON object with model, methods, parameters & returns the query 
   * results as attributes. The request type should be:
   * 'search' => {model:model,
   *  filters:[ 
   *     [method:method, 
   *        params:[params,...],
   * }
   * @return JSON of the ".getCustomAttributes()" of the result
   */
  public function query() {
    //$query = request()
    $data = request()->all();
    //$search = json_decode(keyVal('search', $data), 1);
    $search = keyVal('search', $data);
    $model = keyVal('model', $search);
    $builder = $model::where('id','>',0); #There is a better way to do this
    $filters = keyVal('filters', $search,[]);
    foreach ($filters as $filter) {
      $method = $filter['method'];
      $params = $filter['params'];
      $builder->$method(...$params);
    }
    $result = $builder->get()->getCustomAttributes();
    return $this->success($result);
  }


  /** Verifies Authentication of alread logged in user, or logs
   * in, or declines
   */
  public function authenticate() {
  if ($me = Auth::user() ) { //Already logged in, return details
    return $this->success(['user_id'=>$me->id]);
  } #Not logged in - do we have the credentials?
    $data = request()->all();
  }

  /** Helper Function for other Ajax Uloads - does the heavy, upload, validation, but
   * doesn't create the object - returns it's details, and allows the caller 
   * method to handle the results. Knows nothing about the model/object, just the file
   * @params assoc array - type(s), validation(s),  
   */
   public function _upload($params=[]) {
     pkdebug("Entered _upload");
     $types = keyVal('types', $params, ['image']);
     $fus = new PkFileUploadService();
     $uploaded = $fus->upload();
     pkdebug("Uploaded: ", $uploaded);
     return $uploaded;
   }

   /** We need lots of info with the upload request, to make sure it's
    * legit. Also, could be a separate object, or just an entry in an
    * existing model
    */
   public function upload() {
     pkdebug("In AJaax upload -very exciting - the params:", $this->data);
     $model= keyVal('model',$this->data);
     $id= keyVal('id',$this->data);
     $obj = $model::find($id);
     $finfo = $this->_upload();
     pkdebug("Returnd FileInfo...",$finfo);
     if ($obj->persistFileInfo($finfo)) return $this->success();
   }


  ///////////////////  LEGACY ONLY!!!  Use Above going forward! //////////////////////
  public function jsonsuccess($resp = [], $status=200, $headers=[],
      $options = JSON_PRETTY_PRINT |  JSON_UNESCAPED_LINE_TERMINATORS | JSON_UNESCAPED_SLASHES) {
    if (!is_array($resp)) {
      $resp=['msg'=>$resp];
    }
    $resp['status'] = keyVal('status',$resp,true);
    //pkdebug("Success, resp:",$resp); 
    return response()->json($resp, $status, $headers, $options);
  }
  /**
  public function xjsonsuccess($msg = []) {
    http_response_code(200); 
    if (!is_array($msg)) {
      if (!is_scalar($msg)) {
        pkdebug("Bad Message:",$msg);
        $msg=['success'=>'true', 'error'=>"Message Type"];
      } else {
        $msg=['data'=>$msg];
      }
    }
    if (!is_array($msg)) {
      pkdebug("Bad Message:",$msg);
      $msg=['success'=>true, 'error'=>"Message Type"];
    }
    die(json_encode($msg,$this->jsonopts));
  }
   */

  public function jsonerror($resp = [], $status=500,
      $headers=['HTTP/1.1 499 PkCustom AJAX Request Error Message'],
      $options = JSON_PRETTY_PRINT |  JSON_UNESCAPED_LINE_TERMINATORS) {
    if ($resp instanceOf \Exception) {
      $resp = $resp->__toString;
    }
    if (!is_array($resp)) {
      $resp=['msg'=>$resp];
    }
    $resp['status'] = keyVal('status',$resp,false);
    return response()->json($resp, $status, $headers, $options);
  }

    

  /** Some simple AJAX helpers */
  /*
  public function ajax_header($args = null) {
    header('content-type: application/json');
  }
   */

  /** An AJAX controller just has to call $this->ajaxsuccess($msg);
   * If it's a complicated msg, send an array; else a string. This
   * will arrify it, json it, & die
   * @param string|array $msg
   */
  public function ajaxsuccess($msg = []) {
    if (!is_array($msg)) {
      $msg=['success'=>$msg];
    }
      die(json_encode($msg));
  }

  /** If msg is just a string, makes an array ['error'=>$msg], BUT ALSO 
   * sets the response code to 499 - my custom error code, handled by jQuery
   * @param string|array $msg
   */
  public function ajaxerror($msg = []) {
    //http_response_code(499);
    //http_response_code(401);
    header('HTTP/1.1 499 Custom AJAX Request Error Message');
    if (!is_array($msg)) {
      $msg=['error'=>$msg];
    }
      die(json_encode($msg));
  }
/////////////////  END  LEGACY //////////////////
}


    



