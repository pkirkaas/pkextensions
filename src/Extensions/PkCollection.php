<?php

namespace PkExtensions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * PkCollection - just extends eloquent collection to delete 
 *
 * @author pkirk
 */
class PkCollection extends Collection {

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

  /** This will combine collections of shared collections 
   * Like, users have clients, client have appointments, appointments have payments
   * Silly that Users->client->appointments->payments can't all combine
   * @param type $key - the colletion name - like "appointments" for appointment
   * @return typeWe have several models in the model type collection with same models
   */
  public function __get($key) {
    $type = $this->type();
    $typecollections = $type::getAttributeCollectionNames();
    if (!in_array($key,$typecollections, 1)) {
      return parent::__get($key);
    }
    $joinedcollections= new static();
    foreach ( $this as $item) {
      $joinedcollections = $joinedcollections->merge($item->$key);
    }
    return $joinedcollections;
  }
    
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
