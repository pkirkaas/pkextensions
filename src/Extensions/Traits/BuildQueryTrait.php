<?php

namespace PkExtensions\Traits;
use Illuminate\Database\Eloquent\Collection;
use PkExtensions\Models\PkModel;
use PkExtensions\PkMatch;
use PkExtensions\PkHtmlRenderer;

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
 * 
 * A trait that extends this trait can call static::buildQueryOnTable or 
 * buildQueryOnModel several times with several tables or models, combine 
 * them, then also call it's own custom methods to refine the results, based
 * on the Query defs below.
 * 
 */

  /**
   * 
   * IMPLEMENTOR TO PROVIDE:
    public static $search_field_defs = [
      $basename1 => ['fieldtype' => 'integer',
     'comptype'=>'between', //Comparison Type: DEFAULT: numeric
     'parms'=>['integer','date',...]
     'extra'=>['suffix'=>$type],
     'criteria'=>[ #If not the full default set of criteria for the comptype
       'criteriaSet' =>['='=>'The Same As', '!='=>'Different From',... 
           #EG: An explicit set of criteria values/labels
       {OR} 'omit'=>['=', '!=', ...} #Omit these from the default
       {OR} 'include'=>['0', '='], #include ONLY these from the default
    $basename2 =>  'integer', #Assumes comparison is numeric
    $basename3, #Assumes type is integer & comparison is numeric
    ];
   * 
   * Where $basename is the field name in the target table to search - 
   * Builds something like ['assets_val'=>'integer','assets_crit'=>'string','assets_cmp'=>'string']
   * extra only if we want extra db fields, like 'extra'=>['param'=>'string'] builds 'assets_param=>'string'
   * 
   * This Trait can then build both the table field definitions AND maybe the
   * search controls for the search forms
   * 
   * IF THE QUERY FIELD IS ON A METHOD - IT MIGHT TAKE PARAMETERS - So the "parms" key points to a scalar (only 1 parm of that type),
   * or an array of types. Parm fields will be called "$basename_parm1, "$basename_parm2, etc
   * 
   * The filed Def array can also have a key 'property' - default is 'attribute' of the target table/model. 
   * But it can also be a method on THIS model -  ('attribute'=>'method') OR another model, if the 'model' key is
   * set
   */

/** Note: When using the " IN " set criteria ("group" type query), we use accessors/mutators to 
 * convert in-memory arrays to JSON strings stored in the DB
 */
trait BuildQueryTrait {

  /** In Implemented Classes, will be set to the class/model to be searched.
   * @var ModelClass
   */
  #An implementing trait can define targetModel or targetTable
  #public static $targetModel = 'App\\Models\\Borrower';
  #public $targetTable = 'borrowers';

