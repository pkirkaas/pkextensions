<?php
namespace PkExtensions;
use \Exception;
use PkExtensions\Traits\CriteriaSetsTrait;
/**
 * For searching Collections of Models
 * We run a match method on a model value
 *  Way too simple to call it a query - just does a simple match on simple criteria
 * It has two levels of variables - the names and types of fields it should match -
 * but then has values and criteria
 * 
 * I think I originally created PkMatch to filter on model method results, which
 * couldn't be SQLed - but also useful for difficult things like array intersection,
 * like keeping multiple choices in a json string in the DB
 * 
 * Get $matchObj->satisfy($field_Value).
 */
class PkMatch {
  use CriteriaSetsTrait;
#If 0/False, simple attribute, else 'method', 'function', etc
  public $parameters; #Opt Arr - not only methods take args, 'within' & 'between' as well
  public static $attributeNames = [
      'active',
      'comptype',
      'crit',
      'val',
      'minval',
      'maxval',
      'parms',
      'targettype',
      'callable',
      'targetname',
      'params',
      'method',
      'attribute',
      'model',
      'table',
      'fieldtype',
  ];

  /** Takes an array of PkMatch objects and filters them according to 
   * $params, an array of $key/$value options
   * @param array $matchArr
   * @param array $params - options:
   *   'modelName' => 'App\ModelName': only those PkMatch objs for that class
   *   'modelMethods' => true - filter only those of type method, existing on obj
   *   'emptyCrit' => true - filter those w. no criterea
   * @return array of PkMatch Objs
   */
  public static function filterMatchArr($matchArr, $params = []) {
    if (!is_arrayish($matchArr)) return [];
    if (!$params) return $matchArr;
    $trimArr = [];
    $modelName = keyVal('modelName', $params);
    $modelMethodsFilter = keyVal('modelMethods', $params, 'true');
    if ($modelMethodsFilter) $modelMethods = get_class_methods($modelName);
    $emptyCrit = keyVal('emptyCrit', $params, true);
    foreach ($matchArr as $base => $match) {
      if (!is_a($match->model, $modelName, true)) {
        //pkdebug("Failed for [$modelName] && ", $match);
        continue;
      }
      if (!$match->method || !in_array($match->method, $modelMethods, 1)) {
        //pkdebug("Failed for [match ] && methods", $match);
        continue;
      }
      if ($emptyCrit && !$match->crit) {
        //pkdebug("Failed for [match ] && crit", $match);
        continue;
      }
      $trimArr[$base] = $match;
    }
    //pkdebug("The Trimmed Arr", $trimArr);
    return $trimArr;
  }

  /** Enable making one match obj at a time, should be the value
   *  for the matchObj array keyed by $basename
   * @param type $arrSet
   * @param str $baseName - nice to have
   * 
   */
  public static function mkMatchObj($arrSet,$baseName = null) {
      $marr = [];
      //if ($baseName === 'yrsest') pkdebug("BNAME:  $baseName, arrSetPre", $arrSet);
      if (!$fs = keyVal('field_set', $arrSet)) {
        $fs = keyVal('field_defs', $arrSet);
        if (!$fs) return;
      }
      //if ($baseName === 'yrsest') pkdebug("POSTNAM:  $baseName, arrSetPOST", $arrSet);
      if (array_key_exists('val', $fs)) $marr['val'] = $fs['val'];
      if (array_key_exists('crit', $fs)) $marr['crit'] = $fs['crit'];
      if (array_key_exists('minval', $fs)) $marr['minval'] = $fs['minval'];
      if (array_key_exists('maxval', $fs)) $marr['maxval'] = $fs['maxval'];
      $meta = keyVal('meta', $arrSet);
      $comptype = firstKeyVal('comptype', $fs, $arrSet, $meta);
      if (!$comptype) $comptype = static::getTypeFromCrit(keyVal('crit', $marr));
      if (!$comptype) {
        if (array_key_exists('minval', $fs) || array_key_exists('maxval', $fs))
            $comptype = 'between';
      }
      if (!$comptype && is_array(keyVal('val', $marr))) $comptype = 'group';
      if (!static::isValidCompType($comptype)) return;
      $marr['comptype'] = $comptype;
      $marr['compfield'] = $baseName;
      foreach (static::$attributeNames as $attName) {
        //if ($baseName === 'yrsest') pkdebug("Looking for attName: $attName");
        if ($tst = firstKeyVal($attName, $meta, $arrSet, $fs)) {
          $marr[$attName] = $tst;
        }
      }
      return new PkMatch($marr);
  }

