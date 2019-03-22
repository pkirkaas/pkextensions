<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/*
 * Try a simpler implementation of PkJsonFiedTrait, using what Laravel supports
 * Idea is to support a basic repeatable named object structure - 
 */

namespace PkExtensions\Traits;

/**
 *
 * @author pkirkaas
 */
trait PkJsonArrayFieldTrait {
  protected $casts_JsonField = ['jsonarray'=>'array','schema'=>'array', 'keys'=>'array'];
  public static $table_field_defs_JsonFieldTrait = [
      'jsonarray' => ['type' => 'mediumText', 'methods'=>['nullable']],
      'jsontype' => ['type' => 'string', 'methods'=>['nullable']],
      'schema' => ['type' => 'string', 'methods'=>['nullable']],
      'keys' => ['type' => 'string', 'methods'=>['nullable']],
    ];
  public $shadowJsonarray = [];

  public function ExtraConstructorPkJsonArray($atts = []) {
    $this->shadowJsonarray = json_decode(keyVal('jsonarray',$atts,'{}'),1);
    return $atts;
  }

  public function _savePkJsonArray($opts=[]) {
    $this->jsonarray = $this->shadowJsonarray;
    pkdebug("Running extra savwe method");
  }

  /**
   * Sets the JSON array at $keys to value
   * @param array $keys - an index array of keys to drill down into the JSON field
   * @param array|scalar $value
   * @return array - the underlying array by reference
   */
  public function &setJson($keys, $value, $append=false) {
    $this->shadowJsonarray = $this->jsonarray;
    insert_into_array($keys, $value, $this->shadowJsonarray, false, false, $append);
    $this->jsonarray = $this->shadowJsonarray;
    return $this->shadowJsonarray;
  }

  public function &appendJson($keys,$value) {
    return $this->setJson($keys,$value, true);
  }

  /** Fetches the contents of $keys, or null if nothing exists there
   * MUST BE CALLED WITH &$instance->getJson(....) to get array reference
   * @param array|null $keys
   */
  public function &getJson($keys=null) {
    $this->shadowJsonarray = $this->jsonarray;
    if ($keys === null) {
      return $this->shadowJsonarray;
    }
    return fetch_from_array($keys,$this->shadowJsonarray);
  }

  
  
}
