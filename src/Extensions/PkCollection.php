<?php

namespace PkExtensions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use PkExtensions\Models\PkModel;
use PkExtensions\Traits\PkSelfBuildingTrait;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * PkCollection - just extends eloquent collection to delete 
 *
 * @author pkirk
 */
class PkCollection extends Collection {
   use PkSelfBuildingTrait;
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
       if (!$instance instanceOf PkModel) {
         continue;
       }
       $retarr[]=$instance->fetchAttributes($keys,$extra);
     }
     return $retarr;
   }

   /** The Laravel Helper function data_get only works on objects
    * with set properties - not dynamic
    * properties from __get, like models. So hijack for collection of PkModels
    * @param type $target
    * @param type $key
    * @param type $default
    */
   ##################  Experiment with PkModel::__isset - maybe don't need this? 11/2018




   /*
    */

  public static function pkDataGet($target, $key,$default = null) {
    if (is_null($key)) {
      return $target;
    }
    $key = is_array($key) ? $key : explode('.', $key);
    while (! is_null($segment = array_shift($key))) {
      if ($segment === '*') {
        if ($target instanceof Collection) {
          $target = $target->all();
         } elseif (! is_array($target)) {
          return value($default);
          }
          $result = [];
          foreach ($target as $item) {
            $result[] = static::pkDataGet($item, $key);
          }
          return in_array('*',$key) ? Arr::collapse($result) : $result;
        }
        if (($target instanceOf \PkExtensions\Models\PkModel)) {
          $target = $target->$segment;
        } else if (Arr::accessible($target) && Arr::exists($target, $segment)) {
          $target = $target[$segment];
        } elseif (is_object($target) && isset($target->{$segment})) {
          $target = $target->{$segment};
        } else {
          return value($default);
        }
        $lseg = $segment;
      }
    return $target;
  }
 

   #  * Get an operator checker callback.
   #  * Identical to the default Laravel Collection operatorForWhere, except uses
   #  * static::pkDataGet() instead of the helper "data_get", so if the item 
   #  * is an instance of PkModel, still tries to return "$item->$key", instead 
   #  * of using default data_get, which only de-references object properties if
   #  * they are set (that is, does not allow for "__get")
   #  * @param  string  $key
   #  * @param  string  $operator
   #  * @param  mixed  $value
   #  * @return \Closure
   # * *
    
    protected function operatorForWhere($key, $operator = null, $value = null) {
        if (func_num_args() === 1) {
            $value = true;
            $operator = '=';
        }
        if (func_num_args() === 2) {
            $value = $operator;
            $operator = '=';
        }
        return function ($item) use ($key, $operator, $value) {
            $retrieved = static::pkDataGet($item, $key);
            $strings = array_filter([$retrieved, $value], function ($value) {
                return is_string($value) || (is_object($value) && method_exists($value, '__toString'));
            });

            if (count($strings) < 2 && count(array_filter([$retrieved, $value], 'is_object')) == 1) {
                return in_array($operator, ['!=', '<>', '!==']);
            }

            switch ($operator) {
                default:
                case '=':
                case '==':  return $retrieved == $value;
                case '!=':
                case '<>':  return $retrieved != $value;
                case '<':   return $retrieved < $value;
                case '>':   return $retrieved > $value;
                case '<=':  return $retrieved <= $value;
                case '>=':  return $retrieved >= $value;
                case '===': return $retrieved === $value;
                case '!==': return $retrieved !== $value;
            }
        };
    }
    
/**/
## Already wasn't using this, or not finished 
/**
  * Get a value retrieving callback.
  * @param  string  $value
  * @return callable - again, override for Models to use their custom properties
  */
  /*
    protected function valueRetriever($value) {
        if ($this->useAsCallable($value)) {
            return $value;
        }
        return function ($item) use ($value) {
            return static::pkDataGet($item, $value);
        };
    } 
   * 
   */
  /*
   * 
   */



   /**Takes assoc array of keys=>asc or desc
    * and applies them to the collection - same method exists on PkModel,
    * @param type $keyval
    * @return typeo the collection
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
   /** Just to give it the same signature as a model/builder 
    * $key - what field to order by
    * $desc = false or 'desc' - default ascending, else desc.
    */
   public function orderby($key, $desc = false) {
      if ($desc === 'desc') {
         return  $this->sortbyDesc($key);
       } else {
         return  $this->sortby($key);
       }
     }
   /*
    * 
    */

   #### Only works for non-emtpy collections, of the same model...
   /** Returns a short string to allow recreation of the collection from 
    * the model name & list off IDs
    * @return string
    */
   public function totag() {
     if (!count($this)) return false;
     $model = get_class($this[0]);
     $idarr = $this->pluck('id')->toArray();
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
     $idarr = $this->pluck('id')->toArray();
     ///console("IDARR",$idarr);
     return [
        'model'=>get_class($this[0]),
        'id' => $idarr,
       // 'id' => implode(',',$idarr),
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
   * @return type We have several models in the model type collection with same models
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
    try {
      return $this->getQueueableClass();
    } catch (\Exception $ex) {
      return "mixed";
    }
  }

  public function getCustomAttributes($arg=[]) {
    $retarr = [];
    foreach ($this as $pkinst) {
      $retarr[]=['class_type'=>get_class($pkinst)] + $pkinst->getCustomAttributes($arg);
    }
    return $retarr;
  }
}
