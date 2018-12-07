<?php
namespace PkExtensions\Traits;
use PkExtensions\PkExceptionResponsable;
use PkExtensions\PkFile;
use PkExtensions\PkException;
use PkExtensions\PkFileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/*
class FileProxy {
  public $name; // The name of the file field 
  public $type; //The type of the file
  public $instance; //The instance of the class implemeting PkUploadTrait
  public $fields; //idx Array of the actual name of the fields - $name_mimetime
  public $baseFields; //idx Array of the base field names - mimetype
  public $fieldmap; //Assoc mape baseFieldName => realFieldName
  public static $uploadstaticmethods;
  public function __construct($name, $instance) {
    $this->name = $name;
    $this->instance = $instance;
    $this->baseFields = $instance->getFieldEntryNames();
    $this->fields = $instance->getFieldEntryNames($name);
    $this->fieldMap = array_combine($this->baseFields,$this->fields);
    if (!static::$uploadstaticmethods) {
      static::$uploadstaticmethods=
        traitMethods(PkUploadTrait::fullTrait(),[],
            \ReflectionClass::IS_STATIC);
    }
  }
  public function __get($fname) {
    if (in_array($fname,$this->baseFields)) {
      $rfname = $this->fieldMap[$fname];
      return $this->instance->$rfname;
    } else {
      return null;
    }
  }
  public static function __callStatic($name,$arguments) {
    if (in_array($name, static::$uploadstaticmethods)) {
    }
  }
}
 * 
 */

/** Experiment with allowing multiple file objects as part of a model
 * 
 */
class FileProxy {
  use PkUploadTrait;
  public $baseFields;
  public $name;
  public $type; //The type of the file
  public $fields;
  public $instance;
  public $fieldmap; //Assoc mape baseFieldName => realFieldName
  public function __construct($name, $instance) {
    $this->name = $name;
    $this->instance = $instance;
    $this->baseFields = $instance->getFieldEntryNames();
    $this->fields = $instance->getFieldEntryNames($name);
    $this->fieldMap = array_combine($this->baseFields,$this->fields);
  }

  public function __get($name) {
    if (in_array($name, $baseFields)) {
      $realField = static::fldnm($this->name, $name);
      return $this->instance->$realField;
    }
  }
  
}

/** This is to be incuded by PkModels. The Pk
/**
 * Supports uploads - to be used by Models. In turn, uses PkFileUploadService
 * @author pkirkaas
 */
trait PkUploadTrait {

  ## New Development in progress - as is, this can only support a single file in
  # an implementing class. So I will implement a static array implementing classes
  # implement to name their base files, and this trait will build multiple DB
  # fields to support. So a profile might have
  # public static $uploadFileDefs = ["avatar"=>"image", "resume"=>"pdf"], & this would build
  # avatar_relpath, resume_relpath, avatar_storagepathe, resume_storagepath, etc.
  # If the implementing class doesn't specify files, we'll just use these one set 
  #by default

  public static function fullTrait() {return __TRAIT__;}

  public static $uploadFileDefsUploadTrait = [];
  
  public static $base_table_field_defs_UploadTrait =  [
  'relpath'=>['string','nullable'],
  'storagepath'=>['string','nullable'],
  'filetype'=>['string','nullable'],
  'mediatype' => ['type' => 'string', 'methods' => 'nullable'],#Like image,audio
  #Category, like, doc (text & pdf), media (audio, video)
  'cat' => ['type' => 'string', 'methods' => 'nullable'],
  'mimetype'=>['string','nullable'],
  'size'=>['integer','nullable'],
  'originalname'=>['string','nullable'],
  'path'=>['string','nullable'],
  'uploaddesc' => ['type' => 'string', 'methods' => 'nullable'],
  ];

  /** Goes through all ancestors & traits to gather a combined array of filenames=>types
   *  OR if $name, the expected file type of that field.
   */
  public static function getUploadFileDefs($name = null) {
    $defs = static::getArraysMerged('uploadFileDefs'); 
    if (!$name) return $defs;
    return keyVal($name,$defs);
  }

  /** A associative array of "extra" file defs, by name, pointing to the anonymous
   * proxy object - which is returned by __get on the file name.
   */
  public $proxies = [];

