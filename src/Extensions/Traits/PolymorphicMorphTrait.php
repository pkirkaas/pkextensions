<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/** The 2 PolymorphicTraits - PolymorphicBaseTrait & PolymorphicMorphTrait:
 * This (PolymorphicMorphTrait) is implemented/used by Models that will extend/morph
 * common base Model
 * Example: Common Base: User.  Extended 'Morph' type: Borrower, Lender.
 * So this is implemented by the "Morph" model - Borrower or Lender
 * PolymorphiceBaseTrait is implmented by the "Base", or User.
 */

/** Supports the Morphing extensions that share a common base model. By definition,
 * there will be more than one, so there is an intermediate Abstract Morph model
 * which implements/uses this trait, and is extended by the various extensions/morphs.
 * 
 * Could implement/directly in Borrower & Lender Models, but still better to 
 * use in an abstract MorphSbbUser class, extended by Borrower & Lender, because then
 * both Borrower and Lender are instances of MorphSbbUser, which might be convenient
 *
 * Example: The shared extended base is "User" - the 'Morphing' models are
 * 'App\Models\Borrower' & 'App\Models\Lender'. Both will extend their common
 * abstract extension/morph class 'App\Models\UserMorph' - which is the class
 * which implements/uses this trait..
 *
 * There could be polymorphic users, polymorphic media, etc, so can't be a single
 * common polymorphic base base - but the base from which the extensions diverge
 * will use this trait and be called XXXPolymorphicBase 
 *
 * The classes that extend/diverge/polymorph will implement a PolymorphExtendTrait.
 * 
 * Provided to Implementers/Users
 * 
 * Required and Optional attributes/methods of PolyExtensions using this trait:
 * REQUIRED:
 * public static $morphFrom = 'App\Models\User' or whatever
 * 
 * OPTIONAL:
 * public $morphName = 'user'; or whatever. If not provided, the basename of
 *  $morphFrom will be used ('user'). This provides the $this->user() to return
 * the relation, and $this->user to return the value of the relation.
 */
namespace PkExtensions\Traits;
use PkExtensions\Models\PkModel;

Trait PolymorphicMorphTrait {
  use PkProxiedAttributesTrait;
  # Implementing class must declare static $morphFrom model; ex:
  # public static $morphFrom = 'App\Models\User';
  public static function getMorphName() {
    $class = static::class;
    if (property_exists('morphName', $class)) return static::$morphName;
    return strtolower(baseName(static::getMorphFrom()));
  }
  /** Returns the parent instance, of whatever type 
   * Among other things, used to get the real attribut/column names of parent
   */
  public function morphbase() {
    //return $this->morphOne(static::getMorphFrom(),getMorphBaseKeyForMe());
    $relation = $this->morphOne(static::getMorphFrom(),static::getMorphBaseKeyForMe());
    $this->setProxyRelationship($relation);
    return $relation;
  }




  public function __call($method, $args=[]) {
    if (strtolower($method) === strtolower(static::getMorphName())) {
      return call_user_func_array([$this,'traitTypeMorphOne'], $args);
    }
    return parent::__call($method, $args);
  }

  /*
  public function __get($key) {
    //pkdebug("KEY", $key);
    if (strtolower($key) === strtolower(static::getMorphName())) {
       return $this->getRelationValue('traitTypeMorphOne');
    }
     return parent::__get($key);
  }
   * 
   */
  public function __getPM($key) {
    //pkdebug("KEY", $key);
    if (strtolower($key) === strtolower(static::getMorphName())) {
       return $this->getRelationValue('traitTypeMorphOne');
    }
     //return parent::__get($key);
    return failure();
  }
  /*
   * 
   */

   /** Called by the implementing / using model - from the method definition
    * public function [$typeName] () { return $this
    * @param type $name
    * @param type $type
    * @param type $id
    */
   public function traitTypeMorphOne($morphFrom=null, $name=null, $type = null, $id = null) {
     $morphFrom = $morphFrom ? $morphFrom : static::getMorphFrom();
     $name = $name ? $name : static::getMorphBaseKeyForMe();
     //$name = $name ? $name : static::getMorphName();
     //$type = $type ? $type : static::getMorphBaseKeyForMe().'_type';
     //$id = $id ? $id : static::getMorphBaseKeyForMe().'_id';
     //var_dump(['morphFrom',$morphFrom,'name', $name,'id',$id,'type',$type]);
     return $this->morphOne($morphFrom, $name);
     //return $this->morphOne($morphFrom, $name,$type, $id);
   }

   public static function getMorphFrom() {
     return static::$morphFrom;
   }
   public static function getMorphBaseKeyForMe() {
     $morphFrom = static::getMorphFrom();
     return $morphFrom::getTypeName();
   }

}
