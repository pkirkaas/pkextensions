<?php
/** This is to use in PkModels that "own" other items, & might want to provide
 * info on that.
 */
namespace PkExtensions\Traits;
use PkExtensions\Models\PkModel;
use PkExtensions\PkCollection;
 /** Only used by models, so assumes access to modely stuff
 * @author pkirkaas
 */

   
trait OwnerInfoTrait {
  /**Provides info about the own & optionally its named relationship.
   * If $relationship name/attribute, adds array [attribute->name, model-Model]
   * if $idx is not null, returns the ID of 
   */
  public function ownershipInfo($relationship=null, $idx=null) {
    $info=[];
    if ($relationship && ($relclass = static::getLoadRelations($relationship,true) )) {
      $rel = $this->$relationship;
      $info += ['attribute'=>$relationship,'model'=>$relclass];
      if (!$rel) {
        $info+=['count'=>0,'type'=>'1to1'];
      } else if ($rel && ($rel instanceOf PkModel)) {
        $info+=['id'=>$this->$relationship->id,'count'=>1,'type'=>'1to1'];
      } else if ($rel instanceOf PkCollection) {
        $info+=['count'=>count($rel),'type'=>'1toMany'];
        if (is_int($idx) && $idx<count($rel)) {
          $info+=['idx'=>$idx, 'id'=>$rel[$idx]->id];
          $rel = $rel[$idx];
        }
      }
      if ($relclass::usesTrait('PkUploadTrait') && ($rel instanceOf PkModel)) {
        $info+=['url'=>$rel->url,'mediatype'=>$rel->mediatype];
      }
    }
    $info += ['ownermodel'=>static::class, 'ownerid' => $this->id];
    return $info;
  }
}
