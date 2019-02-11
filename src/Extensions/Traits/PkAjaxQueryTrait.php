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
use Illuminate\Database\Eloquent\Builder;
use PkExtensions\PkCollection;
use App\Models\BouncerReference;

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
  public function traitFetchAttributes($data) {
    if (!$data) {
      return [];
    }
    if (!is_array_assoc($data)) {
      return "Invalid data type of data: ".typeOf($data);
    }
    //PkDebug("Enter Fetchattrures with data:", $this->data, "Request Method:", $_SERVER['REQUEST_METHOD']);
    $model = keyVal('model',$this->data);
    //$bn = basename($model);
    //pkdebug ("basename of [$model] is: [$bn]");
    $show = (basename($model) == "BouncerReference");
    if ($show) pkdebug("Data", $data);
    $id = keyVal('id',$this->data);
    $foreign_key = keyVal('foreign_key',$this->data);
    $foreign_model = keyVal('foreign_model',$this->data);
    $relationship  = keyVal('relationship',$this->data);
    $searchkeys  = keyVal('searchkeys',$this->data);
    $extra = restoreJson(keyVal('extra',$this->data));
    $keys = restoreJson(keyVal('keys',$this->data));
    $orderby = keyVal('orderby',$this->data);
    if (ne_string($keys)) {
      $keys=[$keys];
    }
    if (!ne_string($extra)) {
      $extra = [$extra];
    }
    if (!$searchkeys || !is_array($searchkeys)) {
      $searchkeys = [];
    }
    if ($id && in_array('id',array_keys($searchkeys),1)) {
      return "Error - id exists both as an independent parameter & part of searchkeys";
    }
    if ($id) {
      $searchkeys['id']=$id;
    }
    #MINIMUM need either 'model' OR ALL OF foreign_model, foreign_key & relationship
    if (!$model && !($foreign_model && $foreign_key && $relationship)) {
        return "Insufficient parameters: ".json_encode(data);
    }
    if ($model) {
      if ($foreign_key && $foreign_model) {
        $builder = $model::getAllOwnedBy($foreign_model, $foreign_key);
      } else {
        $builder = $model::query();
      }
      PkModel::multiWhere($searchkeys, $builder);
      PkModel::multiOrderby($orderby, $builder);
      $pkCollection = $builder->get();
    } else { #Don't know this model - search by owner & relationship
      $sortaBuilder = $foreign_model::find($foreign_key)->$relationship();
      if ($searchkeys && is_array_assoc($searchkeys)) {
        $sortaBuilder = PkModel($searchKeys, $sortaBuilder);
      }
      $sortaBuilder = PkModel::multiOrderby($orderby, $sortaBuilder);
      $pkCollection = $sortaBuilder->get();
    }
    if (!($pkCollection instanceOf PkCollection) || !count($pkCollection)) {
      //if ($show) pkdebug("No collection - data: ",$data,"pkCollection: ",$pkCollection);
      return [];
    }
    #Now we hava a non-empty PkCollection of Eloquent instances
    #Iterate through them & apply the extra keys & relationships to get
    #the full data structure
    $atts = $pkCollection->fetchAttributes($keys,$extra);
    if ($id) { //Was only expectect data from a singe object
      $atts = $atts[0];
    }
    if ($show) pkdebug ("Rerting got atts of: ",$atts);
    return $atts;
  }
}
