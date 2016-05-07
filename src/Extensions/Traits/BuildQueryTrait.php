<?php
namespace PkExtensions\Traits;

use PkExtensions\Models\PkModel;
use Illuminate\Database\Eloquent\Model;
use \Request;

/**
 * Builds a query from criteria and value params. Should be available to both
 * query models and query controllers.
 * <p>
 * There are two potential kinds of Models here - a "target" model to be 
 * searched, and a "Query" model which contains the query/search criteria for the
 * target model to be searched.
 * <p>
 * To implement "IN" searches, like "WHERE `typeoffinancing_id IN (4, 8, 12, 14),
 * create a model/table field 'typeoffinancing_id_val' as a string, and get/set
 * the set of values as JSON, using accessors/mutators like:
 * <pre>
  public function setTypeoffinancingIdValAttribute($value) { #$value is an array of possible values
    if (!is_array($value)) $value = [$value];
    $this->attributes['typeoffinancing_id_val'] = json_encode(array_values($value));
  }

  public function getTypeoffinancingIdValAttribute($value) {
    if (!$value) return [];
    return json_decode($value,true);
  }
 * </pre>
 * This trait supports "out of the box" direct field queries. 
 * @author Paul Kirkaas
 */

/** Note: When using the " IN " set criteria, we use accessors/mutators to 
 * convert in-memory arrays to JSON strings stored in the DB
 */ 
trait BuildQueryTrait {

  /** In Implemented Classes, will be set to the class/model to be searched.
   * @var ModelClass
   */
  public $targetModel = null;
  public $targetTable = null;

  public function getTargetTable() {
    if ($this->targetTable) return $this->targetTable;
    if ($this->targetModel) {
      $targetModel = $this->targetModel;
      $obj = new $targetModel();
      $table = $obj->getTable();
      $this->targetTable = $table;
      return $table;
    }
  }

  /** Is $crit a valid DB criterion?
   * 
   * @param string $crit
   * @param string|null $type - if null, true for any valid. Else, only true
   * if crit is of type 'numeric'|'string'|'group'
   * @return boolean
   */
  public static function isValidCriterion($crit, $type = null) {
    if ((!$type || ($type === 'numeric')) && in_array($crit, array_keys(static::$numericQueryCrit)))
        return true;
    if ((!$type || ($type === 'string')) && in_array($crit, array_keys(static::$stringQueryCrit)))
        return true;
    if ((!$type || ($type === 'group')) && in_array($crit, array_keys(static::$groupQueryCrit)))
        return true;
    if ((!$type || ($type === 'between')) && in_array($crit, array_keys(static::$betweenQueryCrit)))
        return true;
    return false;
  }

  /** Must be intish */
  public static function isValidWithinCriterion($crit) {
    if (to_int($crit) === false) return false;
    return true;
  }

  public static $numericQueryCrit = [
      '0' => "Don't Care",
      '>' => 'More Than',
      '<' => 'Less Than',
      '=' => 'Equal To',
      '!=' => 'Not Equal To',
  ];
  public static $stringQueryCrit = [
      '0' => "Don't Care",
      'LIKE' => 'Is',
      '%LIKE' => 'Starts With',
      'LIKE%' => 'Ends With',
      '%LIKE%' => 'Contains',
  ];
  public static $groupQueryCrit = [
      '0' => "Don't Care",
      'IN' => 'In',
      'NOTIN' => 'Not In',
  ];
  public static $withinQueryCrit = [
      '0' => "Don't Care",
      '1' => 'Within 1 mile',
      '5' => 'Within 5 miles',
      '10' => 'Within 10 miles',
      '20' => 'Within 20 miles',
      '50' => 'Within 50 miles',
  ];
  public static $betweenQueryCrit = [
    '0' => "Don't Care",
    'BETWEEN' => 'Between',
  ];

  #Try building the query on the model first - it's prettier

  public function buildQueryOnTable() {
    $table = $this->getTargetTable();
    if (empty($table)) return false;
    $sets = $this->buildQuerySets();
    if (empty($sets)) return false;
  }