  public static function getTableFieldDefsExtraUploadTrait() {
    $uploadFileDefs = static::getUploadFileDefs();
    if (!$uploadFileDefs) {
      return static::$base_table_field_defs_UploadTrait;
    }
    $names = array_keys($uploadFileDefs);
    $fieldDefs = [];
    foreach ($names as $name) {
      foreach (static::$base_table_field_defs_UploadTrait as $field => $uploadFileDef) {
        $fieldDefs[$name."_$field"]=$uploadFileDef;
      }
    }
    return $fieldDefs;
  }
  /*
   * 
   */


  public function manageExtraFile($name) {
    if (array_key_exists($name, $this->proxies)) {
      return $this->proxies[$name];
    }
    return $this->proxies[$name] = new FileProxy($name, $this);
  }

  public static function getEntryNames() {
    return array_keys(static::getUploadFileDefs());
  }

  #Returns an array of all the keys of $base_table_field_defs, if !$name,
  #else the field names with $name prepended
  public static function getFieldEntryNames($name=null) {
    $fields = array_keys(static::$base_table_field_defs_UploadTrait);
    if (!$name) return $fields;
    foreach ($fields as &$field) {
      $field = $name.'_'.$field;
    }
    return $fields;
  }

  public static $attstofuncs_uploadTrait=[
      'url',
  ];

  public static $upload_typesPkUploadTrait = [

   ];

  /** Can accept a uploaded file object, or an array of file info 
   * from PkFileUploadService
   * 
   * @param File|Array $args - either a Symfonyfile, or ['file'=$file] or 
   * an array of parameters needed by PkFileUploadService to upload the
   * file - either from disk or URL
   * @return array - the enhanced constructor array
   * @throws PkExceptionResponsable
   */
  public function ExtraConstructorPkUploadTrait($args=[]) {
  #Just to register any extra file name this might be handling
    $names = static::getEntryNames();
    if (!$names) return $args;
    foreach ($names as $name) {
      $this->attGetters[$name]="manageExtraFile";
    }

    if (!isPost() || empty($_FILES)) {
      //pkdebug("No Files?");
      return $args;
    }
    $us = new PkFileUploadService();
    $extra = $us->upload($args);
    //pkdebug("Extra for upload:",$extra);
    return array_merge($args,$extra);
  }

  public function upload($args=[], $name=null) {
  }
        


  /** Retrieves the file info from the request - both the keys & values.
   * @param int $num -default 0, the first key/val pair. If -1, all
   */
  public static function getFiles($num = 0) {
    $files = request()->allFiles();
    if ($num < 0) {
      return $files;
    } 
    $keys = array_keys($files);
    return [$keys[$num], $files[$keys[$num]]];
  }

  public static function showFilePost() {
    $firstset = static::getFiles();
    return static::setUploadModelProperties($firstset[1], $firstset[0]);
  }

  public static function getInfo() {
    $keyvalarr = static::getFiles();
    return static::setUploadModelProperties($keyvalarr[1], $keyvalarr[0]);
  }
  
  /** Implementing classes can define their own upload types & combine them 
   * with the default from the trait
   * @return complex array mapping keyed by upload type names to properties
   */
  public static function getUploadTypes() {
    return static::getArraysMerged("upload_types");
  }


  public static $required_attsPkUpload = ['relpath', 'mimetype'];



  public static function storage_dir($subdir = 'public') { 
    if (ne_string($subdir)) {
      $subdir = pktrailingslashit($subdir);
    } else {
      $subdir = '';
    }
    return base_path("storage/app/$subdir");
  }

  public static function sfile_path($relpath) {
    $fp = static::storage_dir().$relpath;
    if (file_exists($fp)) {
      return $fp;
    }
    return false;
  }
  public static function fldnm($fname, $name=null) {
    if (!$name) {
      return $fname;
    } else {
      return $name.'_'.$fname;
    }
  }

  public function file_path($deleteonfalse=true, $name=null) {
    $fldname = $this->fldnm('relpath',$name);
    $fp = static::sfile_path($this->$fldname);
    if ($deleteonfalse && !$fp) {
      if ($name) {
        $this->deleteEntry($name);
      } else {
        $this->delete();
      }
    }
    return $fp;
  }
  /** Try to return the appropriate HTML element for the file type; only a
   * 
   * @param array $atts - the attributes for the element (img/audio/div, etc)
   */
  public function render($atts = []) {
  }

