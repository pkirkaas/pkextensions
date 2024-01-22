<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */

namespace PkExtensions\Traits;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use PkExtensions\Models\PkModel;
use PkExtensions\Models\PkSearchModel;
use PkExtensions\PkController;
use PkExtensions\PkRefManager;
use PkExtensions\PkException;
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
  use CriteriaSetsTrait;

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

  /** Returns the target model, if any
   * @param Builder|ModelClassName|null - 
   *    If builder, get Model class from it & return
   *    If class name, Return
   *    If null, figure out from class properties (from app query trait)
   *@return string|null - Model name if any, else null
   */
  public static function getTargetModel($targetEloquent = null) {
    if ($targetEloquent) {
      //if ($targetEloquent instanceOf Builder) {
      if (static::Builderish($targetEloquent)) {
        return get_class($targetEloquent->getModel());
      } else {
        return $targetEloquent;
      }
    }
    if (property_exists(static::class,'targetModel')) return static::$targetModel;
    if (method_exists(static::class,'getTargetBuilder')) {
      return get_class(static::getTargetBuilder()->getModel());
    }
    return null;
  }

  /** Gets the builder from $targetEloquent or local properties */
  public function getQBTargetBuilder($targetEloquent = null) {
    if ($targetEloquent) {
      if (static::Builderish($targetEloquent)) {
        return $targetEloquent;
      } else { #Must be a model name?
        return $targetEloquent::query();
      }
    } else { #Get from class properties - app query trait
      if (method_exists(static::class,'getTargetBuilder')) {
        return $this->getTargetBuilder();
      } else if (property_exists(static::class,'targetModel')) {
        return static::$targetModel::query();
      }
    }
    return null;
  }



  /** From CriteriaSetsTrait:
  public static function isValidCriterion($crit, $type = null) {
  public static $criteriaSets = [
   *
   */

  /** Must be intish */
  public static function isValidWithinCriterion($crit) {
    if (to_int($crit) === false) return false;
    return true;
  }

  #To use a Boolean query control, do something like this in view form:
  /*
        <label>{{$label}}
        {!!PkForm::select($basename.'_crit',BuildQueryTrait::$booleanQueryCrit,
        null,['class'=>'form-control search-crit'])!!}
        {!!PkForm::hidden($basename.'_val',1) !!}
      </label>
   *
   */

  /** Uses the above static $withinQueryCrit for now, until phased out
   *
   * @param string|null $type - query type - 'within', 'string', etc. If null, all
   */
  /*
  public function getQueryCrit($type = null) {
    static $queryCrit = null;
    if (!$queryCrit) {
      $queryCrit = [
          'group' => static::$groupQueryCrit,
          'within' => static::$withinQueryCrit,
          'string' => static::$stringQueryCrit,
          'numeric' => static::$numericQueryCrit,
          'between' => static::$betweenQueryCrit,
          'boolean' => static::$booleanQueryCrit,
          'exists' => static::$existsQueryCrit,
      ];
      if (!$type) return $queryCrit;
      if (in_array($type, array_keys($queryCrit))) return $queryCrit[$type];
      return false;
    }
  }
   *
   */


  /** Converts the simple $search_field_defs into canonical -
   * THIS HAS THE NORMALIZED DEFINITION OF THE FIELDS FROM THE SEARCH CLASS
   * It is used to build the table field defs, but information is stripped.
   * We should merge the two - this w. getBasenameQueryDef
   * @return array of assoc arrays of min: [$basename => ['fieldtype' => $fieldtype]
   */

  public static function getNormedSearchFieldDefs() {
    $configStruct = ['fieldtype',
        'fieldtype' => 'integer',
        'comptype' => 'numeric',
    ];
    return normalizeConfigArray(static::getSearchQDFieldDefs(), $configStruct);
  }

  public static function getSearchQDFieldDefs() {
    if (method_exists(static::class,'getSearchFieldDefs')) {
      return static::getSearchFieldDefs();
    }
    //pkdebug("Static sfd:",static::$search_field_defs);
    return static::$search_field_defs;
  }

  public static function getSearchFields() {
    return array_keys(static::getNormedSearchFieldDefs());
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
  /** Gets the flattened array to use for building migration code table creation
   * Interestingly, the keys of this array will the the keys of interest in the 
   POSTed search data, so we can use them to filter the POST data only for
   real criteria
  */
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

  /** Takes a dirty array (like  POST) and returns only those key/value
   * sets that are usable in a query
   * 
   */
  public static function filterToCritArr($dirtyArr = null) {
    if (!$dirtyArr && isPost()) { 
       $dirtyArr = request()->all();
    }
    if (!is_array($dirtyArr)) return [];
    return array_intersect_key($dirtyArr,static::getTableFieldDefsExtraBuildQuery());
  }

  /** This returns an array keyed by basename=>array of all defs for that basename
   *  query/filter. It needs to be flattened and indexed to be used in building actual
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
    $searchFields = static::getNormedSearchFieldDefs();
    //pkdebug("SearchFields:", $searchFields);
    //$fieldDefsCollection = [];
    foreach ($searchFields as $baseName => &$def) {
      /** Initially defaulted to the Query Model if the field type was method - but
       * that was wrong, since I built my match filters. NOW - always default to target
       * model unless it doesn't exist OR the target is explictly specified to something
       * esle.
       */
      //$searchFields[$baseName]['attribute'] = keyVal('attribute', $def,'property');
      #$attribute - property, so use SQL, or method, so use PkMatch
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
      $custom = keyVal('custom', $def);
      #$custom can be an array of the form ['omit'=>['>=','!='],'='=>'Same As']
      #Where 'omit' removes elements, and the remainder substitutes the descriptive phrases
      $def['criteria'] = static::getCriteriaSets($comptype,$custom);
      #If criteriea exists, either an indexed array of comparison keys to remove from
      #standard, or an assoc array to substitute (esp labels)
      /*
      $criteriaSet = keyVal('criteriaSet', $criteria);
      if (!$criteriaSet) {
        $omit = keyVal('omit', $criteria);
        $criteriaSet = static::getCriteriaSets($comptype, $omit);
      }
      $def['criteria']['criteriaSet'] = $criteriaSet;
       * 
       */
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


  public static function buildQueryFieldsExists($baseName, $def = null) {
    $valType = keyVal('fieldtype', $def, 'string');
    $fieldtype_args = keyVal('fieldtype_args', $def);
    $fields = [];
    $parms = keyVal('parms', $def);
    if ($parms && is_scalar($parms)) $parms = [$parms];
    if($parms && is_array($parms)) foreach ($parms as $i => $parm) {
      $fields[$baseName.'_parm'.$i] = ['type'=> $parm, 'methods' => 'nullable'] ;
    }
    $fields[$baseName . '_val'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[$baseName . '_crit'] = ['type' => 'string', 'methods' => 'nullable'];
    return $fields;

  }


  public static function buildQueryFieldsExact($baseName, $def = null) {
    $valType = keyVal('fieldtype', $def, 'integer');
    $fieldtype_args = keyVal('fieldtype_args', $def);
    $fields = [];
    $parms = keyVal('parms', $def);
    if ($parms && is_scalar($parms)) $parms = [$parms];
    if($parms && is_array($parms)) foreach ($parms as $i => $parm) {
      $fields[$baseName.'_parm'.$i] = ['type'=> $parm, 'methods' => 'nullable'] ;
    }
    $fields[$baseName . '_val'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[$baseName . '_crit'] = ['type' => 'string', 'methods' => 'nullable'];
    return $fields;
  }



  public static function buildQueryFieldsBoolean($baseName, $def = null) {
    $valType = keyVal('fieldtype', $def, 'boolean');
    $fieldtype_args = keyVal('fieldtype_args', $def);
    $fields = [];
    $parms = keyVal('parms', $def);
    if ($parms && is_scalar($parms)) $parms = [$parms];
    if($parms && is_array($parms)) foreach ($parms as $i => $parm) {
      $fields[$baseName.'_parm'.$i] = ['type'=> $parm, 'methods' => 'nullable'] ;
    }
    $fields[$baseName . '_val'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[$baseName . '_crit'] = ['type' => 'string', 'methods' => 'nullable'];
    return $fields;
  }



/////// Questionable? 
  public static function buildQueryFieldsDate($baseName, $def = null) {
    //if ($baseName == 'netprofitmargin') {
     // pkdebug("BASENAME: [ $baseName ] ; def: ", $def);
    //}
    $valType = keyVal('fieldtype', $def, 'date');
    $fieldtype_args = keyVal('fieldtype_args', $def);
    $fields = [];
    //$criteria = keyVal('criteria',$def,[]);
    //$omit = keyVal('omit',$criteria);
    //$fields['criteriaSet'] = PkMatch::getCriteriaSets('numeric', $omit);
    //$def['criteria']['criteriaSet'] = $fields['criteriaSet'];
    $parms = keyVal('parms', $def);
    if ($parms && is_scalar($parms)) $parms = [$parms];
    if($parms && is_array($parms)) foreach ($parms as $i => $parm) {
      //$fields[$baseName.'_parm'.$i] = $parm;
      $fields[$baseName.'_parm'.$i] = ['type'=> $parm, 'methods' => 'nullable'] ;
    }
    $fields[$baseName . '_val'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[$baseName . '_crit'] = ['type' => 'string', 'methods' => 'nullable'];
    return $fields;

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
      //$fields[$baseName.'_parm'.$i] = $parm;
      $fields[$baseName.'_parm'.$i] = ['type'=> $parm, 'methods' => 'nullable'] ;
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
      //$fields[$baseName.'_parm'.$i] = $parm;
      $fields[$baseName.'_parm'.$i] = ['type'=> $parm, 'methods' => 'nullable'] ;
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
      //$fields[$baseName.'_parm'.$i] = $parm;
      $fields[$baseName.'_parm'.$i] = ['type'=> $parm, 'methods' => 'nullable'] ;
    }
    $fields[$baseName . '_maxval'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[$baseName . '_minval'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[$baseName . '_crit'] = ['type' => 'string', 'methods' => 'nullable'];
    return $fields;
  }

  /** This builds a _val field to contain a JSON string of multiple values - so
   * fieldtype HAS to be string
   * @param type $baseName
   * @param type $def
   * @return string
   */
  public static function buildQueryFieldsGroup($baseName, $def = null) {
    //$valType = keyVal('fieldtype', $def, 'string');
    $valType = 'string';
    $fieldtype_args = keyVal('fieldtype_args', $def);
    $fields = [];
    $parms = keyVal('parms', $def);
    if ($parms && is_scalar($parms)) $parms = [$parms];
    if($parms && is_array($parms)) foreach ($parms as $i => $parm) {
      //$fields[$baseName.'_parm'.$i] = $parm;
      $fields[$baseName.'_parm'.$i] = ['type'=> $parm, 'methods' => 'nullable'] ;
    }
    $fields[$baseName . '_val'] = ['type' => $valType, 'methods' => 'nullable', 'type_args' => $fieldtype_args];
    $fields[$baseName . '_crit'] = ['type' => 'string', 'methods' => 'nullable'];
    return $fields;
  }


  /** This builds a _val field to contain a JSON string of multiple values - so
   * fieldtype HAS to be string
   * BUT THE VALUE IN THE UNDERLYING MODEL IS A JSON STRING OF MULTIPLE VALUES TOO
   * @param type $baseName
   * @param type $def
   * @return string
   */
  public static function buildQueryFieldsIntersects($baseName, $def = null) {
    //$valType = keyVal('fieldtype', $def, 'string');
    $valType = 'string';
    $fieldtype_args = keyVal('fieldtype_args', $def);
    $fields = [];
    $parms = keyVal('parms', $def);
    if ($parms && is_scalar($parms)) $parms = [$parms];
    if($parms && is_array($parms)) foreach ($parms as $i => $parm) {
      //$fields[$baseName.'_parm'.$i] = $parm;
      $fields[$baseName.'_parm'.$i] = ['type'=> $parm, 'methods' => 'nullable'] ;
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
      $fields[$baseName.'_parm'.$i] = ['type'=> $parm, 'methods' => 'nullable'] ;
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
  /** Currently useless
  public function buildQueryOnTable($table = null) {
    if (!$table) $table = $this->getTargetTable();
    if (empty($table)) return false;
    $sets = $this->buildQuerySets();
    if (empty($sets)) return false;
  }
   *
   */

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

/** For Sting DB comparison. The "crit" I symbolize with is 
     'string' => [
          '0' => "Don't Care",
          'LIKE' => 'Is',
          '%LIKE' => 'Starts With',
          'LIKE%' => 'Ends With',
          '%LIKE%' => 'Contains',
          but it's really the $val that needs to be
          surrounded by %
*/
  public static function likify($val,$crit) {
    if ($crit === 'LIKE') {
      return $val;
    }  else if ($crit === '%LIKE') {
      return "%".$val;
    } else if ($crit === 'LIKE%') {
      return $val.'%';
    } else if ($crit === '%LIKE%'){
      return "%".$val."%";
    }
  }

  /** Just combines the 'buildQueryOnModel' method, executes it, then
   * runs the filters on the collection.  If there is no query set, but in
   * a POST, use it to buildl query
   *
   *ALL WRONG TRY - QUERY SETS ARE KEYED BY THEIR BASENAME
   */
  public function executeSearch($querySets = null) {
    /*
    if ($querySets && is_array($querySets)) {
      $this->querySets = static::filterToCritArr($querySets);
    } else if (isPost()) {
      $this->querySets = static::filterToCritArr(request()->all());
    }
    */

//$adefs = static::getTableFieldDefsExtraBuildQuery();
//pkdebug("The adefs collection",$adefs);
    $collection = $this->buildQueryOnModel()->get();
    //$sz = count($collection);
    //pkdebug("Just ran query from BQT: SZ: $sz");
    // ORIG$newcol = $this->filterOnMethods($collection);
    //$newcol = $this->filterOnMethods($collection);

    $newcol = $this->filterOnMatch($collection);
    $nnewcol = $this->filterOnClosures($newcol);
    $nnnewcol = $this->filterCustomMethods($nnewcol);
    $sza = count($newcol);
    $sznnn = count($nnnewcol);
    pkdebug("In ExecuteSearch: SZA:  $sza", $newcol, "SZNNN: $sznnn", $nnnewcol);
    return $nnnewcol;
  }


  /**
   * Just an indexed array of closures to run on the collection, simpler
   * than filterOnMatch
   * @var array
   */
  public $closureFilters=[];
  public $customMethods = [];
  #Uses the "customMethods array above - each entry of which has to also contain an
  #array of structures with method name, fields, criteria, etch.

  public function filterCustomMethods($collection) {
    $newcol = $collection;
    foreach($this->customMethods as $filterdata) {
      //pkdebug("Did enter with a custom method to search...");
      $newcol = call_user_func_array($filterdata['callable'], [$filterdata, $newcol]);
    }
    return $newcol;
  }


  public function filterOnClosures($collection) {
    if (method_exists($this,'buildClosureFilters')) {
      $this->buildClosureFilters();
    }
    if (!$this->closureFilters) {
      return $collection;
    }
    if ($this->closureFilters instanceOf \Closure) {
      $this->closureFilters = [$this->closureFilters];
    }
    if (!is_array($this->closureFilters)) {
      throw new PkException("Invalid Type for this->closureFilters:",
          $this->closureFilters);
    }
    $cfrs = $this->closureFilters;
    $subcol = $collection->reject(function ($item) use ($cfrs) {
      $failed = false;
      foreach($cfrs as  $cfr) { #A Closure
        if (!$cfr($item)) {
          $failed = true;
          break;
        }
      }
      return $failed;
    });
    return $subcol;
  }



  /** Return an Eloquent Query Builder Instance for "property" comparisons, AND
   * should build a PkMatch filter for method comparisons, to be applied AFTER
   * the Eloquent Query runs.
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
   * @param $targetEloquent - A PkModel CLASS OR Builder, NOT instance.
   * @return Eloquent Builder
   * */
  public function buildQueryOnModel($targetEloquent = null, $querySets=null) {
//    if (!$targetEloquent) $targetEloquent = static::getTargetEloquent();
 //   if (empty($targetEloquent)) throw new \Exception("No model or builder to build query on");
    $targetModel = static::getTargetModel($targetEloquent);
    $targetBuilder = $this->getQBTargetBuilder($targetEloquent);
    $targetFieldNames = $targetModel::getStaticAttributeNames();
    //pkdebug("TargetFieldNames:", $targetFieldNames);
    if ($querySets === null) {
      if (!empty($this->querySets)) $querySets = $this->querySets;
      else $querySets = $this->buildQuerySets();
    }
    //pkdebug("QuerySets:", $querySets);
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
    //$query = $targetModel::query();
    $query = $targetBuilder;
    if (empty($querySets)) return $query;
    //pkdebug("NOT empty SETS!");
    //pkdebug("QuerySets:", $querySets);

    //pkdebug("My PkMatchObjs:", $this->matchObjs);
    #$querySets includes both attribute=>'property' types, which are eloquent/SQL suitable,
    #as well as attribute=>'method', which should go to PkMatch filters
    foreach ($querySets as $root => $critset) {
      $crit = $critset['crit'];
      $val = $critset['val'];
      $comptype = $critset['comptype'];
      if (static::emptyCrit($critset['crit']) || (static::emptyVal($critset['val']) && !static::noval($comptype))) {
        continue;
      }
      if ($critset['def']['attribute'] === 'method') {
        #add to PkMatch set & continue
        $matchObj = PkMatch::mkMatchObj(
              static::getFullQueryDef($root)+['field_set'=>$critset],$root);
        if ($matchObj) {
          $this->addMatchObj($root,$matchObj);
        }
        continue;
      }
      //$toq = typeOf($query);
      //pkdebug("ROOT: [$root] SET:", $critset, "queryT: $toq");
      if ($root == '0') continue;
      //if ($root==='assetdebtratio') pkdebug("ADR: QT: ".typeOf($query)."..");

      /*
      if (($root==='assetdebtratio') || ($root === "loanamt") || ($root === 'yrsest'))  {
        pkdebug("ROOT: [$root] SET:", $critset, "queryT: $toq");
        //pkdebug("ADR: QT: ".typeOf($query)."..");
      }
       *
       */
      //pkdebug("ROOT: [ $root ] ADR: QT: ".typeOf($query)."..");
      //if (!$critset['crit'] || ($critset['crit'] == '0') || static::emptyVal($critset['val'])) continue;
      //pkdebug("ROOT: [ $root ] ADR: QT: ".typeOf($query)."..");
      //pkdebug("root is:", $root, "critset:", $critset);
      if (in_array($root, $targetFieldNames)) {
        $comptype = static::comptype($root);
        if ($comptype === 'string') {
          $val = $critset['val'];
          $crit = $critset['crit'];
          $val = static::likify($val,$crit);
          $query->where($root,'like',$val);
          continue;
        }









        if (method_exists($this, 'customCompare' . $comptype)) {

            #The $critset is roughly:
            /*
              ['crit'] = $arr[$key];
              ['val'] = $valval;
              ['param'] = $arr[$paramfield];
              ['comptype'] = $comptype;
              ['root'] = $root;
              ['def'] = static::getFullQueryDef($root);
             .... and here lets add the callable - to make it generic for any kind of function,
             closure, method, etc. That runable should accept two arguments - this critset,
             and the collection that passed through the SQL part
             */
            //pkdebug("About to add critset to custom methods: critset:",$critset);
            $critset['callable'] = [$this, "customCompare$comptype"];
            $this->customMethods[] = $critset;


            #We have to pack everything in a structure with field names,
            #method name, criteria, values, etc, & add it to the

            #$this->customMethods array, which in turn will be iterated through & executed
            #by $this->filterCustomMethods($collection), which will take each entry & further
            #This is for methods/comparisons that unlike the above are not built for a
            #single field and might be reusable, or just not suitible for SQL and
            #not worth the trouble of match filters or custom closures. Like "intersects"
            //pkdebug("ROOT: [$root], CT: [$comptype], CRITVAL:", $critset['val']);
          continue;
        }
        if (is_array($critset['val'])) {
          if (($comptype === 'group') && ($critset['crit'] === 'IN')) {
            $query = $query->whereIn($root, array_values($critset['val']));
            continue;
          } else if (($comptype === 'group') && ($critset['crit'] === 'NOTIN')) {
            $query = $query->whereNotIn($root, array_values($critset['val']));
            continue;

          } else if ($critset['crit'] === 'BETWEEN') {
            //  $max = is_int(keyVal('max',$critset['val'])) ? keyVal('max',$critset['val']) : PHP_INT_MAX;
            //  $min = is_int(keyVal('min',$critset['val'])) ? keyVal('min',$critset['val']) : -PHP_INT_MAX;
            $min = to_int(keyVal('minval', $critset['val']), -PHP_INT_MAX);
            $max = to_int(keyVal('maxval', $critset['val']), PHP_INT_MAX);
            //pkdebug('Orig Val Arr:', $critset['val'], "MIN:", $min, "MAX", $max);
            $query = $query->whereBetween($root, [$min, $max]);
            continue;
          /*
          } else if($matchObj = PkMatch::mkMatchObj(
              static::getFullQueryDef($root)+['field_set'=>$critset],$root)){
            pkdebug("FQD for [$root]",static::getFullQueryDef($root),'critst',$critset);
            $this->addMatchObj($root,$matchObj);
            continue;
           */
          } else {
            throw new PkException(["Unhandled for root[$root], comptype [$comptype] w. val:",
                $critset['val']]);
          }
        }
        //pkdebug("QT: ".typeOf($query)."..");
        if ($critset['crit'] === 'EXISTS') {
          $query = $query->whereNotNull($root)->where($root,'!=','');
        } else if ($critset['crit'] === 'NOT EXISTS') {
          $query = $query->whereNull($root);
        } else if ($critset['crit'] === 'IS') {
          $query = $query->where($root, true);
        } else if ($critset['crit'] === 'IS NOT') {
          $query = $query->where($root, false);
        } else {
          $query = $query->where($root, $critset['crit'], $critset['val']);
        }
      } else if (method_exists($this, 'customQuery' . $root)) {
        $customQueryMethod = 'customQuery' . $root;
        $query = $this->$customQueryMethod($query,
            $critset['crit'], $critset['val'], $critset['param']);
      }
    }
    /*
    pkdebug("End of BuildQuerySets - this matchobs: ", $this->matchObjArr,
        "Query to SQL:", $query->toSql());
    */
    return $query;
  }

  public $querySets = [];
  public $matchObjs;
  public $matchObjArr = []; #Just duplicate of above, but I want to populate it differently

  public function addMatchObj($baseName,$matchObj) {
    if (!$matchObj) return;
    $matchObj->compfield = $baseName;
    $this->matchObjArr[$baseName]=$matchObj;
  }
  public function getMatchObj($base = null) {
    if ($base) return keyVal($base,$this->matchObjArr);
    return $this->matchObjArr;
  }

  /** Compares if 2 arrays have any values in common -
   *
   * @param array $critset
              ['crit'] = $arr[$key];
              ['val'] = $valval;
              ['param'] = $arr[$paramfield];
              ['comptype'] = $comptype;
              ['root'] = $root;
              ['def'] = static::getFullQueryDef($root);
              ['callable'] = [$this, "customCompare$comptype"];
   For this function -
      crit = 0, IN, or NOTIN
      val is indexed arrayish (or empty or JSON or ArrayObject) maybe with values
      root is the name of the field to check in the collection element
   * @param collection $collection
   * @return Collection
   */
  public function customCompareintersects($critset, $collection) {
      $crit = $critset['crit'];
      $root = $critset['root'];
      $in = ($crit === 'IN');
      $val = $critset['val'];
      //pkdebug("In the customCopare: crit: [$crit], root: [$root], in:",$in,"val:", $val);
      if (!$crit) return $collection;
      $toArr = function($tst) { #Try to make the arg arrayable - & only the values
        return iterable_values($tst) ?? [];
      };
      $isIntersect = function($a,$b) use ($toArr, $in) {
          $res =  array_intersect($toArr($a), $toArr($b)) ?
            $in : !$in;
    //pkdebug("In isIntersect, toarra:",$toArr($a),"toarrb:", $toArr($b), "res:", $res);
        return array_intersect($toArr($a), $toArr($b)) ?
          $in : !$in;
      };

      $filter = function( $instance) use ($isIntersect, $root, $val) {

        return $isIntersect($instance->$root, $val);
      };
      return $collection->filter($filter);
  }

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
   *   'fieldname'=>['val'=>$val,'crit'=>$crit, 'comptype'=>$comptype, {'param'=>$param}
   */

  ## I should consider NOT building all match objs at once - just ONE AT A TIME
  ## And adding them when I can't build an SQL query.
  ## I want executeQuery to process 3 things - sql queries, PkMatch objs, and
  ## any custom filter methods defined on the Query Model

#SHOULD do what I want - take a flat array, like age_crit, age_val, age_param & return [age=>['crit'


  ### !!TODO!! May 2018 - MatchFilters don't work anymore... In addition,
  #  Have to distinguish between SQL query filters & match set/method
  ## Filters. AND - Don't build filter if "don't care"
###!!!!  BUT DO BUILD FILTER WITHOUT VALUES FOR EXISTENTIAL QUERIES
  public function buildQuerySets(Array $arr = []) { #Should also work from controller
    $arr = static::filterToCritArr($arr);
    //if ($this->matchObjs === null) {
      //$this->matchObjs=PkMatch::matchFactory(static::getFullQueryDef());
    //}
    //pkdebug("The generated 'matches' are", $this->matchObjs);
    $this->checkClearPost();
    if (empty($arr)) {
      if ($this instanceOf PkModel) {
        #To use Accessors/Mutators
        //$arr = $this->getAttributes();
        $arr = static::filterToCritArr($this->getAccessorAttributes());
      } else if (isPost()) {
        $arr = static::filterToCritArr(request()->all());
      }
    }
    if (empty($arr)) {
        return [];
    }
    $sets = [];
    $clear = false;
    if (array_key_exists('submit', $arr) && ($arr['submit'] == 'clear')) {
        $clear = true;
    }
    foreach ($arr as $key => $val) {
      #Does it end in '_crit'?
      $root = removeEndStr($key, '_crit');
      if ($root === false) continue;#Not a crit
      if ($val === null) continue;
      if (!$this->isValidCriterion($key)) continue;
      #We COULD get static::getBasenameQueryDef($root) now, and see if we have supplimental info
      $comptype = static::comptype($root);
      $noval = static::noval($comptype);

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
      if (($valval === null) && !$noval) continue;
      //if (!array_key_exists($valfield, $arr)) continue;
      $paramfield = $root . '_param';
      $arr[$paramfield] = keyVal($paramfield, $arr);

      #We have a criterion and value - build our array
      //$sets[$root] = ['crit' => $arr[$key], 'val' => $arr[$valfield], 'param' => $arr[$paramfield]];
      if (($comptype === 'group') || ($comptype === 'intersects')) {
        #Need to make it an array from json string
        if (is_string($valval)) {
          $valval = json_decode($valval,1);
        }
        //pkdebug("VALVAL",$valval);
      }


      $sets[$root] = [];
      $rootMatch->crit=$sets[$root]['crit'] = $arr[$key];
      $rootMatch->val=$sets[$root]['val'] = $valval;
      $rootMatch->param=$sets[$root]['param'] = $arr[$paramfield];
      $rootMatch->comptype=$sets[$root]['comptype'] = $comptype;
      $rootMatch->root=$sets[$root]['root'] = $root;
      $sets[$root]['def'] = static::getFullQueryDef($root);
    }
    $this->querySets = $sets;
    //pkdebug("After QA, mathcObjs are:", $this->matchObjs);
    //foreach ($this->matchObjs as $ma) {
      //if ($ma->compfield == 'assetdebtratio') pkdebug("After buildQS, The MA is: ", $ma);
    //}
    //pkdebug("querySets should contain both property & method queries: SETS:", $sets);
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

  /** Temporarilly deprecated? Maybe restore later?
  public function getMatchObjs() {
    if (!$this->matchObjs || !is_arrayish($this->matchObjs)) {
      $this->buildQuerySets();
    }
    return $this->matchObjs;
  }
   *
   */

  /** After the Eloquent has run on the attributes and returned an eloquent collection,
   * this method takes the collection and $querySets as above,
   * [$key=>['val'=>$val,'crit'=>$crit,'parm0'=>$parm0.....
   * @param Eloquent Collection $collection
   * @param array $querySets or null to take from local object
   */
  /** Temporarilly deprecated? Maybe restore later?
  public function filterOnMethods(Collection $collection, $matchObjs=null) {
    pkdebug("Yes, trying to filter on Method!.");
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
        $methodResult = $item->$methodName();
        $passed = $match->satisfy($methodResult);
        $reject = !$passed;
        if ($reject) return $reject;
      } ## Passed all the criteria; don't reject
      return false;
    });
    return $trimmedCollection;
  }
   *
   */

  /** Using the individual match objects I created one at a
   * time & added to the new array individually. Experiment
   * @param Collection $collection - the result of Eloquent queries  - should
   * be further filtered by the match filters
   * @return Collection - trimmed/filtered collection
   */
  public function filterOnMatch(Collection $collection) {
    $pkmarr = $this->getMatchObj();
    pkdebug("FOM: MatchArr:",$pkmarr, "Passed Collection:",$collection);
    if(!$pkmarr) {
      return $collection;
    }
    $trimmedCollection = $collection->reject(function ($item) use ($pkmarr) {
      $failed = false;
      foreach($pkmarr as $base => $match) {
        if (!$match->satisfy($item->$base)) {
          $failed = true;
          break;
        }
      }
      return $failed;
    });
    return $trimmedCollection;
  }

/** Uses ALL the query defs & the build htmlQueryControl below to return a
 * complete array of ALL the html search components defined in $search_field_defs,
 * which can just be called in the controller & inserted into the search form view
 @return assoc array keyed by search fields, with the value of the html control for it
# $refresh means to restore the data from a post OR SearchModel if we have it.
 */
public static function buildSearchControlArray($refresh = null) {
  $data = null;
  if ($refresh) { #Clean the POST/Saved Atts
    if ($refresh instanceOf PkSearchModel) {
      $data = static::filterToCritArr($refresh->getAccessorAttributes());
    } else if (is_array($refresh)) { # From a saved search model?
      $data = static::filterToCritArr($refresh);
    } else { #Maybe a post?
      $data = static::filterToCritArr();
    }
  }
  $fields =  static::getSearchFields();
  $ctls = [];
  foreach ($fields as $field) {
    $ctls[$field]=static::htmlQueryControl($field, $data);
  }
  return $ctls;
}





  /** For use in Query Forms - makes a full query control
   * from the field name and comparison type, with $params
   *
   * For now, only handle simple _crit, _val comparisons
   * with $params['basename']
   *
   * @param assoc array | string $params:
   *   If string, the basename to build the search control for
       If assoc arry, ['basename'=>$basename, opt 'label'=>$label
   *   @paramParam: string 'basename' (required): The basename of the field to search - like,
   *   'annual_income' - will build a criteria select box called "annual_income_crit'
   *      ("<", ">", etc)
   *    and a value input box named 'annual_income_val'
   if Label in params, uses that. If absent, uses desc from param def. If absent, upercases first letter of basename for Label
   *
   * @paramParam string 'label' (optional, suggested) - the label for the control
   * If $params
   * @param array $values - optional - to populate the control with intitial data
   * @return string HTML to make the control
   */

  public static function htmlQueryControl($params=[], $values=[]) {
    if (is_string($params)) {
      $params=['basename'=>$params];
    }
    $presets = [];
    $basename = $params['basename'];
    if ($values) { #only get the relevant params for this basename
      foreach ($values as $key=>$value) {
        if (startsWith($key,$basename)) {
          $presets[$key]=$value;
        }
      }
    }
    $qd = static::getFullQueryDef($basename);
    //pkdebug("Querydef:", $qd);
    $refarr = $qd['refarr'] ?? null;
    if (is_subclass_of($refarr,PkRefManager::class)) {
      $refarr = $refarr::notEmpty();
    }
    $qd['refarr'] = $refarr;
    $comptype = $qd['comptype'] ?? 'numeric';
    $label = $qd['label'] ?? $qd['desc'] ?? ucfirst($basename);
    if (!array_key_exists('label', $params)) {
      $params['label'] =  $label;
    }
    $criteriaSet = keyVal('criteriaSet', $params);
    if (!$criteriaSet) {
      $criteriaSet = keyVal('criteriaSet', $qd);
      if (!$criteriaSet) {
        $criteria = keyVal('criteria', $qd);
        if (ne_arrayish($criteria)) {
          $criteriaSet = keyVal('criteriaSet',$criteria);
        }
      }
      if (!$criteriaSet) { #No custom criteriaSet, so default
        //$comptype = keyVal('comptype', $qd, 'numeric');
        $criteriaSet = static::getCriteriaSets($comptype);
      }
    }
    $params['criteriaSet'] = $criteriaSet;
    $params['comptype'] = $comptype;
    $params=array_merge($params,$qd);
    /*
    $fieldDefArr = keyVal('field_defs', $qd);
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
    $tmpCtl =  PkHtmlRenderer::buildQuerySet($params,$presets);

    return PkHtmlRenderer::buildQuerySet($params,$presets);
  }

  /*
  public function processSubmit($opts = null, $inits = null) {
    if (! $this instanceOf PkController) return;
    if (isPost()) {
      $data = request()->all();
      $sedefs = static::getSearchQDFieldDefs();
      $fqd = static::getFullQueryDef();
      pkdebug("In Trait PS, data:", $data, 'SDs',$sedefs, "FullQD", $fqd);
    }

    return parent::processSubmit($opts, $inits);
  }
   *
   */

  /*
  public function save(array $opts = []) {
    if (! $this instanceOf PkSearchModel) return;
    //pkdebug("These Attributes:",$this->getAttributes());
    $fqd = static::getFullQueryDef();
//    pkdebug("FQD:",$fqd);

    foreach ($fqd as $basename => $def) {
      $comptype = static::comptype($basename);
      if(($comptype === 'group') || ($comptype === 'intersects')) { #Have to jsonify it
        $valatt = $basename.'_val';
        pkdebug("THIS att [$valatt] PRE encode: ", $this->$valatt);
        $this->$valatt = json_encode($this->$valatt, JSON_NUMERIC_CHECK);
        pkdebug("THIS att [$valatt] POST encode: ", $this->$valatt);
      }
    }
    return parent::save($opts);
  }
   *
   */

  public static function critset($root) {
    $fqd = static::getFullQueryDef($root);
    return $fqd['criteria']['criteriaSet'];
  }


  /**
   * Returns the comparison type of the query field - like, 'group', 'between',
   * etc.
   */
  public static function comptype($root) {
    $fqd = static::getFullQueryDef($root);
    if (!is_array($fqd)) return false;
    return keyVal('comptype',$fqd,false);
  }
}
