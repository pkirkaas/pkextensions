<?php
/*
 * DB operations with deep arrays of scalar values representing the searches,
 * operations & results
 * 
 * 
 */

namespace PkExtensions\Traits;
use PkExtensions\Models\PkModel;
use PkExtensions\Models\PkUser;

/**
 *
 * @author pkirkaas
 */
trait PkAjaxQueryTrait {

/**
 * Takes deep array of scalars & returns an array of scalars that match 
 * the search.
 * 
  ** Fetch attributes as specified by the model, instance id (or IDS), and 
   * model keys - or could be the relationships belonging to the parent - or
   * other eloquent collection
   * @param foreign_key - string name - opt - if missing but foreign model provided, default
   * @param foreign_model: If present & foreign key missing, default
   * @param foreign_id - value
   * @param string|null - relationship name, from the foreign model's perspective
   * @param model - string- the model to search
   * @param id - single id for single instance, list for collection, empty for all - UNLESS
   * @param searchkeys: if set, array of searchkeys=>values for "where"
   * @param orderby array of fields=>asc or desc
   * @param array keys: array of key atts to return, could include relationships. If empty, the default.
   * @param array extra - extra atts, like relationships, so don't have to specify all keys to add a few.
   *   --- that is, if @keys is empty, return all default keys, PLUS those here 
   * 
   * If we have model/id(s) it's easy. Let's extend to finding objects "owned" by others - 
   * Only need 'ownermodel', 'ownerid', & 'attribute'(relationship)
   * @return array - results - or string with error message
 */
  public function fetchAttributes($data) {
    if (!$data) {
      return [];
    }
    if (!is_array_assoc($data)) {
      return "Invalid data type of data: ".typeOf($data);
    }
    //PkDebug("Enter Fetchattrures with data:", $this->data, "Request Method:", $_SERVER['REQUEST_METHOD']);
    $obj=null;
    $model = keyVal('model',$this->data);
    $id = keyVal('id',$this->data);
    $foreign_key = keyVal('foreign_key',$this->data);
    $foreign_model = keyVal('foreign_model',$this->data);
    $relationship  = keyVal('relationship',$this->data);
    $extra = restoreJson(keyVal('extra',$this->data));
    $keys = restoreJson(keyVal('keys',$this->data));
    if (ne_string($keys)) {
      $keys=[$keys];
    }
    if (!ne_string($extra)) {
      $extra = [$extra];
    }
    if (is_array($keys) && !in_array('id',$keys,1)) {
      $keys[]='id'; #Could be one for an instance, or list for PkCollection
    }
    #MINIMUM need either 'model' OR ALL OF foreign_model, foreign_key & relationship
    if (!$model && !($foreign_model && $foreign_key && $relationship)) {
        return $this->error(['msg'=>"Insufficient parameters","params"=>$this->data]);
    }
    if ($model) {
      if ($foreign_key && $foreign_model) {
        $builder = $model::getAllOwnedBy($foreign_model, $foreign_key);
      } else {
        $builder = $model::query();
      }
      if ($id) {
        if (is_scalar($id)) {
          $builder->where('id',$id);
        } else if (is_array_idx($id)) {
          $builder->whereIn('id', $id);
        } else {
          return $this->error("Invalid type for 'id': ".typeOf($id));
        }
      } #We have the builder - now do we have any searches/filters? 
      
      if (is_array_assoc($searchkeys)) {
        foreach ($searchkeys as $skey=>$sval) {
          if (is_array_idx($sval)) {
            $builder->whereIn($skey,$sval);
          } else if (is_simple($sval)) {
            $builder ->where($skey,$sval);
          } else {
            return $this->error("Invalid type for search value: ".typeOf($sval));
          }
        }
      } #Done with filters for now - orderBy?
      
      $orderby = keyVal('orderby',$this->data);
        } else if ($searchkeys){
          $obj = $model::multiWhere($searchkeys);
        } else {
          $obj = $model::all();
        }
        if ($obj instanceOf Builder) {
          $obj = $obj->get();
        }
        if ((! $obj instanceOf PkModel) && $orderby) {
          $obj = $obj->multiOrderby($orderby);
        }
    } else { #Don't know this model - search by owner & relationship
      $ownermodel=keyVal('ownermodel', $this->data);
      $ownerid = keyVal('ownerid',$this->data);
      $attribute=keyVal('attribute',$this->data);
      $obj = $ownermodel::find($ownerid)->$attribute;
      $keys[]='totag';
    }
    //pkdebug("TypeOF obj:",typeof($obj));
    if ($obj) {
      if ($obj instanceOf Builder) {
        $obj = $obj->get();
      }
      if (($obj instanceOf Collection) && (! $obj instanceOf PkCollection)) {
        $obj = new PkCollection($obj);
      }
      //pkdebug("TypeOF obj AFTER Conv:",typeof($obj));
      $atts = $obj->fetchAttributes($keys,$extra);
      //pkdebug ("Rerting got atts of: ",$atts);
      return $this->success($atts);
    }
    return $this->success([]);
  }
}