  /** Takes a baseKeyed array and turns it into a array of match objs */
  public static function matchFactory($arrSets = [], $params = []) {
    //pkdebug("Enter MatchFact, arrstes:", $arrSets);
    foreach (array_keys($arrSets) as $key) {
      if (removeEndStr($key, '_crit') || removeEndStr($key, '_val')) {
        $arrSets = static::flatToStructured($arrSets, $params);
        break;
      }
    }
    //pkdebug("REFACTOR MatchFact, arrstes:", $arrSets);
    $matchArr = [];
    foreach ($arrSets as $baseName => $arrSet) {
      $matchArr[$baseName] = static::mkMatchObj($arrSet,$baseName);

      /*


      // pkdebug("BASENEAME: [$baseName] ARRSET: ",$arrSet);
      $marr = [];
      //if ($baseName === 'yrsest') pkdebug("BNAME:  $baseName, arrSetPre", $arrSet);
      if (!$fs = keyVal('field_set', $arrSet)) {
        $fs = keyVal('field_defs', $arrSet);
        if (!$fs) continue;
      }
      //if ($baseName === 'yrsest') pkdebug("POSTNAM:  $baseName, arrSetPOST", $arrSet);
      if (array_key_exists('val', $fs)) $marr['val'] = $fs['val'];
      if (array_key_exists('crit', $fs)) $marr['crit'] = $fs['crit'];
      if (array_key_exists('minval', $fs)) $marr['minval'] = $fs['minval'];
      if (array_key_exists('maxval', $fs)) $marr['maxval'] = $fs['maxval'];
      $meta = keyVal('meta', $arrSet);
      $comptype = firstKeyVal('comptype', $fs, $arrSet, $meta);
      if (!$comptype) $comptype = static::getTypeFromCrit(keyVal('crit', $marr));
      if (!$comptype) {
        if (array_key_exists('minval', $fs) || array_key_exists('maxval', $fs))
            $comptype = 'between';
      }
      if (!$comptype && is_array(keyVal('val', $marr))) $comptype = 'group';
      if (!static::isValidCompType($comptype)) continue;
      $marr['comptype'] = $comptype;
      $marr['compfield'] = $baseName;
      foreach (static::$attributeNames as $attName) {
        //if ($baseName === 'yrsest') pkdebug("Looking for attName: $attName");
        if ($tst = firstKeyVal($attName, $meta, $arrSet, $fs)) {
          $marr[$attName] = $tst;
        }
      }



      $matchArr[$baseName] = new PkMatch($marr);
      */
    }


    return $matchArr;
  }

  static $fieldendings = ['crit', 'val', 'parm0', 'parm1', 'parm2', 'maxval', 'minval',];
  static $metaendings = ['comptype', 'fieldtype', 'runnable', 'callable', 'targettype', 'comptarget',];

  /* Creates and returns a structured array from flat, to build a match set
   * of Matches based on the data args and params
   * Could be a flat array like from a DB table -
   *  ['fieldname_crit'=>$crit, 'fieldname_val'=>$val, etc
   */

