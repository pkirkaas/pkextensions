<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions\Traits;
/**
 * Share CriteriaSets between PkMatch Model, & BuildQueryTrait
 *
 * @author pkirk
 */
Trait CriteriaSetsTrait {
  public static $criteriaSets = [
      'numeric' => [
          '0' => "Don't Care",
          '<' => 'Less Than',
          '<=' => 'At Most',
          '>=' => 'At Least',
          '>' => 'More Than',
          '=' => 'Equal To',
          '!=' => 'Not Equal To',
      ],
      'string' => [
          '0' => "Don't Care",
          'LIKE' => 'Is',
          'LIKE%' => 'Starts With',
          '%LIKE' => 'Ends With',
          '%LIKE%' => 'Contains',
      ],
      'group' => [
          '0' => "Don't Care",
          'IN' => 'In',
          'NOTIN' => 'Not In',
      ],
      #Intersects is like in a 'group' - but rather than 1 value in an array,
      #do TWO arrays have any common values? Tough to do w. SQL
      'intersects' => [
          '0' => "Don't Care",
          'IN' => 'In',
          'NOTIN' => 'Not In',
      ],
      'within' => [
          '0' => "Don't Care",
          '1' => 'Within 1 mile',
          '5' => 'Within 5 miles',
          '10' => 'Within 10 miles',
          '20' => 'Within 20 miles',
          '50' => 'Within 50 miles',
      ],
      'between' => [
          '0' => "Don't Care",
          'BETWEEN' => 'Between',
          'NOT BETWEEN' => 'Not Between',
      ],
      'boolean' => [
          '0' => "Don't Care",
          'IS' => 'Required',
          'IS NOT' => "Excluded",
      ],
      'exists' => [
          '0' => "Don't Care",
          'EXISTS' => 'Required',
          'NOT EXISTS' => "Doesn't have",
      ],
      'date' => [
          '0' => "Don't Care",
          '<' => 'Before',
          '>' => 'After',
          '=' => 'At',
          '!=' => 'Not At',
      ],

      'exact' => [
         '0' => "Don't Care",
         "=" => "Required",
         "!=" => "Excluded",
         ], #Seems like boolean, except it's for a specific value,
            #probably in a select dropdown from a reference array

         #Matching a property of a 'many' side - like clients missed appts.
          'oneofmany' => [
             '0' => "Don't Care",
             "IN" => "With",
             "NOT IN" => "Without",
             ],
              
  ];

/** Existential queries that don't require values */
  public static function noval($comptype) {
    return in_array($comptype,['boolean','exists'],1);
  } 

  /**
   * 
   * @param string $type - the criteria type - like, 'group', 'numeric', 'string'
   * @param null|array $omit - if $type && is_array($omit), $omit is the array
   *   of criteria to omit/remove from the results
   * @return type
   */

  public static function getCriteriaSets($type = null, $custom = null) {
    if (!$type) return static::$criteriaSets;
    $rawCritSet = keyVal($type, static::$criteriaSets, []);
    if (!$custom || !is_array($custom)) {
      return $rawCritSet;
    }
    $omit = keyVal('omit',$custom);
    if (is_array_idx($omit) && count($omit)) {
      foreach ($omit as $oc) {
        unset($rawCritSet[$oc]);
      }
      unset($custom['omit']);
    }
    if (count($custom)) {
      foreach ($custom as $key=>$label) {
        $rawCritSet[$key]=$label;
      }
    }
    return $rawCritSet;
  }

  /**
   * 
   * @param string $crit - Criterion value, like '>=', 'EXISTS', etc.
   * @param string|null $type - like, 'group', 'numeric', etc.
   *   if present, only looks for $crit in that group, otherwise all criteria
   */
  public static function isValidCriterion($crit, $type = null) {
    $cs = static::getCriteriaSets();
    // OK, so this works to get CS: 
    pkdebug("Res of getCriteriaSets:", $cs);
    foreach ($cs as $ctype => $criteria) {
      if ((!$type || ($type === $ctype)) && in_array($crit, array_keys($criteria))) {
        pkdebug("crit [$crit], of ctype: [$ctype] IS valid, with criteria:", $criteria);
        return true;
      }
    }
    return false;
  }

  public static function getCriteriaTypes() {
    return array_keys(static::getCriteriaSets());
  }

  public static function isValidCompType($comptype = null) {
    if (in_array($comptype, static::getCriteriaTypes(), 1)) return $comptype;
    return false;
  }

  public static function getTypeFromCrit($crit) {
    if (!$crit || ($crit === '0')) return null;
    foreach (static::getCriteriaSets() as $comptype => $critarr) {
      if (in_array($crit, array_keys($critarr, 1))) return $comptype;
    }
  }

}
