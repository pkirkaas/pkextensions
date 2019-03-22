<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/*
 * Mate to PkTypedModelTrait - model which have Typed Models should use this
 */
namespace PkExtensions\Traits;
use PkExtensions\PkException;
use PkExtensions\PkFileUploadService;
use PkExtensions\PkExceptionResponsable;
/**
 * @author pkirkaas
 */
trait PkHasTypedModelTrait {
  /** If !$key, Returns the combined array definitions of typed members,
   * else the specific member def for $key ($att_name)
   * @param string|null $key
   * @return array - array of all defs, or the def array specified by $key
   */
  public static function getTypedMemberDefs($key=null) {
    $defs =  static::getArraysMerged('typedMemberDefs');
    if ($key) {
      $def = keyVal($key,$defs);
      //pkdebug("TypedMemberDefs: Class: ".static::class."; Key: [$key], def: ",$def," defs: ", $defs, "Cache:", static::$_cache);

      return $def;
      return keyVal($key,$defs)+['att_name'=>$key];
    }
    return $defs;
  }

  /** Returns typed members/relations as specified in $params - which can be
   * a string, key to the typed def, or an array if more filtering required
   * @param string|array $params
   * @return Builder - in case we want more filtering
   */
  public function getTypedMembersBuilder($params = []) {
    if (is_string ($params)) {
      $params = ['key'=>$params];
    }
    $key = $params['key'];
    $def = static::getTypedMemberDefs($key);
    $model=$def['model'];
    $att_name = $def['att_name'];
    $filterKeys = array_keys($model::getTypedFields());
    $builder=$model::where($this->getForeignKey(),$this->id)->where('att_name',$att_name);
    foreach ($params as $filter=>$val) {
      if (in_array($filter, $filterKeys,1)) {
        $builder->where($filter,$val);
      }
    }
    return $builder;
  }

  public function getTypedMembers($params=[]) {
    $builder = $this->getTypedMembersBuilder($params);
    if (is_string ($params)) {
      $params = ['key'=>$params];
    }
    $key = $params['key'];
    $def = static::getTypedMemberDefs($key);
    if (keyVal('single',$def)) {
      return $builder->first();
    }
    return  $builder->get();
  }

  /** The typed model must implement PkTypedModelTrait, & have an 
   * att_name property that's the key to this owner's typedMemberDefs
   * @param typeModel $typedModel
   */
  public function addTypedMember($typedModel) {
    $att_name = $typedModel->att_name;
    $def = static::getTypedMemberDefs($att_name);
    if (!ne_array($def)) {
      throw new PkExceptionResponsable("'$att_name' not a valid member");
    }
    $model = $def['model'];
    if (! $typedModel instanceOf $model) {
      throw new PkExceptionResponsable("Wrong type of model for member");
    }
    #Check all the properties meet the requirements
    $filterKeys = array_keys($model::getTypedFields());
    $defKeys = array_keys($def);
    foreach ($filterKeys as $filterKey) {
      if (in_array($filterKey, $defKeys)) {
        if ($typedModel->$filterKey !== $def[$filterKey]) {
          throw new PkExceptionResponsable("Model didn't meet membership req");
        }
      }
    }
    $fk = keyVal('foreign_key', $def, $this->getForeignKey());
    $typedModel->$fk = $this->id;
    if (keyVal('single', $def)) { #Remove any existing
      $model::where($fk,$this->id)->where('att_name',$att_name)->delete();
    }
    $typedModel->save();
    return $typedModel;
  }

  /*
    'avatar' =>['model'=>'App\Models\ProfileUpload', 'type'=>'image','single'=>true,
        'att_label'=>'Avatar', 'att_name'=>'avatar'
        ],
   * 
   */
  public function makeTypedMember($key,$params=[]) {
    $def = static::getTypedMemberDefs($key);
    $model = $def['model'];
    if ($model::usesTrait(PkUploadTrait::class)) {
      $uploadService = new PkFileUploadService();
      $uploadArr = $uploadService->upload(array_merge($def,$params));
    } else {
      $uploadArr = [];
    }
    pkdebug("UploadArr: ", $uploadArr, "def: ",$def, 'params', $params);
    $fk = keyVal('foreign_key', $def, $this->getForeignKey());
    $params[$fk]=$this->id;
    $typedMember = new $model(array_merge($uploadArr,$def,$params));
    return $this->addTypedMember($typedMember);
  }

}
