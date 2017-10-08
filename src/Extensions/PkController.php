<?php

/**
 * PkController - Extends & adds functionality to default/base Laravel
 * <tt>app\Http\Controllers\Controller</tt>, in conjuction with PkModel &
 * the JS library pklib.js.
 * 
 * Highlights:
 * <tt>->processSubmit($pkmodel)</tt>: Checks if request type is "POST", if so,
 * maps the POSTed fields to $pkmodel attributes & updates them, then checks the
 * $pkmodel->load_relations array (which maps one-to-many relationships) and 
 * creates/updates/deletes the "many" sides as well. So if $pkmodel is a "Cart"
 * with many "Items", will update the cart and its $items.
 * 
 * Basic support for importing/exporting CSV
 * 
 * Basic support for default uploading
 * 
 * Basic support for flash messaging & error display
 * 
 * @author Paul.Kirkaas@gmail.com
 */

namespace PkExtensions;

use App\Http\Controllers\Controller;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag; #A collection of MessageBags
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use PkExtensions\Models\PkModel;
use PkExtensions\Traits\UtilityMethodsTrait;
use Illuminate\Http\UploadedFile;
use Request;
use \Exception;
use \Closure;

abstract class PkController extends Controller {
  use UtilityMethodsTrait;

  public static $errorMsgBag; #The error messages, if any. Try static first
  public static $viewErrorBag; #The error messages, if any. Try static first

  /**
   * Adds to or creates a ViewErrorBag & flashes the error for handling in the
   * layout (or wherever). Can be called from any controller method.
   * @param string $msg
   */

  public static function addErrorMsg($msg) {
    if (!static::$errorMsgBag instanceOf MessageBag) {
      static::$errorMsgBag = new MessageBag();
    }
    static::$errorMsgBag->add('error', $msg);
    $viewErrorBag = session('errors');
    if (!($viewErrorBag instanceOf ViewErrorBag)) {
      $viewErrorBag = new ViewErrorBag();
      session()->flash('errors', static::$viewErrorBag);
    }
    $viewErrorBag->put('PkControllerErrors', static::$errorMsgBag);
    session()->flash('errors', static::$errorMsgBag);
  }

  /**
    #Validation for ProcessSubmit()
   * Since we provide processSubmit() for most simple Form->DB saves, can be
   * used by several methods in same controller. If a method wants to validate,
   * just set <tt>$this->validationrules=['zip'=>'required'];</tt> (and optionally
   * the other validation parameters), and processSubmit will validate on the rules.
   * @var type 
   */
  public $validationrules; #Allows methods to set their own validators
  /* Example:
    $this->validationrules= [
        "rate"=>"nullable|integer",
        'zip'=>'nullable|integer',
        ];
   */
  public $validationmessages=[]; #Allows methods to set their own validators
  public $validationcustomattributes=[]; #Allows methods to set their own validators
  #Alternatively, the method creates its own custom validator, and sets it:
  public $validator;

  /** For validating requests. 4 implementations - & throws exception if failure
   * if arg $validator exists, we run it.
   * Else, if $this->validator exists, we run it
   * Else if $this->validationrules exist, we build & run with the rules
   * Else no validation.
   * @param Validator $validator
   */
  public function validateRequest($validator = null, $request = null) {
    if (!$validator) {
      $validator = $this->validator;
    }
    if ($validator) {
      return $this->validateWith($validator,$request);
    }
    if ($this->validationrules) {
      return $this->validate(request(), $this->validationrules,
          $this->validationmessages, $this->validationcustomattributes);
    }
    return true;
  }

