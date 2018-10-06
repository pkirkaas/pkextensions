<?php
namespace PkExtensions\Traits;
use PkExtensions\PkExceptionResponsable;
use PkExtensions\PkFile;
use PkExtensions\PkFileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\File as SymfonyFile;

/** This is to be incuded by PkModels. The Pk
/**
 * Supports uploads - to be used by Models. In turn, uses PkFileUploadService
 * @author pkirkaas
 */
trait PkUploadTrait {
  
  public static $table_field_defs_UploadTrait =  [
  'relpath'=>'string',
  'storagepath'=>'string',
  'mediatype' => ['type' => 'string', 'methods' => 'nullable'],#Like image,audio
  #Category, like, doc (text & pdf), media (audio, video)
  'cat' => ['type' => 'string', 'methods' => 'nullable'],
  'mimetype'=>'string',
  'size'=>'integer',
  'originalname'=>'string',
  'path'=>['string','nullable'],
  'uploaddesc' => ['type' => 'string', 'methods' => 'nullable'],
  ];
  public $uploadedFile;
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

    if (!isPost() || empty($_FILES)) {
      pkdebug("No Files?");
      return $args;
    }
    $us = new PkFileUploadService();
    $extra = $us->upload($args);
    //pkdebug("Extra for upload:",$extra);
    return array_merge($args,$extra);
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