  /** String '0' and int 0 are valid values, NULL or '' are empty */
  public static function emptyVal($val) {
    if (($val === NULL) || ($val === '' || $val === [])) return true;
    return false;
  }

  /** For Criteria, null, '', '0' & 0 are ALL considered empty/don't care */
  public static function emptyCrit($crit) {
    if (empty($crit) || ($crit === '0')) return true;
    return false;
  }

  /** Return an Eloquent Query Builder Instance
   * Builds a basic chained "AND WHERE..." query from the instance 'querySets" array.
   * The query set is an associative array of <tt>what=>[crit, val]</tt>, where
   * 'what' is either a field/column name, OR the root of a custom query method,
   * which has to be defined in the implementing classes, presumably a trait
   * shared by the Controller/SearchModel. Looks first for field name, then
   * method name, defined as <tt>$this->customQuery{What}($crit,$val)</tt>
   * (Ex: <tt>$this->customQueryAsset_debt_ratio($query,$crit, $val)</tt>:
   * <pre> 
   * [
   *   'fieldName1'=>['crit'=>$crit1, 'val'=>$val1],
   *   'fieldName2'=>['crit'=>$crit2, 'val'=>$val2],
   *   'methodName1'=>['crit'=>$crit3, 'val'=>$val3],
   *   'methodName2'=>['crit'=>$crit4, 'val'=>$val4, 'param'=>$param],
   * ....]
   * </pre>
   * 
   * @param $targetModel - A PkModel CLASS, NOT instance.
   * @return Eloquent Builder
   * */
  public function buildQueryOnModel($targetModel = null) {
    if (!$targetModel) $targetModel = $this->targetModel;
    if (empty($targetModel)) throw new \Exception("No model to build query on");
    $targetFieldNames = $targetModel::getStaticAttributeNames();
    //pkdebug("TargetFieldNames:", $targetFieldNames);
    if (!empty($this->querySets)) $sets = $this->querySets;
    else $sets = $this->buildQuerySets();
    //pkdebug("Query Sets:", $sets);
    $query = $targetModel::query();
    if (empty($sets)) return $query;
    //pkdebug("NOT empty SETS!");
    foreach ($sets as $root => $critset) {
      if ($root == '0') continue;
      //if (!$critset['crit'] || ($critset['crit'] == '0') || static::emptyVal($critset['val'])) continue;
      if (static::emptyCrit($critset['crit']) || static::emptyVal($critset['val']))
          continue;
      //pkdebug("root is:", $root, "critset:", $critset);
      if (in_array($root, $targetFieldNames)) {
        if (is_array($critset['val'])) {
          if ($critset['crit'] === 'IN') {
            $query = $query->whereIn($root, array_values($critset['val']));
            continue;
          } else if ($critset['crit'] === 'NOTIN') {
            $query = $query->whereNotIn($root, array_values($critset['val']));
            continue;
          } else if ($critset['crit'] === 'BETWEEN') {
            $max = to_int(keyVal('max',$critset['val'], PHP_INT_MAX));
            $min = to_int(keyVal('min',$critset['val'], -PHP_INT_MAX));
            $query = $query->whereBetween($root, [$min, $max]);
            continue;
          } else {
            continue;
          }
        }
        $query = $query->where($root, $critset['crit'], $critset['val']);
      } else if (method_exists($this, 'customQuery' . $root)) {
        $customQueryMethod = 'customQuery' . $root;
        $query = $this->$customQueryMethod($query, $critset['crit'], $critset['val'], $critset['param']);
      }
    }
    return $query;
  }

  public $querySets = [];