  /** Verify if we should process this submit, called by $this->processSubmit();
   * If method not a POST, return false. Otherwise, check the submit button name and value
   * $opts are an array of params:
   * @param string $submitname - default 'submit' - The name of the POST key to check
   * @param string $submitvalue - default NULL - If you don't want to check on the submittd key/value, leave value out
   * @return boolean - true if we should process the post, else false if it fails a test
   */
  public function shouldProcessSubmit($opts = null) {
    if (Request::method() !== 'POST') return false;
    if (!$opts) return true;
    if ($opts instanceOf PkModel) return $opts->shouldProcessPost();
    if (is_array($opts)) $closurecheck = keyVal('closurecheck', $opts);
    if ($opts instanceOf Closure) $closurecheck = $opts;
    if ($closurecheck instanceOf Closure) {
      $data = Request::all();
      if (!$closurecheck($data, $opts)) return false;
    }
    $submitname = keyVal('submitname', $opts, 'submit');
    $submitvalue = keyVal('submitvalue', $opts);
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
   * @param PkModel $pkmodel - if you are processing a one-to-many subform, the model should be the 'one'
   * @param Arrayish PkModels $pkmodels - optional
   * @param array $inits - initial/default values if not found in POST
   * @param type $modelkey
   * @return boolean|null - null if shouldn't processSubmit, true if succeds, else false
   */
  public function processSubmit($opts = null, $inits = null) {
    if (!$this->ShouldProcessSubmit($opts)) return null;
    $validator = keyVal('validator', $opts);
    $valres = $this->validateRequest($validator);
    pkdebug("Validation Result:", $valres);
 
    #In a POST && met 'shouldProcessSubmit' requirements
    if ($opts instanceOf PkModel) {
      $pkmodel = $opts;
    } else if (is_arrayish($opts)) {
      #We are processing a form submission
      $customProccessor = keyVal('customProccessor', $opts);
      if (is_callable($customProccessor)) $customProccessor($opts, $inits);
      $pkmodel = keyVal('pkmodel', $opts);
      $pkmodels = keyVal('pkmodels', $opts);
      if ($inits === null) $inits = keyVal('inits', $opts);
      $modelkey = keyVal('modelkey', $opts);
    }
    /*
      #Processing a POST - what to do? Look at args:
      if (is_string($pkmodel) && class_exists($pkmodel,1)
      && is_subclass_of($pkmodel, 'PkExtensions\\Models\\PkModel')) {
      #It's a PkModel name - process
      }
     */
    $data = Request::all();
    if ($pkmodel instanceOf PkModel) {
      if (is_array($inits))
          foreach ($inits as $key => $val) {
          $data[$key] = $val;
        }
      $result = $pkmodel->saveRelations($data);
      return $result;
      #TODO: Future Enhancement if multiple models



      /*
        if (!$pkmodels || !is_arrayish($pkmodels)) {
        if (is_arrayish($pkmodel)) $pkmodels = $pkmodel;
        else $pkmodels = $opts;
        }
        if ($modelName = $this->isModelSetSubmit()) {
        #Then we look for a key of 'modelset' in the $data array, which
        #should have the value of a full model name 'App\Models\Item'
        #THEN we look for the Model Name Key in the $data - name the
        #controls by name='App\Models\Item[$idx][id]', etc
        $modelDataArray = keyValOrDefault($modelName, $data, false);
        if ($modelDataArray === false) return false;
        if ((!is_arrayish($modelDataArray) || !count($modelDataArray)) &&
        !count($pkmodels)) return false;
        if (!is_subclass_of($modelName, 'PkExtensions\Models\PkModel'))
        throw new Exception("[$modelName] does not extend PkModel");
        #We assume $pkmodels is a collection of the original models, and $modelDataArray
        #contains whatever changes/additions/deletions. We hand off to the Model
        #class to manage.
        return $modelName::updateModels($pkmodels, $modelDataArray);
        }
        throw new \Exception("Don't know what to do with pkmodels: " . print_r($pkmodels, 1));
       * 
       */
    } else {
      pkdebug("No pkmodel");
    }
  }

  /** Can be called multiple times in a form submission - once for each file upload.
   * The file input name ($ctlName) should be different from the actual model attribute
   * name. Convention is: $ctlName = 'XXX_file', $attName='XXX_filename'. If passes validation,
   * the base uploaded filename will be saved in 'XXX_filename'/$attName. 
   * @param PkModel $pkmodel - the PkModel instance
   * @param string $ctlName - The name of the file input ctl on the form
   * @param string $attName - The attribute name to store the base file uploade name
   * @param string $validationStr - The validation string to use for validation
   * @return type
   */
  public function processFileUploads($pkmodel,$ctlName,$attName,$validationStr='image') {
    if (!$this->shouldProcessSubmit()) return;
    $request = request();
    $uploadedFile = $request->file($ctlName);
    if ($validationStr) {
      $this->validate($request,[$ctlName=>$validationStr]);
    }
    if ($uploadedFile instanceOf UploadedFile) {
      $path = $uploadedFile->store('public');
      $baseName = basename($path);
      $pkmodel->$attName = $baseName;
      $pkmodel->save();
    } else {
      pkdebug("No file uploaded to ".get_class($pkmodel).
          " for att: [$attName] with ctlName: [$ctlName]");
    }
  }

  /** Not an action - but checks if the POST/Submission is for an
   * array/collection of models without an owner. It does this by checking
   * if the POST key 'modelset' exists - which should have the value of the
   * fully qualified 'App\Models\Item' model name or whatever.
   * @return false | ModelName
   */
  public function isModelSetSubmit() {
    if (Request::method() !== 'POST') return false;
    $data = Request::all();
    return keyValOrDefault('modelset', $data, false);
  }

  /**
   * THIS IS NOT AN ACTION - The route('error') should lead to an action, by 
   * default, the "displayerror" action below...
   * Redirects to error report page
   * @param string $msg - the error to report
   * @return Redirect Response
   */
  public function error($msg = null) {
    if (!$msg) {
      $msg = "There was an error";
    }
    static::addErrorMsg($msg);
    //return redirect()->route('showerror')->withError(new MessageBag(['error' => $msg]));
    //return redirect()->route()->back()->withError(new MessageBag(['error' => $msg]));
    return redirect()->back()->withError(new MessageBag(['error' => $msg]));
  }

  /** Ideally, the error will NOT be in the URL, but in the flashed message bag
   * @param type $error
   * @return Redirected to the error page with appropriate error msg.
   */
  public function showerror($error = null) {
    if ($error === null) $error = \Session::get('error');
    if (!$error instanceOf MessageBag) {
      if (is_string($error)) $error = new MessageBag(['error' => $error]);
      else $error = new MessageBag(['error' => print_r($error, 1)]);
    }
    return view('showerror', ['error' => $error]);
  }

  public function message($msg) {
    return redirect()->route('showmessage')->withMessage(new MessageBag(['message' => $msg]));
  }

  public function showmessage($message = null) {
    if ($message === null) $message = \Session::get('message');
    if (!$message instanceOf MessageBag) {
      if (is_string($message))
          $message = new MessageBag(['message' => $message]);
      else $message = new MessageBag(['message' => print_r($message, 1)]);
    }
    return view('showmessage', ['message' => $message]);
  }

  /** Returns just the controller name, without ending in 'Controller'.
   * @param boolean - $lc - Return the name in lower case? Default true
   * @return string - the base controller name
   */
  public static function getControllerName($lc = true) {
    $shortname = (new \ReflectionClass($this))->getShortName();
    $controllerName = removeEndStr($shortname, 'Controller');
    if ($lc) return to_lower($controllerName);
    return $controllerName;
  }

  /** Some simple AJAX helpers */
  public function ajax_header($args = null) {
    header('content-type: application/json');
  }

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

  /**
   * Directly renders data into a PHTML template
   * Uses the same view paths as Blade, but assumes PHTML
   * @param str $view
   * @param array $data
   */
  public function render($view, $data = []) {
    if (!$view || !is_string($view)) return '';
    $relview = str_replace('.', '/', $view);
    $viewroots = \Config::get('view.paths');
    $viewfile = null;
    foreach ($viewroots as $viewroot) {
      $testpath = $viewroot . '/' . $relview . '.phtml';
      if (file_exists($testpath)) {
        $viewfile = $testpath;
        continue;
      }
    }
    if (!$viewfile) {
      pkdebug("ERROR: Couldn't find viewtemplate: [$view]");
      return ' ';
    }
    if (is_array($data)) {
      ############# BE VERY CAREFUL ABOUT VARIABLE NAMES USED AFTER EXTRACT!!!
      ###########  $out, for example, is a terrible choice!
      extract($data);
    }
    ob_start();
    include ($viewfile);
    $___PKMVC_RENDERER_OUT = ob_get_contents();
    ob_end_clean();
    return $___PKMVC_RENDERER_OUT;
  }

  public static function staticRender($view, $data = []) {
    if (!$view || !is_string($view)) return '';
    $relview = str_replace('.', '/', $view);
    $viewroots = \Config::get('view.paths');
    $viewfile = null;
    foreach ($viewroots as $viewroot) {
      $testpath = $viewroot . '/' . $relview . '.phtml';
      if (file_exists($testpath)) {
        $viewfile = $testpath;
        continue;
      }
    }
    if (!$viewfile) {
      pkdebug("ERROR: Couldn't find viewtemplate: [$view]");
      return ' ';
    }
    if (is_array($data)) {
      ############# BE VERY CAREFUL ABOUT VARIABLE NAMES USED AFTER EXTRACT!!!
      ###########  $out, for example, is a terrible choice!
      extract($data);
    }
    ob_start();
    include ($viewfile);
    $___PKMVC_RENDERER_OUT = ob_get_contents();
    ob_end_clean();
    return $___PKMVC_RENDERER_OUT;
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
    $assetfilepath = realpath(__DIR__ . "/../assets/$assetpath");
    if (!file_exists($assetfilepath)) {
      header("HTTP/1.0 404 Not Found");
      die();
    }
    $mimeType = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $assetfilepath);
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
  }

