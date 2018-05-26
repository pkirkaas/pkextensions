<?php
namespace PkExtensions\Traits;
use Illuminate\Http\UploadedFile;

/** This is to be incuded by PkModels. The Pk
/**
 * Supports uploads - to be used by Models. In turn, uses PkFileUploadService
 * @author pkirkaas
 */
trait PkUploadTrait {
  
  public static $table_field_defs_UploadTrait =  [
  'relpath'=>'string',
  'mediatype' => ['type' => 'string', 'methods' => 'nullable'],#Like image,audio
  #Category, like, doc (text & pdf), media (audio, video)
  'cat' => ['type' => 'string', 'methods' => 'nullable'],
  'mimetype'=>'string',
  'size'=>'integer',
  'originalname'=>'string',

  'uploaddesc' => ['type' => 'string', 'methods' => 'nullable'],
  ];
  public $uploadedFile;

  public static $methodsAsAttributeNamesPkUpload = [
      'url',
  ];

  public static $upload_typesPkUploadTrait = [

   ];

  /** You have an uploaded file object - but it's not a model & not persistent.
   * Let's set them up.
   * @param UploadedFile $file
   */
  public function setUploadModelProperties(UploadedFile $file, $key=null) {
    #Let's see what data we can get from it first:
    $fileProperties = [
        'storeresult' =>$file->store('public'),
        'mimetype' => $file->getMimeType(),
        'originalname' => $file->getClientOriginalName(),
        'size' =>$file->getClientSize(),
        'path' =>$file->path(),
        'key' => $key,
];
    pkdebug("Uploaded File info: Array first:", $fileProperties, "Now from the file obj: ",$file);
        

  }

  /** Retrieves the file info from the request - both the keys & values.
   * @param int $num -default 0, the first key/val pair. If -1, all
   */
  public static function getFiles($num = 0) {
    $files = request()->filesAll();
    if ($num < 0) {
      return $files;
    } 
    $keys = array_keys($files);
    return [$keys[$num], $files[$keys[$num]]];
  }
  
  /** Implementing classes can define their own upload types & combine them 
   * with the default from the trait
   * @return complex array mapping keyed by upload type names to properties
   */
  public static function getUploadTypes() {
    return static::combineAncestorAndSiblings("upload_types");
  }

  #What could be in here?
  public function ExtraConstructorUploadTrait($atts = []) {
    $this->uploadedFile = keyVal('uploaded_file', $atts);
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

  public function file_path($deleteonfalse=true) {
    $fp = static::sfile_path($this->relpath);
    if ($deleteonfalse && !$fp) {
      $this->delete();
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
  public function getType($mimefirst = false) {
    if (!$mimefirst && ne_string($this->mediatype)) {
      return $this->mediatype;
    }
    return $this->mimeMainType();
  }

  public function mimeMainType() {
    return static::smimeMainType($this->mimetype);
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
   */
  public function url($check = false, $deleteonfalse=true) {
    if ($check) {
      if (!$this->file_path($deleteonfalse)) {
        return false;
      }
    }
    return $this->getBaseUrl().$this->relpath;
  }

  /** We want to delete the file as well - but we have to find where Laravel put it...
   * Shall we just trust the Storage facade?
   */
  public function delete($cascade = true) {
    Storage::delete($this->relpath);
    return parent::delete($cascade);
  }

  /** Does the file actually exist?
   * 
   * @param boolean $deleteonfalse Default: True. Delete this if no file
   */
  public function thisfileexists($deleteonfalse = true) {
    return !!$this->file_path($deleteonfalse);
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
    

  /** Create object immediately on upload 
   * 
   * @param array $fileinfo - the basic info for this abstract base class
   * @param array $extra - additional details, 
   * @return \static|boolean - the new instance, or false if error
   */
  public static function CreateUpload($fileinfo, $extra = []) {
    pkdebug("CU; FF: ",$fileinfo,'EXTRA: ', $extra);
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
