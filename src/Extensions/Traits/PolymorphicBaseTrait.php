<?php
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
   public static $polytypes = ['App\Models\Borrower', 'App\Models\Lender'];
   public function [$typeName]() { return $this->traitTypeMorphTo() }

   * The Type name will be built from the Base class name of the Poly Extended class,
   * so "Lender' & 'Borrower' - that's what willl be stored in the 'type_type' field.

   * OPTIONAL:
   static::$typeName='imageable'; #Makes 'imageable_type' & 'imageable_id'
        #Otherwise default field names: 'type_type' & 'type_id'
    protected $table = 'users'; #If you want to specify the table name
   */

   public static $bases = [];
   public static function getPolytypes() {
     return static::$polytypes;
   }
   public static function getPolyBases() {
     $class = static::class;
     if (array_key_exists($class, static::$bases)) return static::$bases[$class];
     $polyTypes = static::getPolyTypes();
     static::$bases[$class] = [];
     foreach ($polyTypes as $polyType) {
       static::$bases[$class][] = getBaseName($polyType);
     }
     return static::$bases[$class];
   }

   public static function getExtensionFieldDefs() {
     //$class = static::class;
     $typeName=static::typeName();
     return [
       $typeName.'_id' => ['type'=>'integer','methods'=>'index'],
       $typeName.'_type' => 'string',
       ];
   }

   /** Called by the implementing / using model - from the method definition
    * public function [$typeName] () { return $this
    * @param type $name
    * @param type $type
    * @param type $id
    */
   public function traitTypeMorphTo($name = null, $type = null, $id = null) {
     $name = $name ? $name : static::getTypeName();
     $type = $type ? $type : static::getTypeName().'_type';
     $id = $id ? $id : static::getTypeName().'_id';
     return $this->morphTo($name, $type, $id);
   }

  public static function typeName() {
     $class = static::class;
     if (property_exists($class,'typeName')) return static::$typeName;
     return 'type';
  }
}