  public static function flatToStructured($arrSets = [], $params = []) {
    $basekeyed = [];
    foreach ($arrSets as $key => $val) {
      foreach (static::$fieldendings as $end) {
        if ($base = removeEndStr($key, '_' . $end)) {
          if (!array_key_exists($base, $basekeyed)) {
            $basekeyed[$base] = [];
          }
          if (!array_key_exists('field_set', $basekeyed[$base])) {
            $basekeyed[$base]['field_set'] = [];
          }
          $basekeyed[$base]['field_set'][$end] = $val;
        }
      }
      foreach (static::$metaendings as $end) {
        if ($base = removeEndStr($key, '_' . $end)) {
          if (!array_key_exists($base, $basekeyed)) {
            $basekeyed[$base] = [];
          }
          if (!array_key_exists('meta', $basekeyed[$base])) {
            $basekeyed[$base]['meta'] = [];
          }
          $basekeyed[$base]['meta'][$end] = $val;
        }
      }
    }

    return $basekeyed;
  }

  /** Is $crit a valid DB criterion?
   * 
   * @param string $crit
   * @param string|null $type - if null, ANY valid criteria type is valid; else $type is a specific
   *    criteria type and $crit is only valid in that group. Like, "<" for 'numeric"
   * if crit is of type 'numeric'|'string'|'group'
   * @return boolean
   */
  public static function isValidCriterion($crit, $type = null) {
    foreach (static::getCriteriaSets() as $typeKey => $critVals) {
      if ((!$type || ($type === $typeKey)) && in_array($crit, $critVals, 1))
          return true;
    }
    return false;
  }

  public $attributes = [];

  public function __construct($argarr = []) {
    $this->attributes = $argarr;
  }

  /** Evaluates this Match Object with the option arg, returns true or
   * false
   * @param type $arg
   */
  public function satisfy($arg = null) {
    //pkdebug("Trying to satisfy this...", $this, 'with arg', $arg);
    if (!$this->crit || ($this->crit === '0')) return true;
    if ($this->comptype === 'string') return $this->stringComp($arg);
    if ($this->comptype === 'numeric') return $this->numericComp($arg);
    if ($this->comptype === 'group') return $this->groupComp($arg);
    if ($this->comptype === 'within') return $this->withinComp($arg);
    if ($this->comptype === 'between') return $this->betweenComp($arg);
    if ($this->comptype === 'exists') return $this->existsComp($arg);
    if ($this->comptype === 'intersects') return $this->intersectsComp($arg);
    throw new \Exception("Unknown comparison type: " . $this->comptype);
  }

  public function stringComp($arg = null) {
    if ($this->crit === 'LIKE') {
      return $this->val === $arg;
    }
    if ($this->crit === '%LIKE') {
      return removeEndStr($arg, $this->val);
    }
    if ($this->crit === 'LIKE%') {
      return removeStartStr($arg, $this->val);
    }
    if ($this->crit === '%LIKE%') {
      return strpos($arg, $this->val) !== false;
    }
    throw new \Exception("Unknown [{$this->crit}] for comptype: {$this->comptype}");
  }

  public function groupComp($arg = null) {
    if ($this->crit === 'IN') {
      if (!$this->val) return false;
      if (!is_array($this->val))
          throw new Exception("Invalid val:" . print_r($this->val, 1));
      return (in_array($arg, $this->val));
    }
    if ($this->crit === 'NOTIN') {
      if (!$this->val) return true;
      if (!is_array($this->val))
          throw new Exception("Invalid val:" . print_r($this->val, 1));
      return (!in_array($arg, $this->val));
    }
    throw new Exception("Unknown [{$this->crit}] for comptype: {$this->comptype}");
  }

  /** $arg has to be an array or string to be json_decoded 
   * 
   * @param type $arg
   */
  function intersectsComp($arg = null) {
    if (ne_string($arg)) {
      $arg = json_decode($arg,1);
    }
    if (!is_array($arg)) return false;
    if ($this->crit === 'IN') {
      if (!$this->val) return false;
      if (!is_array($this->val)) {
          throw new Exception("Invalid val:" . print_r($this->val, 1));
      }
      return !!count(array_intersect($arg, $this->val));
    }
    if ($this->crit === 'NOTIN') {
      if (!$this->val) return true;
      if (!is_array($this->val)){
          throw new Exception("Invalid val:" . print_r($this->val, 1));
      }
      return !count(array_intersect($arg, $this->val));
    }
    throw new Exception("Unknown [{$this->crit}] for comptype: {$this->comptype}");
  }
    


