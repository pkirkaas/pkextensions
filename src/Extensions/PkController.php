<?php
/**
 * PkController - Base Controller that provides some common
 * methods
 *
 * @author Paul.Kirkaas@gmail.com
 */
namespace PkExtensions;
use App\Http\Controllers\Controller;
use Illuminate\Support\MessageBag;
use PkExtensions\Models\PkModel;
use Request;
use \Exception;
use \Closure;
class PkController extends Controller {

  /** Verify if we should process this submit, called by $this->processSubmit();
   * If method not a POST, return false. Otherwise, check the submit button name and value
   * $opts are an array of params:
   * @param string $submitname - default 'submit' - The name of the POST key to check
   * @param string $submitvalue - default NULL - If you don't want to check on the submittd key/value, leave value out
   * @return boolean - true if we should process the post, else false if it fails a test
   */
  public function shouldProcessSubmit($opts = null) {
    if (Request::method() !== 'POST') return false;
    if (!$opts ) return true;
    if ($opts instanceOf PkModel) return $opts->shouldProcessPost();
    if (is_array($opts)) $closurecheck = keyVal('closurecheck',$opts);
    if ($opts instanceOf Closure) $closurecheck = $opts;
    if ($closurecheck instanceOf Closure) { 
      $data = Request::all();
      if( !$closurecheck($data, $opts)) return false;
    }
    $submitname = keyVal('submitname',$opts,'submit');
    $submitvalue = keyVal('submitvalue',$opts);
    if (($submitvalue === null) || !$submitname) return true;
    return Request::input($submitname) == $submitvalue;
  }

  /** Submits POST data to the PkModel instance to save updates. 
   * 
   * $opts can be an instance of PkModel, or an array of PkModels, or a parameter 
   *    array of Params containing at least a 'pkmodel' or 'pkmodels' key
   * @param \PkExtensions\Models\PkModel OR Collecton/Array of such $pkmodels
   * @param array $inits - Associative array of supplimental data to submit
   * @param string|null $modelkey - If we have an array of models to process, what is the post key for them?
   * @return type
   */
  /** Experimenting with handling array/collections of models - then, the 
   * 'Submit' button has the name 'modelset', and the fully namespaced value of
   * the model class. We need to get the original set of models, because we don't
   * want to delete models that didn't belong to that collection in the first place...
   * @param PkModel $pkmodel
   * @param Arrayish PkModels $pkmodels - optional
   * @param array $inits - initial/default values if not found in POST
   * @param type $modelkey
   * @return boolean|null - null if shouldn't processSubmit, true if succeds, else false
   */
  //public function processSubmit( $pkmodel, Array $inits = [], $modelkey = null) {
  public function processSubmit( $opts = null, $inits=null) {
    if(!$this->ShouldProcessSubmit($opts)) return null;
     if ($opts instanceOf PkModel) $pkmodel = $opts;
    if (is_arrayish($opts)) {
       #We are processing a submission
      $customProccessor = keyVal('customProccessor', $opts);
      if (is_callable($customProccessor))  $customProccessor($opts, $inits);
      $pkmodel = keyVal('pkmodel',$opts);
      $pkmodels = keyVal('pkmodels',$opts);
      if($inits === null) $inits = keyVal('inits',$opts);
      $modelkey = keyVal('modelkey',$opts);
    /*
      #Processing a POST - what to do? Look at args:
      if (is_string($pkmodel) && class_exists($pkmodel,1)
              && is_subclass_of($pkmodel, 'PkExtensions\\Models\\PkModel')) { #It's a PkModel name
        #So what do we do with that?
      }
     */
      $data = Request::all();
      $tpkm = typeOf($pkmodel);
      pkdebug("TPO: [$tpkm], Data:",$data);
      //if (!$pkmodel) return false;
      if ($pkmodel instanceOf PkModel) {
        if (is_array($inits)) foreach ($inits as $key => $val) {
          $data[$key] = $val;
        }
        //pkdebug("The POST:", $_POST, 'DATA:', $data);
        $result = $pkmodel->saveRelations($data);
        return $result;

      }
        if (!$pkmodels || !is_arrayish($pkmodels) ) {
          if(is_arrayish($pkmodel)) $pkmodels = $pkmodel;
          else $pkmodels = $opts;
        }
        if ($modelName = $this->isModelSetSubmit()) {
         #Then we look for a key of 'modelset' in the $data array, which
         #should have the value of a full model name 'App\Models\Item'
         #THEN we look for the Model Name Key in the $data - name the
         #controls by name='App\Models\Item[$idx][id]', etc
        $modelDataArray = keyValOrDefault($modelName,$data,false);
        if ($modelDataArray === false) return false;
        if ((!is_arrayish($modelDataArray) || !count($modelDataArray)) && 
                !count($pkmodels)) return false;
        if (!is_subclass_of($modelName, 'PkExtensions\Models\PkModel')) throw new Exception ("[$modelName] does not extend PkModel");
        #We assume $pkmodels is a collection of the original models, and $modelDataArray
        #contains whatever changes/additions/deletions. We hand off to the Model
        #class to manage.
        return $modelName::updateModels($pkmodels, $modelDataArray);
      }
      throw new \Exception ("Don't know what to do with pkmodels: ".print_r($pkmodels,1));
    }
  }

