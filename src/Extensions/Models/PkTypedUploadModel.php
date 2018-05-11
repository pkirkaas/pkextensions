<?php
namespace PkExtensions\Models;
use PkExtensions\PkException;
use PkExtensions\Traits\PkOwnedModelTrait;
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
  use PkOwnedModelTrait;
  public static $table_field_defs = [ ];

  public static function extensionCheck($atts) {
      return static::OwnedModelTraitExtensionCheck($atts);
  }

  //protected $owner; #If set, the owning PkModel - make protected to call __get
  public function __construct(array $atts = []) {
    $atts = $this->OwnedModelTraitConstruct($atts);
    parent::__construct($atts);
    $this->initializeFromArr($atts);
  }
}
