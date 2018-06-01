<?php
/*
 * Mate to PkTypedModelTrait - model which have Typed Models should use this
 */
namespace PkExtensions\Traits;
use PkExtensions\PkException;
use PkExtensions\PkExceptionResponsable;
/**
 * @author pkirkaas
 */
trait PkHasTypeModelTrait {
  /** If !$key, Returns the combined array definitions of typed members,
   * else the specific member def for $key ($att_name)
   * @param string|null $key
   * @return array - array of all defs, or the def array specified by $key
   */
  public static function getTypedMemberDefs($key=null) {
    $defs =  static::getArraysMerged('typedMemberDefs');
    if ($key) {
      return keyVal($key,$defs);
    }
    return $defs;
  }

  /** Returns typed members/relations as specified in $params - which can be
   * a string, key to the typed def, or an array if more filtering required
   * @param string|array $params
   * @return Builder - in case we want more filtering
   */
  public function getTypedMembers($params = null) {
    if (is_string ($params)) {
      $params = ['key'=>$params];
    }
    $key = $params['key'];
    $def = static::getTypedMemberDefs($key);
    $model=$def['model'];
    $filterKeys = array_keys($model::getTypedFields());
    $builder=$model::where($this->getForeignKey(),$this->id);
    foreach ($params as $filter=>$val) {
      if (in_array($filter, $filterKeys,1)) {
        $builder->where($filter,$val);
      }
    }
    return $builder;
  }

  /** The typed model must implement PkTypedModelTrait, & have an 
   * att_name property that's the key to this owner's typedMemberDefs
   * @param type $typedModel
   */
  public function addTypedMember($typedModel) {
    $att_name = $typedModel->att_name;
    $def = static::getTypedMemberDefs($att_name);
    if (!ne_array($def)) {
      throw new \Exception("'$att_name' not a valid member");
    }
    $model = $def['model'];
    if (! $typedModel instanceOf $model) {
      throw new PkException("Wrong type of model for member");
    }
    #Check all the properties meet the requirements
    $filterKeys = array_keys($model::getTypedFields());
    $defKeys = array_keys($def);
    foreach ($filterKeys as $filterKey) {
      if (in_array($filterKey, $defKeys)) {
        if ($typedModel->$filterKey !== $def[$filterKey]) {
          throw new PkException("Model didn't meet membership req");
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

}
