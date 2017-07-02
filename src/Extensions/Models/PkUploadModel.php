<?php
namespace PkExtensions\Models;

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
      'type' => ['type' => 'string', 'methods' => 'nullable'],
      'mimetype'=>'string',
      'desc' => ['type' => 'string', 'methods' => 'nullable'],
      ];
  
  public static function getUploadTypes() {
    return static::getAncestorArraysMerged('upload_types');
  }

  public $baseurl = 'storage';
  public function getBaseUrl() {
    return pkleadingslashit(pktrailingslashit($this->baseurl));
  }

  public function url() {
    return $this->getBaseUrl().$this->relpath;
  }

  public static function CreateUpload($fileinfo,Array $extra) {
    pkdebug("CU; FF: ",$fileinfo,'EXTRA: ', $extra);
    if (!$fileinfo || !is_array($fileinfo)) {
      return false;
    }
    $fo = new Static();
    $fo->fill($fileinfo);
    if (is_array($extra)) {
      $fo->fill($extra);
    }
    if($fo->save()) {
      pkdebug("The new ID:", $fo->id);
      return $fo;
    }
    return false;
  }
}
