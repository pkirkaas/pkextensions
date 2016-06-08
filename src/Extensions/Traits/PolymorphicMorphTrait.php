<?php
/** The 2 PolymorphicTraits - PolymorphicBaseTrait & PolymorphicMorphTrait:
 * This (PolymorphicMorphTrait) is implemented/used by Models that will extend/morph
 * common base Model
 * Example: Common Base: User.  Extended 'Morph' type: Borrower, Lender.
 */

/** Supports the Morphing extensions that share a common base model. By definition,
 * there will be more than one, so there is an intermediate Abstract Morph model
 * which implements/uses this trait, and is extended by the various extensions/morphs.
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
 */
namespace PkExtensions\Traits;
use PkExtensions\Models\PkModel;

Trait PolymorphicMorphTrait {
}
