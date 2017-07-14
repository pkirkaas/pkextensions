<?php
namespace PkExtensions\Models;
use Illuminate\Support\Facades\Storage;
/**
 *  PkUploadModel - pure Laravel, replaces the attachment models that were based on stapler
 *
 * @author pkirk
 */
abstract class PkUploadModel extends PkModel {
  #Map the general media type to an array of the specific mime types
  /*
  public static $upload_types = [
    'image'=>['image/gif','image/png', 'image/jpeg', 'image/bmp', 'image/jpg','image/svg' ],
    'video'=>['video/ogg','video/mpeg', 'video/mp4', 'video/webm',
        'video/3gpp','video/quicktime', ],
    'audio'=>['audio/ogg','audio/mpeg', 'audio/mp4', 'audio/webm', 'audio/mp3',
        'audio/wav', 'audio/wave'],
    'pdf'=>['application/pdf'],
    'text'=>['text/plain', 'text/html'],
  ];
   * 
   */
  
  public static $table_field_defs = [
      'relpath'=>'string',
      'type' => ['type' => 'string', 'methods' => 'nullable'],#Like image,audio
      #Category, like, doc (text & pdf), media (audio, video)
      'cat' => ['type' => 'string', 'methods' => 'nullable'],
      'mimetype'=>'string',
      'desc' => ['type' => 'string', 'methods' => 'nullable'],
      ];
  
  public static function getUploadTypes() {
    return static::getAncestorArraysMerged('upload_types');
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
    if (!$mimefirst && ne_string($this->type)) {
      return $this->type;
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
      if (!$this->fileexists($deleteonfalse)) {
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
  public function fileexists($deleteonfalse = true) {
    if (Storage::exists($this->relpath)) {
      return true;
    }
    if ($deleteonfalse) {
      $this->delete();
    }
    return false;
  }

  /** If "$this->type" not set, try to guess from mime-type
   * @param array $args - optional args
   * @return Model
   */
  public function save(Array $args = []) {
    if (!$this->type) {
      $this->type = $this->getType(true);
    }
      return parent::save($args);
  }

  /** Create object immediately on upload 
   * 
   * @param array $fileinfo - the basic info for this abstract base class
   * @param array $extra - additional details, 
   * @return \static|boolean - the new instance, or false if error
   */
  public static function CreateUpload($fileinfo,Array $extra = null) {
    //pkdebug("CU; FF: ",$fileinfo,'EXTRA: ', $extra);
    if (!$fileinfo || !is_array($fileinfo)
        || !Storage::exists(keyVal('relpath',$fileinfo))) {
      return false;
    }
    $fo = new Static();
    $fo->fill($fileinfo);
    if (is_array($extra)) {
      $fo->fill($extra);
    }
    if($fo->save()) {
      return $fo;
    }
    return false;
  }
}
