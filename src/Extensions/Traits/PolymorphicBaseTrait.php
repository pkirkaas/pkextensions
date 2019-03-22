<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
/** The 2 PolymorphicTraits - PolymorphicBaseTrait & PolymorphicMorphTrait:
 * This (PolymorphicBaseTrait) is implemented/used by Models that will be the
 * common basis for the extended "Morph" Models. 
 * Example: Common Base: User.  Extended 'Morph' type: Borrower, Lender.
 */

/** Supports the base model that polymorphic extensions share.
 * There could be polymorphic users, polymorphic media, etc, so can't be a single
 * common polymorphic base base - but the base from which the extensions diverge
 * will use this trait and be called XXXPolymorphicBase 
 *
 * The classes that extend/diverge/polymorph will implement a PolymorphExtendTrait.
 */
namespace PkExtensions\Traits;
use PkExtensions\Models\PkModel;

Trait PolymorphicBaseTrait {
  /** A polymorphic base model needs to have the table/class polymorph
   * type - like "borrower" or "lender", and needs to have an ID/Key 
   * to the matching record in that table. By default this trait defines
   * those fields as "type_type" (string) and "type_id" (int), but implementing
   * classes can override. ALSO: This Trait assumes an implementing Polymorphic
   * Base Class will know what models it can polymorph into, and create a
   * static array of fully qualified class names:

   * Using the Trait: Implementing PolyBaseModel
   REQUIRED:
   //public function [$typeName]() { return $this->traitTypeMorphTo() }

   * The Type name will be built from the Base class name of the Poly Extended class,

   * OPTIONAL:
   #if you want to automate isBorrower(), isLender(), etc in the PolymorphBase
   public static $polytypes = ['App\Models\Borrower', 'App\Models\Lender'];

   static::$typeName='imageable';
    #Makes table fields 'imageable_type' & 'imageable_id', and implements the 
     method $this->imageable() to return the morphTo() relation.
        #Otherwise default field names: 'type_type' & 'type_id'
    protected $table = 'users'; #If you want to specify the table name
   */

   /** Returns the implementing class's statically decared '$polytypes'
    * fully namespaced extension array - 
    *  example: ['App\\Models\\Borrower', 'App\\Models\\Lender']
    * @return array of namespaced Morph/Extension Model, keyed by lc base names,
    * like: ['borrower'=>'App\Models\Borrower', 
    */

   public static function getPolyTypes() {
     $spt = static::$polytypes;
     $closure = function()use($spt) {
      $polyTypes = [];
      foreach ($spt as $polytype) {
       $polyTypes[strtolower(getBaseName($polytype))] = $polytype;
      }
      return $polyTypes;
     };
     return static::getCached('polyTypes', $closure);
   }

   public static function getTypeId() {
     return static::getTypeName().'_id'; 
   }
   public static function getTypeField() {
     return static::getTypeName().'_type'; 
   }
   public static function getTableFieldDefsExtraMorphBase() {
     return [
       static::getTypeId()=> ['type'=>'integer','methods'=>'index'],
       static::getTypeField()=> 'string',
       ];
   }

   /** Automates the 'morphTo()' method, and is[$type] - like, isBorrower()
    * 
    * @param type $method
    * @param type $args
    * @return type
    */
   public function __call($method, $args=[]) {
     if (strtolower($method) === strtolower(static::getTypeName())) {
       /*
       $key = strtolower($method);
       $res = $this->getICache($key);
       if ($res !== false) {
         return $res;
       }
       return $this->setICache($key,call_user_func_array([$this,'traitTypeMorphTo'],$args));
        * 
        */
       return call_user_func_array([$this,'traitTypeMorphTo'],$args);
     }
     $tstType = removeStartStr($method, 'is');
     if ($tstType && is_string($tstType)) {
       $key=$method;
       //$res = $this->getICache($key);
       //if ($res === false) {
         $tstType = strtolower($tstType);
         $polyTypes = static::getPolytypes();
         if (in_array($tstType, array_keys($polyTypes))) {
        //   $res= $this->setICache ($key, 
            return $polyTypes[$tstType] === $this->getMorphType();
         }
       return false;
     }
     return parent::__call($method, $args);
   }

   public function __get($key) {
     if ($key === static::getTypeName()) {
       /*
       $res = $this->getICache($key);
       if ($res !== false) {
         return $res;
       }
       return $this->setICache($key,$this->getRelationValue('traitTypeMorphTo'));
        * 
        */
       return $this->getRelationValue('traitTypeMorphTo');
     }
     return parent::__get($key);
   }

   /** Is the argument an instance of this class AND the same morph type?
    * 
    * @param object $other
    * @return boolean
    */
  public function sameType( $other) {
    if (!is_object($other) || (get_class() !== get_class($other))) return false;
    return $this->getMorphType() === $other->getMorphType();
  }

  /**Returns the value of the morph type for this instance ('App\Models\Borrower')
   * 
   */
  public function getMorphType() {
    $typeField = static::getTypeField();
    return $this->$typeField;
  }

  public function getBaseMorphType() { #Like, just "Borrower"
    return basename(str_replace('\\','/',$this->getMorphType()));
    //$typeField = static::getTypeField();
    //return $this->$typeField;
  }

  /*
   public static function getTableFieldDefs() {
     $_fieldDefs = static::_getTableFieldDefs();
     $_polyBaseFieldDefs = static::getPolyBaseFieldDefs();
     $joinedFieldDefs = array_merge($_fieldDefs, $_polyBaseFieldDefs);
     return $joinedFieldDefs;
   }
   * 
   */

   /** Called by the implementing / using model - from the method definition
    * public function [$typeName] () { return $this
    * @param type $name
    * @param type $type
    * @param type $id
    */
   public function traitTypeMorphTo($name = null, $type = null, $id = null) {
     /*
     $key='traitMorphTo:name:'.$name.'type:'.$type."id:".$id;
     $res = $this->getICache($key);
     if ($res === false) {
       //$closure=
        $res = $this->setICache($key,
          (function()use($name, $type, $id) {
            $name = $name ? $name : static::getTypeName();
            $type = $type ? $type : static::getTypeName().'_type';
            $id = $id ? $id : static::getTypeName().'_id';
            return $this->morphTo($name, $type, $id);
        })());
     }
     return $res;
      * 
      */
      $name = $name ? $name : static::getTypeName();
      $type = $type ? $type : static::getTypeName().'_type';
      $id = $id ? $id : static::getTypeName().'_id';
      return $this->morphTo($name, $type, $id);
   }

  public static function getTypeName() {
     $class = static::class;
     if (property_exists($class,'typeName')) return static::$typeName;
     return 'type';
  }

  public function delete($cascade = true) {
    pkdebug("The result of this delete:",$this->type->delete($cascade));
    return parent::delete($cascade);
  }
}
