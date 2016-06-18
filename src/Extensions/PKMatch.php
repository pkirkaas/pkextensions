<?php

namespace PkExtensions;
use \Exception;

/** Way too simple to call it a query - just does a simple match on simple criteria
 * It has two levels of variables - the names and types of fields it should match -
 * but then has values and criteria
 */
class PkMatch {

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
          '%LIKE' => 'Starts With',
          'LIKE%' => 'Ends With',
          '%LIKE%' => 'Contains',
      ],
      'group' => [
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
      'exists' => [
          '0' => "Don't Care",
          'EXISTS' => 'Exists',
          'NOT EXISTS' => "Doesn't Exist",
      ],
  ];
  /*
  public $active; #Should this match be run?
  public $comptype; #Like, 'numeric', 'string', 'between', etc
  public $criterion; # Like 0 (Don't care), >, <, 'LIKE%', etc
  public $compvalue; # Should it be > 7? Or an array, like for 'group' or 'between'
  public $comptarget; #ModelName, TableName, or none
  public $targettype; #'table', 'model', etc
  public $compfield; #Field/Attribute name like 'user_id', or a method name
  public $callable; #Is it a simple filed/attribute name, or a callable method/function/etc
   * 
   */
#If 0/False, simple attribute, else 'method', 'function', etc
  public $parameters; #Opt Arr - not only methods take args, 'within' & 'between' as well

  public static function getCriteriaSets($key = null) {
    if (!$key) return static::$criteriaSets;
    return keVal($key, static::$criteriaSets);
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
    $modelName = keyVal('modelName',$params);
    $modelMethodsFilter = keyVal('modelMethods', $params,'true');
    if ($modelMethodsFilter) $modelMethods = get_class_methods($modelName);
    $emptyCrit = keyVal('emptyCrit', $params, true);
    foreach ($matchArr as $base => $match) {
      if (!is_a($match->model, $modelName, true)) {
        //pkdebug("Failed for [$modelName] && ", $match);
        continue;
      }
      if (!$match->method || !in_array($match->method,$modelMethods,1)) {
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

  /** Takes a baseKeyed array and turns it into a array of match objs */
  public static function matchFactory($arrSets = [], $params = []) {
    //pkdebug("Enter MatchFact, arrstes:", $arrSets);
    foreach (array_keys($arrSets) as $key) {
      if (removeEndStr($key,'_crit') || removeEndStr($key,'_val')) {
        $arrSets = static::flatToStructured($arrSets, $params);
        break;
      }
    }
    //pkdebug("REFACTOR MatchFact, arrstes:", $arrSets);
    $matchArr = [];
    foreach ($arrSets as $baseName => $arrSet) {
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
      if (!$comptype)  $comptype = static::getTypeFromCrit(keyVal('crit',$marr));
      if (!$comptype) {
        if (array_key_exists('minval',$fs ) || array_key_exists('maxval',$fs)) $comptype = 'between';
      }
      if (!$comptype && is_array(keyVal('val', $marr))) $comptype = 'group';
      if (!static::isValidCompType($comptype)) continue;
      $marr['comptype'] = $comptype;
      $marr['compfield'] = $baseName;
      foreach (static::$attributeNames as $attName) {
      //if ($baseName === 'yrsest') pkdebug("Looking for attName: $attName");
        if ($tst = firstKeyVal($attName,$meta, $arrSet, $fs)) {
          $marr[$attName] = $tst;
        }
      }
      $matchArr[$baseName] = new PkMatch($marr);
    }
    return $matchArr;
  }

 static   $fieldendings = ['crit', 'val', 'parm0', 'parm1', 'parm2', 'maxval', 'minval',];
 static   $metaendings = ['comptype','fieldtype','runnable', 'callable', 'targettype','comptarget', ];
  /* Creates and returns a structured array from flat, to build a match set
   * of Matches based on the data args and params
   * Could be a flat array like from a DB table -
   *  ['fieldname_crit'=>$crit, 'fieldname_val'=>$val, etc
   */
  public static function flatToStructured($arrSets = [], $params =[]) {
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

  public $attributes=[];
  public function __construct($argarr = []) {
    $this->attributes = $argarr;
  }

  /** Evaluates this Match Object with the option arg, returns true or
   * false
   * @param type $arg
   */
  public function satisfy($arg=null) {
    //pkdebug("Trying to satisfy this...", $this, 'with arg', $arg);
    if (!$this->crit || ($this->crit === '0')) return true;
    if ($this->comptype === 'string') return $this->stringComp($arg);
    if ($this->comptype === 'numeric') return $this->numericComp($arg);
    if ($this->comptype === 'group') return $this->groupComp($arg);
    if ($this->comptype === 'within') return $this->withinComp($arg);
    if ($this->comptype === 'between') return $this->betweenComp($arg);
    if ($this->comptype === 'exists') return $this->numericComp($arg);
    throw new \Exception ("Unknown comparison type: ".$this->comptype);
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
      throw new \Exception ("Unknown [{$this->crit}] for comptype: {$this->comptype}");
    }

    public function groupComp($arg = null) {
      if ($this->crit === 'IN') {
        if (!$this->val) return false;
        if (!is_array($this->val)) throw new Exception ("Invalid val:".print_r($this->val,1));
        return (in_array($arg, $this->val));
      }
        if ($this->crit === 'NOTIN') {
        if (!$this->val) return true;
        if (!is_array($this->val)) throw new Exception ("Invalid val:".print_r($this->val,1));
        return (!in_array($arg, $this->val));
      }
      throw new Exception ("Unknown [{$this->crit}] for comptype: {$this->comptype}");
    }


    public function withinComp($arg = null) {
      throw new Exception ("'WITHIN' not supported yet");
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
      $between = ($maxval !== false)
          && ($minval !== false)
          && ($arg <= $maxval) && ($arg >=$minval);
      if ($this->crit === 'BETWEEN') return $between;
      if ($this->crit === 'NOT BETWEEN') return !$between;
      throw new Exception ("Unknown [{$this->crit}] for comptype: {$this->comptype}");
    }

    public function existsComp($arg = null) {
      return ($this->crit==='EXISTS') && $arg;
    }


    public function numericComp($arg = null) {
      //pkdebug("ArG",$arg,'this', $this);
      if (!is_numeric($this->val) || !is_numeric($arg)) {
        //throw new Exception ("[{$this->val}] or [$arg] is not numeric");
        return pkdebug ("[{$this->val}] or [$arg] is not numeric");
      }
      if ($this->crit === '<' ) return $arg <  $this->val;

      if ($this->crit === '<=') return $arg <=  $this->val;

      if ($this->crit === '>=') return $arg >=  $this->val;

      if ($this->crit === '>')   return $arg >  $this->val;
      if ($this->crit === '=' ) return $arg == $this->val;
      if ($this->crit === '!=' ) return $arg != $this->val;
      throw new Exception ("Unknown [{$this->crit}] for comptype: {$this->comptype}");

    }

  public function __get($key) {
    return keyVal($key,$this->attributes);
  }

  public function __set($key,$val) {
    $this->attributes[$key] = $val;
  }


  /** Should take a wide variety of formats & convert. Examples:
   * ['attr1_val=>$val1,'attr1_crit'=>$crit1,'parms'=>[], 
   */
//public function set($argarr = []) {
}
