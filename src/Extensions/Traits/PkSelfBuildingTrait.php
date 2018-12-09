<?php
namespace PkExtensions\Traits;
use PKExtensions\Models\PkModel;
use PKExtensions\PkCollection;
/*
 * Just enforces methods to provide info to re-create the Model (or Collection)
 * For a model, just the model name & ID, for PkCollection, the base collection
 * model name & list of IDs
 * 
 *
 * @author pkirkaas
 */
trait PkSelfBuildingTrait {
/** Takes the params generated getRestore (PkModel & ID or ID's) & rebuilds the 
 * instance or collection
 * @param[PkModel model: Fully namespaced
 * @param[int | array of ints] - keys to rebuild the model(s)
 * @return PkModel or PkCollection. For a single model instance, you don't
 * need the model name - conversely, you can use any PkModel if you do have the
 * Model name. PkCollections are not as easy.
 */


  /** Params can be an array or json string */
   public static function rehydrate($params) {
     return static::restore($params);
   } 
   public static function resotore($params) {
     if (!is_array($params) && ne_string($params)) {
       $params = json_decode($params, 1);
       if (!is_array($params)) {
         throw new \Exception("Couldn't reydrate params");
       }
     }
           
     $id = keyVal('id',$params); //int or list of ints or array of ins
     $model = keyVal('model',$params);
     $collection = keyVal('collection',$params);
     if (!$id || (!$model && !is_a($model = static::class, PkModel, true))) {
       return null;
     }
     #We have what we need to build the instance or instances
     $response = $model::Find($id); ///Could be on, or if many, already a collection
     if (!is_a($response,PkCollection) &&
         (is_a(static::class,PkCollection) || $collection || is_array($id))) {
       $response = new PkCollection($response);
     }
     return $response;
   }

   /** Returns minimal fields to rebuild the item/collection 
    * 
    */
   public function deyhdrate($json = 0) {
     return $this->restorePoints($json);
   }
   public function restorePoints($json =0) {
     if (is_collection()) {
      if (!count($this)) return false;
      $ret = [
         'model' => get_class($this[0]),
          'id' => $this->pluck('id')->toArray(),
          'collection' => static::class,
          ];
     } else if ($this->isPkModel()) {
       $ret = ['id' => $this->id,
        'model' => static::class,
         'collection' => false];
     }
     if ($json) {
       return json_enecode($ret, static::$jsonopts);
     } else {
       return $ret;
     }
   }

   public function isCollection() {
     return is_a(static::class, PkCollection);
   }
   public function isPkModel() {
     return $this instanceOf PkModel;
   }
}