  /** Takes an associative array, possibly from a Search Model, possibly from a post,
   * and only selects matching keys in the form:
       "xxxx_crit"
       "xxxx_val"
       "xxxx_param" (optional parameters for custom querys)
   * ... and
   * only if the content of those keys is valid. Then builds an array in the 
   * form of: ['xxxx']=>['crit'=> $crit, 'val'=>$val, 'param' => $param] and returns it.
   * <p>
   * 'xxxx'/$root: either a field/column name in the base table of the model, 
   * ELSE the name of a method in the custom query trait, like for calculation
   * of ratios, or "within x miles of ZIP". But this method doesn't know about that, it's the
   * "buildQueryOnModel/Table" Class implementing this trait that
   *  deal with that.
   * @param array $arr
   * @return array: [
   *    'fieldName1'=>['crit'=>$crit1, 'val'=>$val1, 'param'=>$param1,],
   *    'fieldName2'=>['crit'=>$crit2, 'val'=>$val2, 'param'=>$param2,],
   *    'customMethodName1'=>['crit'=>$crit3, 'val'=>$val3, 'param'=>$param3,],
   *  #...
   * ];
   */
  public function buildQuerySets(Array $arr = []) {
    $this->checkClearPost();
    if (empty($arr)) {
      if ($this instanceOf PkModel) {
        #To use Accessors/Mutators
        //$arr = $this->getAttributes();
        $arr = $this->getAccessorAttributes();
      }
    }
    if (empty($arr)) return [];
    $sets = [];
    $clear = false;
    if (array_key_exists('submit', $arr) && ($arr['submit'] == 'clear'))
        $clear = true;
    foreach ($arr as $key => $val) {
      #Does it end in '_crit'?
      $root = removeEndStr($key, '_crit');
      if ($root === false) continue;#Not a crit
      if ($val === null) continue;
      if (!$this->isValidCriterion($key)) continue;
      $maxvalfield = $root.'_maxval'; #For 'BETWEEN'comparison
      $minvalfield = $root.'_minval'; #For 'BETWEEN'comparison
      $valfield = $root . '_val';
      $valval = null;
      #Getting Complicated. $valval can be a scalar for ordinary comparison
      #If doing an " IN " comparison, $valval is a JSON encoded array.
      #if doing a "BETWEEN" comparison, $valval is an actual array, ['max'=>$max,'min'=>$min]
      if (array_key_exists($maxvalfield, $arr)) $valval['max'] = $arr[$maxvalfield];
      if (array_key_exists($minvalfield, $arr)) $valval['min'] = $arr[$minvalfield];
      if (is_array($valval)) { #At least one of min or max was set for BETWEEN
        $valval['max'] = keyVal('max', $valval, PHP_INT_MAX);
        $valval['min'] = keyVal('min', $valval, -PHP_INT_MAX);
      }
      if (array_key_exists($valfield, $arr)) $valval = $arr[$valfield];
      if ($valval === null) continue;
      //if (!array_key_exists($valfield, $arr)) continue;
      $paramfield = $root . '_param';
      $arr[$paramfield] = keyVal($paramfield, $arr);
      
      #We have a criterion and value - build our array
      //$sets[$root] = ['crit' => $arr[$key], 'val' => $arr[$valfield], 'param' => $arr[$paramfield]];
      $sets[$root] = ['crit' => $arr[$key], 'val' => $valval, 'param' => $arr[$paramfield]];
    }
    $this->querySets = $sets;
    return $sets;
  }

  /**
   * Clear the search parameters (also in the saved query) if $submit == 'clear'
   * @param array|string $extrafields - clear these fields also
   */
  public function checkClearPost($extrafields = []) {
    if (is_string($extrafields)) $extrafields = [$extrafields];
    $method = Request::method();
    //if (($method == 'POST') && array_key_exists('submit',$_POST) && ($_POST['submit'] == 'clear')) {
    if (($method == 'POST') && (Request::input('submit') == 'clear')) {
      $data = Request::all();
      //pkdebug("Trying to clear: Data before:", $data);
      $mergearr = [];
      $keys = array_keys($data);
      foreach ($keys as $key) {
        if (removeEndStr($key, '_crit') !== false) $mergearr[$key] = '';
        if (removeEndStr($key, '_val') !== false) $mergearr[$key] = '';
      }
      foreach ($extrafields as $extrafield) {
        $mergearr[$extrafield] = '';
      }
      //pkdebug("MergeArr:", $mergearr);
      Request::merge($mergearr);
    }
  }

}
