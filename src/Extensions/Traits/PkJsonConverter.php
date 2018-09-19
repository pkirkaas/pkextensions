<?php
/*
 * Base trait to make it easier to structure to & from JSON responses,
 * possibly update objects, respond with the modified data, etc. 
 * 
 */

namespace PkExtensions\Traits;
use PkExtensions\Models\PkModel;
use PkExtensions\PkRefManager;
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

  /** Makes either a select input component, or the display value for the val
   * 
   */
  public static function mkSelect($ref,$val,$input=true, $forvue=true) {
  }


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


  public static function structForVueRespTbl(
      $model,$atts=[],$rowinfo=[], $tbldata = [],$input=true) {
    $input = keyVal('input',$tbldata,$input);
    $rowinfo['input']=$input;
    if (!$input) {
      unset($tbldata['newbtn']);
      unset($rowinfo['new']);
    }
    if(keyVal('newbtn',$tbldata) && $input) {
      $rowinfo['new']=true;
    }
    $rowinfo['input']=$input;
    $tbldata['relation']=keyVal('relation',$rowinfo);
    $tbldata['rowdataarr'] = static::structForVueRespRows($model, $atts, $rowinfo);
    return ['tbldata'=>$tbldata];
  }

  /* Structures model data for use in Vue resp-tbl
   * @param PkModel|PkModel array - $model 
   * @param (idx or assoc) array $atts -
   * if indexed ['name','age','rank'], return values w/o labels
   * if assoc ['name'=>"Name", 'age'=>"Age",], keys are fields & values labels
   * if assoc assoc: ['name'=>['label'=>"Name",'cellstyle'=>"flex-basis: 25%; ", 'age'=>"Age",], keys are fields & values labels
   * 
   * @return array of arrays of row data, as 
   * [['celldatarr'=>$celldataarr,'rowinfo'=>$rowinfo],..]
   * 
   * !! if celldata.width is set, that column will get a fixed pixel width
   * and the the others will share any extra
   * 
   */
  public static function structForVueRespRows($model,$atts=[],$rowinfo=[]){
    $input = keyVal('input', $rowinfo);
    if (is_array_idx($atts)) {
      $fields = $atts;
      $celldata=null;
      $delete=null;
    } else if (is_array_assoc($atts)) {

      $celldata = [];
      foreach ($atts as $key => $val) {
        if (is_string($val)) {
          $celldata[$key]=['label'=>$val];
        } else if (is_array_assoc($val) ) {
          $celldata[$key]= $val;
        } else if (is_bool($val)) {
          $celldata[$key]= [$val];
        } else {
          throw new \Exception ("Invalid att");
        }
      }
      $delete = unsetret($atts,'delete');
      /*
      if ($delete && is_scalar($delete)) {
        $delete = ['delete'=>[$delete]];
      }
       * 
       */
      $fields = array_keys($atts);
    } else {
      throw new \Exception("Invalid atts");
    }
    $mdldata = static::modelsToAtts($model,['display'=>$fields]);
    $retarr = [];
    if ($celldata) {
      $first = reset($celldata);
      if (array_key_exists('label',$first)) {
        if (keyVal('new',$rowinfo) && $input) {
          $retarr[] = static::mkRowData(['display'=>$celldata+['delete'=>$delete]]
              ,$celldata,$rowinfo);
        }
        unset($rowinfo['new']);
        $retarr[]=static::mkRowData(['display'=>$celldata],$celldata,['islbl'=>true]);
      }
    }
    $rowinfo['cnt']=0;
    foreach ($mdldata as $mdldtm) {
      //pkdebug("mldtm:",$mdldtm);
      if ($input && $delete) {
        if (is_scalar($delete)) {
          $delete = [$delete];
        }
        $delete['classname']=$mdldtm['classname'];
        $delete['id']=$mdldtm['id'];
        $mdldtm['display']['delete'] = $delete;
      }
      pkdebug("mldtm:",$mdldtm);
      $retarr[] = static::mkRowData($mdldtm,$celldata,$rowinfo);
      $rowinfo['cnt']++;
    }
    //$retarr[] = $newrow;
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
  public static function mkRowData($atts,$celldata=[],$rowinfo=[]) {
    $input = keyVal('input',$rowinfo);
    if (!$input) {
      unset($rowinfo['new']);
      unset($atts['display']['delete']);
      unset($celldata['input']);
    }
    $celldataarr = [];
    $id = keyVal('id',$atts);
    $isnew = keyVal('new',$rowinfo);
    $classname=keyVal('classname',$atts,keyVal('classname',$rowinfo));
    foreach ($atts['display'] as $key => $val) {
      if (keyVal('islbl',$rowinfo)) {
        $val = keyVal('label',$atts['display'][$key]);
      }
      if ($key === 'delete') {
        $rowinfo['delete']=$val ;
        $rowinfo['id'] = $id;
        $rowinfo['classname'] = $classname;
        continue;
      }
      //pkdebug("Atts:",$atts,"celldata", $celldata);

      if ($input) {
        $input = keyVal('input',$celldata[$key]);
        if ($input) {
          if ($isnew) {
            $cnt='__CNT_TPL__';
          } else {
            $cnt = keyVal('cnt',$rowinfo,0);
          }
          $name = keyVal('relation',$rowinfo)."[$cnt][$key]";
          $placeholder = keyVal('placeholder',$celldata[$key],
              keyVal('label',$celldata[$key]));
          if ($isnew) {
            $val=null;
          }
          $val = "<input class='rt-inp' type='$input' value='$val' name='$name'
            placeholder='$placeholder'/>";
        }
      }


      if (keyVal('islbl',$rowinfo)) {
        $val = keyVal('label',$atts['display'][$key]);
      }
      $celldataarr[] = keyVal($key,$celldata,[]) + ['field'=>$val];
    }
    pkdebug("Actual celldataarr:", $celldataarr);
    return ['celldataarr'=>$celldataarr, 'rowinfo'=>$rowinfo];
  }
}


