<?php
/*
 * Base trait to make it easier to structure to & from JSON responses,
 * possibly update objects, respond with the modified data, etc. 
 * 
 */

namespace PkExtensions\Traits;
use PkExtensions\Models\PkModel;
/**
 *
 * @author pkirkaas
 */
trait PkJsonConverter {
  use UtilityMethodsTrait;

  /**
   * Takes a PkModel or array of PkModels, and a keyed array of attribute types
   * to indexed arrays of attribute names.
   * @param PkModel||array of -  $model
   * @param indexed array $atts -- attribute categories to attribute names,
   * like ['display'=>['name','age','title'], 'key'=>'id', 'private'=>['phone',... etc.
   * BUT Always return at least the key->id
   * @return complex array with the requested info
   */
  public static function modelsToAtts($model, $atts=[]) {
    if (!$model || (is_arrayish($model) && !count($model))) {
      return [];
    }
    if ($model instanceOf PkModel) {
      return static::_modelToAtts($model, $atts);
    }
    if (!is_arrayish($model) || ! ($model[0] instanceOf PkModel)) {
      throw new PkException("Wrong argument to modelsToAtts");
    }
    $results = [];
    foreach ($model as $key => $instance) {
      $results[$key]=static::_modelToAtts($instance, $atts);
    }
    return $results;
  }
  public static function _modelToAtts($model, $atts=[]) {
    if (!$model instanceOf PkModel) {
      throw new PkException("Wrong argument to modelsToAtts");
    }
    $result = ["id"=>$model->id];
    foreach ($atts as $type=>$props) {
      if (is_scalar($props)) {
        if (is_int($type)) {
          $result[$props]=$model->$props;
        } else {
          $result[$type] = [$props =>$model->$props];
        }
      } else if (is_arrayish($props)) {
          $cat = [];
          foreach ($props as $prop) {
            $cat[$prop] = $model->$prop;
          }
          $result[$type]=$cat;
      } else {
        throw new PkException("Wrong value for Atts to model");
      }
    }
    return $result;
  }
}