  /** Not an action - but checks if the POST/Submission is for an
   * array/collection of models without an owner. It does this by checking
   * if the POST key 'modelset' exists - which should have the value of the
   * fully qualified 'App\Models\Item' model name or whatever.
   * @return false | ModelName
   */
  public function isModelSetSubmit() {
    if(Request::method() !== 'POST') return false;
    $data = Request::all();
    return keyValOrDefault('modelset', $data,false);
  }

  /**
   * THIS IS NOT AN ACTION - The route('error') should lead to an action, by 
   * default, the "displayerror" action below...
   * Redirects to error report page
   * @param string $msg - the error to report
   * @return Redirect Response
   */
  public function error($msg) {
      return redirect()->route('showerror')->withError(new MessageBag(['error'=>$msg]));
  }

  /** Ideally, the error will NOT be in the URL, but in the flashed message bag
   * 
   * @param type $error
   * @return Redirected to the error page with appropriate error msg.
   */
  public function showerror($error=null) {
  if ($error === null) $error = \Session::get('error');
    if (! $error instanceOf MessageBag) {
      if (is_string($error)) $error = new MessageBag(['error'=>$error]);
      else $error = new MessageBag(['error' => print_r($error,1)]);
    }
		return view('showerror', ['error'=>$error]);
  }

  public function message($msg) {
    return redirect()->route('showmessage')->withMessage(new MessageBag(['message'=>$msg]));
  }

  public function showmessage($message=null) {
  if ($message === null) $message = \Session::get('message');
    if (! $message instanceOf MessageBag) {
      if (is_string($message)) $message = new MessageBag(['message'=>$message]);
      else $message = new MessageBag(['message' => print_r($message,1)]);
    }
		return view('showmessage', ['message'=>$message]);
  }


  /**
   * Returns assets (.css, .js, etc) within the PkExtensions package.
   * This is a kludge, because the router doesn't allow for controllers outside
   * the default namespace. So controllers which extend PkController should NOT
   * override this method - the router should call this method on a controller
   * which extends PkController, but this method will look for assets relative
   * to the PkController location.
   * @param string $assetpath - the relative path to the asset within the
   * PkExtensions package
   * @return file - with appropriate header
   */
  public function pkasset($assetpath) {
    if (!$assetpath || !is_string($assetpath)) { 
      header("HTTP/1.0 404 Not Found");
      die();
    }
    $assetfilepath = realpath(__DIR__."/../assets/$assetpath");
    if (!file_exists($assetfilepath)) {
      header("HTTP/1.0 404 Not Found");
      die();
    }
    $mimeType =finfo_file(finfo_open(FILEINFO_MIME_TYPE), $assetfilepath);
    #Hack for CSS since PHP can't detect that...
    if ($mimeType === 'text/plain') {
      $ext = pathinfo($assetfilepath, PATHINFO_EXTENSION);
      $ext = strtolower($ext);
      if ($ext === 'css') $mimeType = 'text/css'; 
    }
    header("content-type: $mimeType");
    header('Content-Description: File Transfer');
    header('Content-Length: ' . filesize($assetfilepath));
    readfile($assetfilepath);
    die();
    //return "This is an asset request with path: ".print_r($assetpath,1);
  }

  /** Sets headers for file export/save
   * Should be followed by "echo" of data, then die();
   * @param string $filename - suggested filename
   */
  public static function setExportHeaders($filename = '') {
    header("Pragma: public");
    header("Expires: 0"); // set expiration time
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    // browser must download file from server instead of cache
    // force download dialog
    header("Content-Type: application/force-download");
    header("Content-Type: application/octet-stream");
    header("Content-Type: application/download");

    // use the Content-Disposition header to supply a recommended filename and
    // force the browser to display the save dialog.
    header("Content-Disposition: attachment; filename=$filename;");

    //Make sure the browser gets a 200 header
    header('Last-Modified: ' . gmdate('D, d M Y H:i:s', time()) . ' GMT', true, 200);
  }
}
