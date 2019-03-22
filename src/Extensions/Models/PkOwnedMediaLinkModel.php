<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions\Models;
use PkExtensions\Traits\PkOwnedModelTrait;
/**
/**
 * PkOwnedMediaLinkModel - If user's want to drag & drop a media link onto a Post
 * or something - image, video, etc. Implements the "Owned" trait, so single model/
 * table can belong to different kinds of owning models.
 *
 * @author pkirk
 */
class PkOwnedMediaLinkModel extends PkModel{
  use PkOwnedModelTrait;
  public static $table_field_defs = [
      'url'=> ['type' => 'string'],
      'element'=> ['type' => 'string','methods' => 'nullable'],
      'display_type'=> ['type' => 'string','methods' => 'nullable'],
      ];
  
  //protected $owner; #If set, the owning PkModel - make protected to call __get
  public function __construct(array $atts = []) {
    $atts = OwnedModelTraitConstruct($atts);
    parent::__construct($atts);
  }
  public function save(Array $args = []) {
    if (!filter_var($this->url, FILTER_VALIDATE_URL)) {
      $this->delete();
    }
    return parent::save( $args);
  }
}
