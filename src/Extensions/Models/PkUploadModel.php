<?php
namespace PkExtensions\Models;

/**
 *  PkUploadModel - pure Laravel, replaces the attachment models that were based on stapler
 *
 * @author pkirk
 */
class PkUploadModel extends PkModel {
  #Map the general media type to an array of the specific mime types
  public static $upload_types = [
    'image'=>['image/gif','image/png', 'image/jpeg', 'image/bmp', 'image/jpg','image/svg' ],
    'video'=>['video/ogg','video/mpeg', 'video/mp4', 'video/webm',
        'video/3gpp','video/quicktime', ],
    'audio'=>['audio/ogg','audio/mpeg', 'audio/mp4', 'audio/webm', 'audio/mp3',
        'audio/wav', 'audio/wave'],
    'pdf'=>['application/pdf'],
    'text'=>['text/plain', 'text/html'],
  ];
  
  public static $table_field_defs = [
      'name'=>'string',
      'type'=>'string',
      'mime_type'=>'string',
      'full_path'=>'string',
      ];
  
  public static function getUploadTypes() {
    return static::getAncestorArraysMerged('upload_types');
  }
}