  public function withinComp($arg = null) {
    throw new Exception("'WITHIN' not supported yet");
  }

  public function betweenComp($arg = null) {
    if (!is_numeric($arg)) { #Can we turn it into a date? 
      $arg = strtotime($arg);
    }
    if ($arg === false) return $this->crit !== 'BETWEEN';
    $minval = $this->min;
    $maxval = $this->maxval;
    if (!is_numeric($minval)) $minval = strtotime($min);
    if (!is_numeric($maxval)) $maxval = strtotime($maxval);
    $between = ($maxval !== false) && ($minval !== false) && ($arg <= $maxval) && ($arg >= $minval);
    if ($this->crit === 'BETWEEN') return $between;
    if ($this->crit === 'NOT BETWEEN') return !$between;
    throw new Exception("Unknown [{$this->crit}] for comptype: {$this->comptype}");
  }

  public function existsComp($arg = null) {
    return ($this->crit === 'EXISTS') && $arg;
  }

  public function numericComp($arg = null) {
    if ($arg === null) return false;
      //if ($this->compfield == 'assetdebtratio')  pkdebug("YES: SUCCESSFULLY GOT TO NUMERIC, ARG:", $arg,$this);
    //pkdebug("ArG",$arg,'this', $this);
    if (!is_numeric($this->val) || !is_numeric($arg)) {
      //throw new Exception ("[{$this->val}] or [$arg] is not numeric");
      return pkwarn("A PROBLEM: For thisMatch;", $this, 'this->val', $this->val, " or [arg] ", $arg, " is not numeric");
    }
    /** Restore to this clean state when debugged...
      if ($this->crit === '<' ) return $arg <  $this->val;

      if ($this->crit === '<=') return $arg <=  $this->val;

      if ($this->crit === '>=') return $arg >=  $this->val;

      if ($this->crit === '>')   return $arg >  $this->val;
      if ($this->crit === '=' ) return $arg == $this->val;
      if ($this->crit === '!=' ) return $arg != $this->val;
     * 
     */
    if ($this->crit === '<') {
      $res = ($arg < $this->val);
      //if ($this->compfield == 'assetdebtratio')  pkdebug("adet: THIS:", $this, 'ARG', $arg, 'res', $res);
      return $res;
    }

    if ($this->crit === '<=') {
      $res = ( $arg <= $this->val);
      //if ($this->compfield == 'assetdebtratio') pkdebug("adet: THIS:", $this, 'ARG', $arg, 'res', $res);
      return $res;
    }

    if ($this->crit === '>=') {
      $res = ($arg >= $this->val);
      //if ($this->compfield == 'assetdebtratio') pkdebug("adet: THIS:", $this, 'ARG', $arg, 'res', $res);
      return $res;
    }

    if ($this->crit === '>') {
      $res = ($arg > $this->val);
      //if ($this->compfield == 'assetdebtratio') pkdebug("adet: THIS:", $this, 'ARG', $arg, 'res', $res);
      return $res;
    }
    if ($this->crit === '=') {
      $res = ( $arg == $this->val);
      //if ($this->compfield == 'assetdebtratio') pkdebug("adet: THIS:", $this, 'ARG', $arg, 'res', $res);
      return $res;
    }
    if ($this->crit === '!=') {
      $res = ($arg != $this->val);
      //if ($this->compfield == 'assetdebtratio') pkdebug("adet: THIS:", $this, 'ARG', $arg, 'res', $res);
      return $res;
    }
    throw new Exception("Unknown [{$this->crit}] for comptype: {$this->comptype}");
  }

  public function __get($key) {
    return keyVal($key, $this->attributes);
  }

  public function __set($key, $val) {
    $this->attributes[$key] = $val;
  }

  /** Should take a wide variety of formats & convert. Examples:
   * ['attr1_val=>$val1,'attr1_crit'=>$crit1,'parms'=>[], 
   */
//public function set($argarr = []) {
}
