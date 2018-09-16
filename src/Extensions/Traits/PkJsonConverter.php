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
   * @param assoc/indexed array $atts -- attribute categories to attribute names,
   * like ['display'=>['name','age','title'], 'key'=>'id', 'private'=>['phone',... etc.
   * BUT Always return at least the key->id and the classname=>[classname]
   * If $atts is just indexed arr like: ['name', 'age',...] return:
   * ['name'=>$name, 'age'=>$age, 'key'=>$id, 'classname'=>$classname
   * 
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
    $result = ["id"=>$model->id, 'classname'=>get_class($model)];
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

  /* Structures model data for use in Vue resp-tbl
   * @param PkModel|PkModel array - $model 
   * @param (idx or assoc) array $atts -
   * if indexed ['name','age','rank'], return values w/o labels
   * if assoc ['name'=>"Name", 'age'=>"Age",], keys are fields & values labels
   * 
   * @return array of arrays of row data, as 
   * [['celldatarr'=>$celldataarr,'rowinfo'=>$rowinfo],..]
   * 
   */
  public static function structForVueRespTbl($model,$atts=[]){
    if (is_array_idx($atts)) {
      $fields = $atts;
      $labels=null;
    } else if (is_array_assoc($atts)) {
      $fields = array_keys($atts);
      $labels = $atts;
    } else {
      throw new \Exception("Invalid atts");
    }
    $mdldata = static::modelsToAtts($model,['display'=>$fields]);
    $retarr = [];
    if ($labels) {
      $retarr[]=static::mkRowData($labels,[],['islbl'=>true]);
    }
    foreach ($mdldata as $mdldtm) {
      $retarr[] = static::mkRowData($mdldtm['display'],$labels);
    }
    return $retarr;
    
  }

  /**
   * Takes a single array of row cell data, & formats it for vue resp-row
   * @param array $atts - in the form ['fnm1'=>'val1', 'fnm2'=>'val2',...]
   * @param array $labels - ['fnm1'=>'lbl1', 'fnm2'=>'lbl2',...]
   * @param array $rowinfo - for now, either empty or ['islbl'=>true]
   * @return assoc array:
   *   ['celldataarr'=>[['field'=>'val1','label'='lbl1'],['field'=>'val2', ...]],
   *    'rowinfo'=>$rowinfo
   *   ]
   */
  public static function mkRowData($atts,$labels=[],$rowinfo=[]) {
    $celldataarr = [];
    foreach ($atts as $key => $val) {
      $celldataarr[] = ['field'=>$val,'label'=>keyVal($key,$labels)];
    }
    return ['celldataarr'=>$celldataarr, 'rowinfo'=>$rowinfo];
  }
}