  /** Imports a CSV file as an array. Tries to recover from errors and return
   * as much as possible.
   * @param string $fileName - The file to try and import
   * @return array of arrays (rows) - or else an error
   */
  function importCsv($fileName) {
    if (!$fileName || !is_string($fileName)) return false;
    $csvMimeTypes = [
        'text/plain',
        'text/csv',
        'text/x-csv',
        'application/csv',
        'text/comma-separated-values',
        'application/x-csv',
    ];
    $fmt = getFileMimeType($fileName);
    if (!$fmt || !in_array($fmt, $csvMimeTypes, 1)) {
      throw new \Exception("Importinc CSV file: [$fileName], reported MimeType: [$fmt]");
    }
    $handle = fopen($fileName, "r");
    if ($handle === false) return false;
    $retarr = [];
    while (($data = fgetcsv($handle)) !== FALSE) {
      if (!is_array($data) || ((sizeOf($data) === 1 ) && ($data[0] === null))) {
        continue;
      }
      $retarr[] = $data;
    }
    return $retarr;
  }

  /** Exports an array of arrays as a CSV file
   * 
   * @param string $fileName - What to call the export file
   * @param array of arrays - $output_arr - the output
   * @param array $columnHeaders - optional. If present, and if an array,
   *   will output them first as column headers for the CSV file.
   * @return - 
   */
  public function exportCsv($fileName, Array $output_arr = [], $columnHeaders = null) {
    $this->setExportHeaders($fileName);
    $output = fopen("php://output", "w");
    if ($columnHeaders && is_array($columnHeaders) && sizeOf($columnHeaders)) {
      fputcsv($output, $columnHeaders);
    }
    foreach ($output_arr as $output_line) {
      fputcsv($output, $output_line);
    }
    fclose($output);
    die();
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