  public static function getTargetTable() {
    if (property_exists(static::class,'targetTable')) return static::$targetTable;
    if (property_exists(static::class,'targetModel')) {
      $model = new static::$targetModel();
      return $model->getTable();
    }
  }
  public static function getTargetModel() {
    if (property_exists(static::class,'targetModel')) return static::$targetModel;
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

  /** Uses the above static $withinQueryCrit for now, until phased out
   * 
   * @param string|null $type - query type - 'within', 'string', etc. If null, all 
   */
  public function getQueryCrit($type = null) {
    static $queryCrit = null;
    if (!$queryCrit) {
      $queryCrit = [
          'group' => static::$groupQueryCrit,
          'within' => static::$withinQueryCrit,
          'string' => static::$stringQueryCrit,
          'numeric' => static::$numericQueryCrit,
          'between' => static::$betweenQueryCrit,
      ];
      if (!$type) return $queryCrit;
      if (in_array($type, array_keys($queryCrit))) return $queryCrit[$type];
      return false;
    }
  }


  /** Converts the simple $search_field_defs into canonical - 
   * THIS HAS THE NORMALIZED DEFINITION OF THE FIELDS FROM THE SEARCH CLASS
   * It is used to build the table field defs, but information is stripped.
   * We should merge the two - this w. getBasenameQueryDef
   * @return array of assoc arrays of min: [$basename => ['fieldtype' => $fieldtype]
   */

  public static function getSearchFieldDefs() {
    $configStruct = ['fieldtype',
        'fieldtype' => 'integer',
        'comptype' => 'numeric',
    ];
    return normalizeConfigArray(static::$search_field_defs, $configStruct);
  }

  public static function getSearchFields() {
    return array_keys(static::getSearchFieldDefs());
  }

  /** Builds a set of HTML inputs for a particular search item/term, based on the
   * comparison type - ex, if 
   * @param type $baseName - 
   */
  /* No, don't think I'll do this - already have good generators */
  /*
  public static function buildSearchControl($baseName) { }
   */

  public static $queryFieldDefCacheKey = 'baseKeyedQueryTableFieldDefs';
  /** Gets the flattened array to use for building migration code */
  public static function getTableFieldDefsExtraBuildQuery() {
    $fqd = static::getFullQueryDef();
    $fieldDefSet = [];
    foreach ($fqd as $f) {
      $fieldDefSet[]=$f['field_defs'];
    }
    //$idxArr = array_values(static::getBasenameQueryDef());
    //return call_user_func_array('array_merge', $idxArr);
    if (!is_array($fieldDefSet) || !count($fieldDefSet)) return [];
    return call_user_func_array('array_merge', $fieldDefSet);
  }
    
  /** This returns an array keyed by basename=>array of all defs for that basename
   *  query. It needs to be flattened and indexed to be used in building actual
   *  migration table building code, which is what the above
   *   getTableFieldDefsExtraBuildQuery does.
   * @param $baseName - null or string - if null returns all basenames
   *   and defs - if string, returns the def for that basename, or null
   * @return type
   */
  public static function getFullQueryDef($fieldName=null) {
    /*
    if($r = static::getCached(static::$queryFieldDefCacheKey)) {
      if ($baseName) return keyVal($baseName,$r);
      return $r;
    }
     * 
     */
    //$searchDefs = []; //Made of both the table field defs AND other params/settings
    $searchFields = static::getSearchFieldDefs();
    //$fieldDefsCollection = [];
    foreach ($searchFields as $baseName => &$def) {
      /** Initially defaulted to the Query Model if the field type was method - but
       * that was wrong, since I built my match filters. NOW - always default to target
       * model unless it doesn't exist OR the target is explictly specified to something
       * esle.
       */
      //$searchFields[$baseName]['attribute'] = keyVal('attribute', $def,'property');
      $attribute = keyVal('attribute', $def,'property');
      $model = keyVal('model', $def);


      $def['attribute'] = $attribute;
      if (($model === 'target') || !$model) {
        $def['model'] = static::getTargetModel();
      } else {
        $def['model'] = static::class;
      }
      $comptype = $def['comptype'] = keyVal('comptype', $def, 'numeric');
      $fieldBuildMethod = 'buildQueryFields' . $comptype;
      /** This allows implementing classes to add additional comparison types
       * and methods, and still get called from here.
       */
      if (!is_arrayish($def)) $def = [];
      $criteria = keyVal('criteria', $def);
      $criteriaSet = keyVal('criteriaSet', $criteria);
      if (!$criteriaSet) {
        $omit = keyVal('omit', $criteria);
        $criteriaSet = PkMatch::getCriteriaSets($comptype, $omit);
      }
      $def['criteria']['criteriaSet'] = $criteriaSet;
      $fieldDefs = static::$fieldBuildMethod($baseName, $def);
      /*
      if ($extra) {
        $extraDefs = static::buildExtraFields($baseName, $extra);
        if (is_array($extraDefs)) $fieldDefs = array_merge($fieldDefs, $extraDefs);
      }
       * 
       */
      //$searchFields[$baseName]['field_defs']=$fieldDefs;
      $def['field_defs'] = $fieldDefs;
      //$fieldDefsCollection[$baseName] = $fieldDefs;
    }
    //pkdebug("FIELDDEFS:", $fieldDefs);
    if ($fieldName) return $searchFields[$fieldName];
    return $searchFields;
    //$r = static::setCached(static::$queryFieldDefCacheKey, $fieldDefsCollection);
    //if ($baseName) return keyVal($baseName, $r);
    //return $r;
  }

  /** The query definitions can comprise several different Tables, Models,
   * return QueryBuilder instances, or call methods on different models with
   * different arguments. Have to separate them out. The instantiating
   * QueryTrait can sequence them - just needs them broken down by model,
   * table, if it's using SQL or methods...
   */
  public static function decomposeQueryDef() {
    $queryDefs = static::getbaseNameQueryDef();
    $models = [];
    
    foreach ($queryDefs as $baseName=>$queryDef) {

    }

  }
  /** Allow the instantiating class to retrieve the query defs lots of ways
   * The param keys specify what to get - 'model', 'method', 'table', 'query'.
   * If the value of the key is 'true', all the defs for that type are returned
   * that is, all if 'model'=>true, all model defs returned. But if model =>
   * 'App\Models\Borrower', only those returned.
   * @param type $params
   * @return type
   */
  public static function fetchQueryDefSet($params=[]) {

    $queryDefs=static::getFullQueryDef();
    //pkdebug("QueryDefs",$queryDefs);//, 'sfd',static::$search_field_defs);
    if (!$params) return $queryDefs;
    //pkdebug("QueryDefs",$queryDefs);//, 'sfd',static::$search_field_defs);
    $ret = [];
    $model = keyVal('model',$params);
    $query = keyVal('query',$params);
    $methods = keyVal('methods',$params);
    $attributes = keyVal('attributes',$params);
    foreach ($queryDefs as $baseName=>$queryDef) {
      
    }


  }


  public static function buildQueryFieldsNumeric($baseName, $def = null) {
    //if ($baseName == 'netprofitmargin') {
     // pkdebug("BASENAME: [ $baseName ] ; def: ", $def);
    //}
    $valType = keyVal('fieldtype', $def, 'integer');
    $fieldtype_args = keyVal('fieldtype_args', $def);
    $fields = [];
    //$criteria = keyVal('criteria',$def,[]);
    //$omit = keyVal('omit',$criteria);
    //$fields['criteriaSet'] = PkMatch::getCriteriaSets('numeric', $omit);
    //$def['criteria']['criteriaSet'] = $fields['criteriaSet'];
    $parms = keyVal('parms', $def);
    if ($parms && is_scalar($parms)) $parms = [$parms];
    if($parms && is_array($parms)) foreach ($parms as $i => $parm) {
      $fields[$baseName.'_parm'.$i] = $parm; 
    }
    $fields[$baseName . '_val'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[$baseName . '_crit'] = ['type' => 'string', 'methods' => 'nullable'];
    return $fields;

  }

  public static function buildQueryFieldsString($baseName, $def = null) {
    $valType = keyVal('fieldtype', $def, 'string');
    $fieldtype_args = keyVal('fieldtype_args', $def);
    $fields = [];
    $parms = keyVal('parms', $def);
    if ($parms && is_scalar($parms)) $parms = [$parms];
    if($parms && is_array($parms)) foreach ($parms as $i => $parm) {
      $fields[$baseName.'_parm'.$i] = $parm; 
    }
    $fields[$baseName . '_val'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[$baseName . '_crit'] = ['type' => 'string', 'methods' => 'nullable'];
    return $fields;
  }

  public static function buildQueryFieldsBetween($baseName, $def = null) {
    $valType = keyVal('fieldtype', $def, 'integer');
    $fieldtype_args = keyVal('fieldtype_args', $def);
    $fields = [];
    $parms = keyVal('parms', $def);
    if ($parms && is_scalar($parms)) $parms = [$parms];
    if($parms && is_array($parms)) foreach ($parms as $i => $parm) {
      $fields[$baseName.'_parm'.$i] = $parm; 
    }
    $fields[$baseName . '_maxval'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[$baseName . '_minval'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[$baseName . '_crit'] = ['type' => 'string', 'methods' => 'nullable'];
    return $fields;
  }

  public static function buildQueryFieldsGroup($baseName, $def = null) {
    $valType = keyVal('fieldtype', $def, 'string');
    $fieldtype_args = keyVal('fieldtype_args', $def);
    $fields = [];
    $parms = keyVal('parms', $def);
    if ($parms && is_scalar($parms)) $parms = [$parms];
    if($parms && is_array($parms)) foreach ($parms as $i => $parm) {
      $fields[$baseName.'_parm'.$i] = $parm; 
    }
    $fields[$baseName . '_val'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[$baseName . '_crit'] = ['type' => 'string', 'methods' => 'nullable'];
    return $fields;
  }

  public static function buildQueryFieldsWithin($baseName, $def = null) {
    $valType = keyVal('fieldtype', $def, 'integer');
    $fieldtype_args = keyVal('fieldtype_args', $def);
    $paramType = keyVal('paramtype', $def, 'integer');
    $fields = [];
    $parms = keyVal('parms', $def);
    if ($parms && is_scalar($parms)) $parms = [$parms];

    if($parms && is_array($parms)) foreach ($parms as $i => $parm) {
      $fields[$baseName.'_parm'.$i] = $parm; 
    }
    $fields[  $baseName . '_val'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[ $baseName . '_crit'] = ['type' => 'string', 'methods' => 'nullable'];
    $fields[$baseName . '_param'] = ['type' => $paramType, 'methods' => 'nullable'];
    return $fields;
  }

  #Try building the query on the model first - it's prettier

  /** Can build queries on any table, but defaults to our targetTable if
   * no table given. Can be combined several times
   * @param string|null $table
   * @return QueryBuilder instance or false
   */
  public function buildQueryOnTable($table = null) {
    if (!$table) $table = $this->getTargetTable();
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

  /** Just combines the 'buildQueryOnModel' method, executes it, then
   * runs the filters on the collection.
   */
  public function executeSearch() {
    $collection = $this->buildQueryOnModel()->get();
    $sz = count($collection);
    //pkdebug("Just ran query from BQT: SZ: $sz");
    $newcol = $this->filterOnMethods($collection);
    $sza = count($newcol);
    //pkdebug("SXA:  $sza");
    return $newcol;
  }

  /** Return an Eloquent Query Builder Instance
   * Builds a basic chained "AND WHERE..." query from the instance 'querySets" array.
   * The query set is an associative array of <tt>what=>[crit, val]</tt>, where
   * 'what' is either a field/column name, OR the root of a custom query method,
   * which has to be defined in the implementing classes, presumably a trait
   * shared by the Controller/SearchModel. Looks first for field name, then
   * method name, defined as <tt>$this->customQuery{What}($crit,$val)</tt>
   * 
   * One advantage to searching on a model instance - you can also use
   * and specify model methods as comparison criteria for stuff that's hard in 
   * DB. Can also take parameters. The Query will first execute the query it
   * can on the DB - then from the return, filter on the instances using
   * the methods.
   * 
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
  public function buildQueryOnModel($targetModel = null, $querySets=null) {
    if (!$targetModel) $targetModel = static::getTargetModel();
    if (empty($targetModel)) throw new \Exception("No model to build query on");
    $targetFieldNames = $targetModel::getStaticAttributeNames();
    //pkdebug("TargetFieldNames:", $targetFieldNames);
    if ($querySets === null) {
      if (!empty($this->querySets)) $querySets = $this->querySets;
      else $querySets = $this->buildQuerySets();
    }
    #Sets are keyed by 'root' or 'baseName', with a definition array. If the
    #root key matches an attribute name on the model, that's what we search 
    #against. If not, try to figure out what the query is on/for/to.
    #
    #The definition array might contain a sub-definition array - 
    #$sets[$root]['def']['attribute'] - which could be : 'property',
    #'target_method' (a method to call on the target Model), or 'self_method'
    #(a method defined in this model/trait.) - with $baseName the method or property
    #name. It could also contain other fields, like as parameters to methods
    #
    #In fact, it's totally appropriate for a persistent query to consist entirely
    #of methods and NO model/table fields.
    //pkdebug("Query Sets:", $sets);
    $query = $targetModel::query();
    if (empty($querySets)) return $query;
    //pkdebug("NOT empty SETS!");
    //pkdebug("QuerySets:", $querySets);

    //pkdebug("My PkMatchObjs:", $this->matchObjs);
    foreach ($querySets as $root => $critset) {
      $toq = typeOf($query);
      //pkdebug("ROOT: [$root] SET:", $critset, "queryT: $toq");
      if ($root == '0') continue;
      //if ($root==='assetdebtratio') pkdebug("ADR: QT: ".typeOf($query)."..");
      //pkdebug("ROOT: [ $root ] ADR: QT: ".typeOf($query)."..");
      //if (!$critset['crit'] || ($critset['crit'] == '0') || static::emptyVal($critset['val'])) continue;
      if (static::emptyCrit($critset['crit']) || static::emptyVal($critset['val']))
          continue;
      //pkdebug("ROOT: [ $root ] ADR: QT: ".typeOf($query)."..");
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
            //  $max = is_int(keyVal('max',$critset['val'])) ? keyVal('max',$critset['val']) : PHP_INT_MAX;
     // pkdebug("ROOT: [ $root ] ADR: QT: ".typeOf($query)."..");
            //  $min = is_int(keyVal('min',$critset['val'])) ? keyVal('min',$critset['val']) : -PHP_INT_MAX;
            $min = to_int(keyVal('minval', $critset['val']), -PHP_INT_MAX);
            $max = to_int(keyVal('maxval', $critset['val']), PHP_INT_MAX);
            //pkdebug('Orig Val Arr:', $critset['val'], "MIN:", $min, "MAX", $max);
            $query = $query->whereBetween($root, [$min, $max]);
            continue;
          } else {
            continue;
          }
        }
        //pkdebug("QT: ".typeOf($query)."..");
        $query = $query->where($root, $critset['crit'], $critset['val']);
      } else if (method_exists($this, 'customQuery' . $root)) {
        $customQueryMethod = 'customQuery' . $root;
        $query = $this->$customQueryMethod($query, $critset['crit'], $critset['val'], $critset['param']);
      }
    }
    return $query;
  }

  public $querySets = [];
  public $matchObjs;

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
  /** Builds the query from the the key=> values either in the arg $arr
   *  (if it was implemented from a controller action), or
   * from it's own values (if it is implemented in a search model), or from
   * a post array if it is implemented from a controller.
   * @param array $arr
   * @return array keyed by
   *   'fieldname'=>['val'=>$val,'crit'=>$crit, {'param'=>$param}
   */
  public function buildQuerySets(Array $arr = []) {
    if ($this->matchObjs === null) {
      $this->matchObjs=PkMatch::matchFactory(static::getFullQueryDef());
    }
    //pkdebug("The generated 'matches' are", $this->matchObjs);
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
      #We COULD get static::getBasenameQueryDef($root) now, and see if we have supplimental info
      $maxvalfield = $root . '_maxval'; #For 'BETWEEN'comparison
      $minvalfield = $root . '_minval'; #For 'BETWEEN'comparison
      $valfield = $root . '_val';
      $valval = null;
      #Getting Complicated. $valval can be a scalar for ordinary comparison
      #If doing an " IN " comparison, $valval is a JSON encoded array.
      #if doing a "BETWEEN" comparison, $valval is an actual array, ['max'=>$max,'min'=>$min]
      $rootMatch = keyVal($root, $this->matchObjs, new PkMatch);
      if (array_key_exists($maxvalfield, $arr)) {
          $valval['maxval'] = $arr[$maxvalfield];
          $rootMatch->maxval = $arr[$maxvalfield];
      }
      if (array_key_exists($minvalfield, $arr)) {
          $valval['minval'] = $arr[$minvalfield];
          $rootMatch->minval = $arr[$minvalfield];
      }
      if (is_array($valval)) { #At least one of min or max was set for BETWEEN
        $rootMatch->maxval=$valval['maxval'] = to_int(keyVal('maxval', $valval), PHP_INT_MAX);
        $rootMatch->minval=$valval['minval'] = to_int(keyVal('minval', $valval), -PHP_INT_MAX);
      }
      if (array_key_exists($valfield, $arr)) $valval = $arr[$valfield];
      if ($valval === null) continue;
      //if (!array_key_exists($valfield, $arr)) continue;
      $paramfield = $root . '_param';
      $arr[$paramfield] = keyVal($paramfield, $arr);

      #We have a criterion and value - build our array
      //$sets[$root] = ['crit' => $arr[$key], 'val' => $arr[$valfield], 'param' => $arr[$paramfield]];
       
      $sets[$root] = [];
      $rootMatch->crit=$sets[$root]['crit'] = $arr[$key];
      $rootMatch->val=$sets[$root]['val'] = $valval;
      $rootMatch->param=$sets[$root]['param'] = $arr[$paramfield];
      $sets[$root]['def'] = static::getFullQueryDef($root);
    }
    $this->querySets = $sets;
    //foreach ($this->matchObjs as $ma) {
      //if ($ma->compfield == 'assetdebtratio') pkdebug("After buildQS, The MA is: ", $ma);
    //}
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

  public function getMatchObjs() {
    if ($this->matchObjs && is_arrayish($this->matchObjs)) return $this->matchObjs;
    $this->buildQuerySets();
    if ($this->matchObjs && is_arrayish($this->matchObjs)) return $this->matchObjs;
  }

  /** After the Eloquent has run on the attributes and returned an eloquent collection,
   * this method takes the collection and $querySets as above, 
   * [$key=>['val'=>$val,'crit'=>$crit,'parm0'=>$parm0.....
   * @param Eloquent Collection $collection
   * @param array $querySets or null to take from local object
   */
  public function filterOnMethods(Collection $collection, $matchObjs=null) {
    //pkdebug("Yes, trying to filter.");
    if (!$matchObjs || !is_arrayish($matchObjs)) $matchObjs = $this->getMatchObjs();
    if (!$matchObjs || !is_arrayish($matchObjs)) return $collection;
    $numpre = count($matchObjs);
    $modelName = $collection-> getQueueableClass();
    //pkdebug("The num of match objs before: The QC is: [ $modelName ], the num $numpre -- is mine here?");
    //foreach ($matchObjs as $ma) { if ($ma->compfield == 'assetdebtratio') pkdebug("After buildQS, The MA is: ", $ma); }



    $trimmedMatches = PkMatch::filterMatchArr($matchObjs,
        ['modelName'=>$modelName,'modelMethods'=>true,'emptyCrit'=>true]);
    //pkdebug("The Trimmed Match Collection:", $trimmedMatches);
    if (!count($trimmedMatches)) return $collection;
    $trimmedCollection = $collection->reject(function ($item) use ($trimmedMatches) {
      foreach($trimmedMatches as $match) {
        $methodName = $match->method;
        $res = $item->$methodName();
        $ans = $match->satisfy($res);
      //  if ($ans) pkdebug("This one failed:", $ans, " METHOD NAME: $methodName; $res: ",$res);
        return !$ans;
        //if (!$match->satisfy($res)) return false;
      }
      return true;
    });
    return $trimmedCollection;
  }

  /** For use in Query Forms - makes a full query control
   * from the field name and comparison type, with $params
   * 
   * For now, only handle simple _crit, _val comparisons
   * with $params['basename']
   * 
   * @param assoc array $params:
   *   @paramParam: string 'basename' (required): The basename of the field to search - like,
   *   'annual_income' - will build a criteria select box called "annual_income_crit'
   *      ("<", ">", etc)
   *    and a value input box named 'annual_income_val'
   * 
   * @paramParam string 'label' (optional, suggested) - the label for the control
   * 
   * 
   * @return string HTML to make the control
   */
   
  public static function htmlQueryControl($params=[]) {
    $basename = $params['basename'];
    $queryDef = static::getFullQueryDef($basename);
    pkdebug("queryDef:", $queryDef);
    $criteriaSet = keyVal('criteriaSet', $params);
    if (!$criteriaSet) {
      $criteriaSet = keyVal('criteriaSet', $queryDef);
    pkdebug("criteriaSet:", $criteriaSet);
      if (!$criteriaSet) {
        $criteria = keyVal('criteria', $queryDef);
        if (ne_arrayish($criteria)) {
          $criteriaSet = keyVal('criteriaSet',$criteria);
    pkdebug("criteriaSet:", $criteriaSet);
        }
      }
      if (!$criteriaSet) { #No custom criteriaSet, so default
        $comptype = keyVal('comptype', $queryDef, 'numeric');
        $criteriaSet = PkMatch::getCriteriaSets($comptype);
    pkdebug("criteriaSet:", $criteriaSet);
      }
    }
    pkdebug("criteriaSet:", $criteriaSet);
    $params['criteriaSet'] = $criteriaSet;
    /*
    $fieldDefArr = keyVal('field_defs', $queryDef);
    if (ne_array($fieldDefArr)) {
      $fieldNames = array_keys($fieldDefArr);
    } else {
      $fieldNames = [$basename.'_crit', $basename.'_val'];
    }
    $critName  = null;
    $valName  = null;
    foreach ($fieldNames as $i => $fieldName) {
      if (removeEndStr($fieldName, '_crit')) {
        $critName = $fieldName;
        unset ($fieldNames[$i]);
      }
      if (removeEndStr($fieldName, '_val')) {
        $valName = $fieldName;
        unset ($fieldNames[$i]);
      }
    }
     * 
     */
    $tmpCtl =  PkHtmlRenderer::buildQuerySet($params);
    pkdebug("TMPCTL: ", $tmpCtl);

    return PkHtmlRenderer::buildQuerySet($params);
    //pkdebug("The QueryDef for [ $basename ]:", $fieldDef);
  }

}
