<?php
/** Actually, maybe better called "OwnedModelTrait" - it is "owned" by another
 * model (which could use the "PkHasTypedModelTrait" to manage this.)
 * This can be one to many or one to one - like ProfileUploads, could be several
 * types, all belong to the same profile. All the implementing/using/owned class
 * needs to do is specify the foreign key & relationship -- like:
 * 

class ProfileUpload extends PkModel {
  use PkTypedUploadTrait;
  public static $table_field_defs_ExtraProfileItemTrait = [
      'profile_id' => ['type' => 'integer', 'methods' => ['index']],
    ];

  public function profile() {
    return $this->belongsTo('App\Models\Profile');
  }
 */
namespace PkExtensions\Traits;
use PkExtensions\PkException;
use PkExtensions\Models\PkModel;
/**
 * If some types of PkModels might be similar, create a single model & adjust
 * behavior by type. Instead of making separate classes & tables for them all,
 * make a single class that can have different owner types
 * 
 * I've cleverly extended PkModel to add the relationships hasOneTyped & hasManyTyped,
 * so you can use them like any other relationship definition:
 * 
 * //In Owner of typed class:
    public function avatar() {
      return $this->hasOneTyped('avatar');
    }
 * 
 * The using/implementing class just has to specify the relationship & foreign key,

 * @author pkirk
 */

/** Geez, just so much easier to create, say "profile_id" in the implementing 
 * classes...., & function profile() {return $this->ownedBy(Profile::class);}
 * If "owned", Owner should use PkHasTypedModelTrait to manage these
 */
trait PkTypedModelTrait {


  public static $table_field_defs_PkTyped = [
        /*
      #Like, 'App\Models\Profile'
      'owner_model'=> ['type' => 'string', 'methods' => 'nullable'],
      #Like - 7 - the ID of the owning model
      'owner_id' => ['type' => 'integer', 'methods' => ['index', 'nullable']],
         * 
         */
      #Like 'avatar'; The "owner's" name for the attribute, in the relationship method
      'att_name'=> ['type' => 'string', 'methods' => ['nullable','index']],
      'att_desc'=> ['type' => 'string', 'methods' => ['nullable',]],
      'att_label'=> ['type' => 'string', 'methods' => ['nullable',]],
      'type'=> ['type' => 'string', 'methods' => ['nullable','index']],
      'subtype'=> ['type' => 'string', 'methods' => ['nullable','index']],
      'category'=> ['type' => 'string', 'methods' =>  'nullable'],
    ];

  /** So additional traits (or implementing classes) can add type filter*/
  public static function getTypedFields() {
    return static::getArraysMerged('table_field_defs_PkTyped');
  }
  /** Both validates and enhances the constructor arguments / atts */
  /*
  public static function OwnedModelTraitExtensionCheck($atts) {
    //pkdebug("Entering & Leaving OMTEC w. atts:", $atts);
    return $atts;
    if (!keyVal('att_name', $atts)) {
      return false;
    }
    if (array_key_exists('owner', $atts)) {
      $owner = $atts['owner'];
      if (!$owner instanceOf PkModel) {
        throw new PkException(["Owner was set, but not PkModel:", $owner]);
      }
      //unset($atts['owner']);
      $atts['owner_id'] = $owner->id;
      $atts['owner_model']=get_class($owner);
    } else {
      $atts['owner'] = $atts['owner_model']::find($atts['owner_id']);
      if (!$atts['owner'] instanceOf PkModel) {
        throw new PkException(["Owner was set, but not PkModel:", $owner]);
      }
    }
    return $atts;
  }
  public function  OwnedModelTraitConstruct($atts) {
    //pkdebug("Entering Trait Constructor w. atts:", $atts);
      $owner = unsetret($atts,'owner');
      if ($owner instanceOf PkModel) {
        $this->owner_id = $atts['owner_id'] = $owner->id;
        $this->owner_model = $atts['owner_model']  = get_class($owner);
      } else {
        $this->owner_id = keyVal('owner_id',$atts);
        $this->owner_model =  keyVal('owner_model',$atts);
      }
    return $atts;
    }
  
  public function getOwnerName() {
    if (!($om = $this->owner_model)) {
      return null;
    }
      $ucmodel = substr($om, strrpos($om, '\\') + 1)?:$om;
      $ownername = strtolower($ucmodel);
      return $ownername;
  }

*/
  /** In case we create the upload object before we give it an owner
   * 
   * @param \PkExtensions\Models\PkModel $owner
   * @return $this
   */
  /*
  public function setOwner(PkModel $owner) {
    $this->owner_model = get_class($owner);
    $this->owner_id = $owner->id;
    $this->owner = $owner;
    return $this;
  }
   * 
   */



  /** So if it belongs to 'profile', allow $this->profile() & $this->profile to 
   * return appropriately
   * @param string $method
   * @param array $args
   * @return relationship/builder
   */
  /*
  public function __call($method, $args=[]) {
    if ($method === $this->getOwnerName()) {
      return $this->belongsTo($this->owner_model, 'owner_id');
    }
    return parent::__call($method, $args);
  }
  public function owner() {
    if ($this->owner instanceOf PkModel) {
      return $this->owner;
    }
    return $this->owner = $this->owner_model::find($this->owner_id);
  }
   * 
   */

    /*
  public function __get($att) {
    if ($att === 'owner') {
      if ($this->owner) {
        return $this->owner;
      }
      $owner_model = $this->owner_model;
      if (!$owner_model || !$this->owner_id) {
        return null;
      }
      return $this->owner = $owner_model::find($this->owner_id);
    }
    if ($att === 'owner_model') {
      return keyVal('owner_model',$this->attributes);
    }
    if ($att === $this->getOwnerName()) {
      return $this->$att()->first();
    }
    return parent::__get($att);
  }
     */
  
  /*

  public function __set($att, $val) {
    if (($att === 'owner') && ($val instanceOf PkModel) ) {
      $this->owner_model = get_class($val);
      $this->owner_id = $val->id;
      $this->owner = $val;
      return;
    }
    if ($att === $this->getOwnerName()) {
      if (!$val) {
        $this->owner_id = null;
      } else if ($val instanceOf $this->owner_model) {
        $this->owner_id = $val->id;
      } else {
        throw new PkException(
            ["Wrong type Val; Owner-Model: {$this->owner_model}; Val:", $val]);
      }
    } else {
      parent::__set($att, $val);
    }
  }
   */
}
