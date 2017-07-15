<?php
namespace PkExtensions\Models;
use PkExtensions\PkException;
/**
 * PkTypedUploadModel - Extends base PkUploadModel as attempt to make it
 * more generically usable for different Owners & Types & Names by giving it
 * owner_id, owner_class, member_name, & applying those to the relationships...
 * .... Experimental.... Trying to avoid making a different class/table for every
 * uploaded file type
 *
 * @author pkirk
 */
class PkTypedUploadModel extends PkUploadModel {
  public static $table_field_defs = [
      #Like, 'App\Models\Profile'
      'owner_model'=> ['type' => 'string', 'methods' => 'nullable'],
      #Like - 7 - the ID of the owning model
      'owner_id' => ['type' => 'integer', 'methods' => ['index', 'nullable']],
      #Like 'avatar'; The "owner's" name for the attribute, in the relationship method
      'att_name'=> ['type' => 'string', 'methods' => 'index'],
      ];

  
  public function __construct(array $atts = []) {
    pkdebug("Constructing w. atts:",$atts);
    if (array_key_exists('owner', $atts)) {
      $owner = $atts['owner'];
      if (!$owner instanceOf PkModel) {
        throw new PkException(["Owner was set, but not PkModel:", $owner]);
      }
      unset($atts['owner']);
      $atts['owner_id'] = $owner->id;
      $atts['owner_model']=get_class($owner);
    }
    pkdebug("Parent Constructing w. atts:",$atts);
    parent::__construct($atts);
    pkdebug("Finished Constructing w. atts:",$atts);
  }
  
  public function getOwnerName() {
    pkdebug("Trying to get owner name");
    
    if (!($om = $this->owner_model)) {
      return null;
    }
      $ucmodel = substr($om, strrpos($om, '\\') + 1)?:$om;
      $ownername = strtolower($ucmodel);
      return $ownername;
  }
  /*
   * 
   */

  /** In case we create the upload object before we give it an owner
   * 
   * @param \PkExtensions\Models\PkModel $owner
   * @return $this
   */
  public function setOwner(PkModel $owner) {
    $this->owner_model = get_class($owner);
    $this->owner_id = $owner->id;
    return $this;
  }
  /*
   * *
   */

  /** So if it belongs to 'profile', allow $this->profile() & $this->profile to 
   * return appropriately
   * @param string $method
   * @param array $args
   * @return relationship/builder
   */
  public function __call($method, $args=[]) {
    if ($method === $this->getOwnerName()) {
      return $this->belongsTo($this->owner_model, 'owner_id');
    }
    return parent::__call($method, $args);
  }

  public function __get($att) {
    if ($att === 'owner') {
      $owner_model = $this->owner_model;
      if (!$owner_model || !$this->owner_id) {
        return null;
      }
      return $owner_model::find($this->owner_id);
    }
    if ($att === 'owner_model') {
      return keyVal('owner_model',$this->attributes);
    }
    if ($att === $this->getOwnerName()) {
      return $this->$att()->first();
    }
    return parent::__get($att);
  }
  /*
   * 
   */

  

  public function __set($att, $val) {
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



}
