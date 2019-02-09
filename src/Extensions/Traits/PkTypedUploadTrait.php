<?php
/*
 * To combine TypedModel trait with UploadTrait. Unfortunately, models using
 * this will have to explicitly use PkTypedModelTrait, because otherwise there
 * are conflicts loading/using that trait twice
 */
namespace PkExtensions\Traits;
use PkExtensions\PkFileUploadService;
use PkExtensions\Traits\PkJsonFieldTrait;
/** To compose Typed Model & Upload traits for co-functionality
 * 
 */

trait PkTypedUploadTrait {
  #Implementing classes:
  #use PkTypedModelTrait;
  use PkUploadTrait;

  /** Makes a typed upload model instance from the $typedDef & file params
   * 
   * @param array $def - the typed model def - ex:
   * 
    'avatar' =>['model'=>'App\Models\ProfileUpload', 'type'=>'image','single'=>true,
        'att_label'=>'Avatar'
   * @param array $uploadparams - whatever optional data for the upload type.
   * OPTIONAL:
   *   owner - instanceOf PkModel
   */
  public static function makeUploadType($def, $uploadparams=[]) {
    $uploadparams['types'] = keyVal('type',$uploadparams,keyVal('type',$def));
    $uploadService = new PkFileUploadService();
    $res = $uploadService($uploadparams);
    $res['att_label']=keyVal('att_label',$def);
    $uploadClass = $def['model'];
    $UC = new $uploadClass(array_merge($uploadparams,$res));
    pkdebug("Uploaded Model Made:", $UC);
    return $UC;
    return new $uploadClass(array_merge($uploadparams,$res));
  }
  



}
