<?php

namespace PkExtensions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use PkExtensions\Models\PkModel;

/**
 * PkCollection - just extends eloquent collection to delete 
 *
 * @author pkirk
 */
class PkCollection extends Collection {

  /**
   * Just iterates over the instances & returns an array
   * of the of the individuale instance 'fetchAttributes()
   * @param type $keys
   * @param type $extra
   * @return Array
   */
   public function fetchAttributes($keys=[],$extra=[]) {
     $retarr=[];
     foreach ($this as $instance) {
       $retarr[]=$instance->fetchAttributes($keys,$extra);
     }
     return $retarr;
   }

   /**Takes assoc array of keys=>asc or desc
    * and applies them to the collection
    */
   public function multiOrderby($keyval) {
     $collection = $this->toBase();
     foreach ($keyval as $key=>$val) {
       if ($val === 'desc') {
         $collection = $collection->sortbyDesc($key);
       } else {
         $collection = $collection->sortby($key);
       }
     }
     return $collection;
   }

   #### Only works for non-emtpy collections, of the same model...
   /** Returns a short string to allow recreation of the collection from 
    * the model name & list off IDs
    * @return string
    */
   public function totag() {
     if (!count($this)) return false;
     $model = get_class($this[0]);
     $idarr = $this->pluck('id');
     $idlist = implode(',',$idarr);
     return $model.'#'.$idlist;
   }

   /** Returns assoc array to send to AJAX to reconstruct:
    * ['model'=>$modelName,
    *   'ids' => $idlist
    * ] ... but if empty, returns empty []
    */
   public function toajax() {
     if (!count($this)) return [];
     $idarr = $this->pluck('id');
     return [
        'model'=>get_class($this[0]),
        'idarr' => $idarr,
        'id' => implode(',',$idarr),
        ];
   }

   public static function fromtag($tag) {
     return PkModel::fromtag($tag);
   }


  /**
   * Deletes Models from an Eloquent collection
   * @param scalar|array|null $stuff  $stuff or array of things 
   * to remove from the collection, AND delete, if they are model instances;
   * or NULL to remove EVERYTHING from the collection & delete all the models
   * and update the collection count to 0. This is useful because other references
   * to the collection won't be aware the models have been deleted.
   * 
   * @param $delanyway - if $stuff has models that are NOT in the collection, 
   * delete those models anyway? Default, false
   */
  public function delete($stuff = null, $delanyway = false) {
    if ($stuff === null) {
      while ($item = $this->pop()) {
        if ($item instanceOf Model) {
          $item->delete();
        }
      }
      return $this;
    }
    if (!is_array($stuff)) {
      $stuff = [$stuff];
    }
    foreach ($stuff as $it) {
      $key = $this->search($it);
      if ($key === false) {
        if (($it instanceOf Model) && $delanyway) {
            $it->delete();
        }
        continue;
      }
      $item = $this->pull($key);
      if ($item instanceOf Model) {
          $item->delete();
      }
    }
    return $this;
  }

  public function which() {
    $out = 'Class: '.get_class($this).":\n";
    foreach ($this as $item) {
      $out.="  {$item->which()}\n";
    }
    return $out;
  }

  /*
  public function pkpluck ($value, $key = null) {
    $plucked = $this->pluck($value, $key);
    pkdebug("Plucked:", $plucked);
    return $plucked;
    //return $this->pluck($value, $key);
  }
   * */



  /** This will combine collections of shared collections 
   * Like, users have clients, client have appointments, appointments have payments
   * Silly that Users->client->appointments->payments can't all combine
   * @param type $key - the colletion name - like "appointments" for appointment
   * @return typeWe have several models in the model type collection with same models
   */
  public function __get($key) {
    $type = $this->type();
    if (!class_exists($type)) {
      return parent::__get($key);
    }
    //die("Type:".$type);
    //$typecollections = $type::getAttributeCollectionNames();
    $typecollections = $type::getRelationNames();
    if (!in_array($key,$typecollections, 1)) {
      return parent::__get($key);
    }
    $joinedcollections= new static();
    foreach ( $this as $item) {
      $joinedcollections = $joinedcollections->merge($item->$key);
    }
    return $joinedcollections;
  }
    
  #The model class of the collection - but if empty, null
  public function type() {
    return $this->getQueueableClass();
  }

  public function getCustomAttributes($arg=[]) {
    $retarr = [];
    foreach ($this as $pkinst) {
      $retarr[]=['class_type'=>get_class($pkinst)] + $pkinst->getCustomAttributes($arg);
    }
    return $retarr;
  }
}