  /** Returns the file type - if it exists as a field, return that, else the
   * first component of the mime type - unless $mimefirst, ignores the type
   * field & just uses first component of mime type
   * @param boolean $mimefirst - skip the type field & just use mime? Default false
   */
  public function getType($mimefirst = false, $name=null) {
    $fldname = $this->fldnm('mediatype',$name);
    if (!$mimefirst && ne_string($this->$fldname)) {
      return $this->$fldname;
    }
    return $this->mimeMainType($name);
  }

  public function mimeMainType($name=null) {
    $fldname = static::fldnm('mimetype',$name);
    return static::smimeMainType($this->$fldname);
  }
  public static function smimeMainType($mimetype) {
    if (!ne_string($mimetype)) {
      return null;
    }
    $mimarr = explode('/', $mimetype);
    return $mimarr[0];
  }

  public $baseurl = 'storage';
  //public $basepath = 
  public function getBaseUrl() {
    return pkleadingslashit(pktrailingslashit($this->baseurl));
  }

  /** Return the URL for the file
   * @param boolean $check - check if file exists? Default: false
   * @param boolean $deleteonfalse - if checking and no file, delete this? 
   * 
   */
  public function url($check = false, $deleteonfalse=true,$name = null) {
    if ($check) {
      if (!$this->file_path($deleteonfalse)) {
        return false;
      }
    }
    if ($this->relpath) {
      return $this->getBaseUrl().$this->relpath;
    }
  }

  /** We want to delete the file as well - but we have to find where Laravel put it...
   * Shall we just trust the Storage facade?
   */
  public function delete($cascade = true) {
    Storage::delete($this->relpath);
    return parent::delete($cascade);
  }

  /** If the uploaded file was part of a larger object, just delete the file entry,
   * not the entire instance
   * @param type $name
   */
  public function deleteEntry($name=null) {
    if($name) {
      $path = $name.'_relpath';
    } else {
      $path = "relpath";
    }
    Storage::delete($this->$path);
     foreach(static::getFieldEntryNames($name) as $field) {
       $this->$field=null;
     }
     $this->save();
  }

  /** Does the file actually exist?
   * 
   * @param boolean $deleteonfalse Default: True. Delete this if no file
   */
  public function thisfileexists($deleteonfalse = true,$name=null) {
    return !!$this->file_path($deleteonfalse,$name);
  }

  /** If "$this->mediatype" not set, try to guess from mime-type
   * @param array $args - optional args
   * @return Model
   */
  public function save(Array $args = []) {
    if (!$this->mediatype) {
      $this->mediatype = $this->getType(true);
    }
      return parent::save($args);
  }

  public static function extensionCheck($atts) {
     if (!is_string($atts['mimetype']) ||
         //!Storage::exists(keyVal('relpath',$atts))) {
         !is_string($atts['relpath'])) {
       pkdebug("Failed fupl check w. atts:", $atts);
       return false;
     }
    return $atts;
  }

  public function persistFileInfo($fileinfo,$name=null) {
    pkdebug("Entered uptlodtrai/persist wi fifing", $fileinfo);
    if (!is_array($fileinfo)) {
      throw new PkException(["Fileinfo not an array:", $fileinfo]);
    }
    foreach ($fileinfo as $key=>$val) {
      $key = static::fldnm($key,$name);
      $this->$key = $val;
    }
    $this->save();
    pkdebug("Leaving - atts:",$this->getAttributes());
    return true;
  }

    

  /** Create object immediately on upload 
   * 
   * @param array $fileinfo - the basic info for this abstract base class
   * @param array $extra - additional details, 
   * @return \static|boolean - the new instance, or false if error
   */
  public static function CreateUpload($fileinfo, $extra = []) {
    //pkdebug("CU; FF: ",$fileinfo,'EXTRA: ', $extra);
    if ($fileinfo === false) {
      pkdebug("Couldn't file the data", $fileinfo);
      return false;
    }
    if (is_array_assoc($extra)) {
      $fileinfo += $extra;
    }
    $fo = new Static($fileinfo);
    if($fo->save()) {
      return $fo;
    }
    return false;
  }
}
