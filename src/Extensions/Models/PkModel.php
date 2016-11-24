<?php

/** Adds functionality to base model, including authorization checks. 
 * In the default, all auths are true - if you want to default to false, sublcass
 * this and set them to false.
 */
/** TODO! Add static method and array to build Migration files - this will allow
 * the Model to build the migration file, so all data definitions localized in
 * the model, but more importantly, THE MODEL WILL KNOW WHAT DB TABLE ATTRIBUTES
 * it has!
 */

namespace PkExtensions\Models;

use PkExtensions\Traits\UtilityMethodsTrait;
use PkExtensions\BaseTransformer;
use Illuminate\Database\Eloquent\Builder;
use Schema;
use App\Models\User;
use \Auth;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use \Request;
use \Exception;
use PkExtensions\PkTestGenerator;

abstract class PkModel extends Model {

  use UtilityMethodsTrait;

  public $transformer;
  public static $timestamp = true;

  /** If a sublcass defines 'static $onlyLocal = true", then static::getTableFieldDefs()
   * will only return static::$table_field_defs;
   * @var type 
   */
  public static $onlyLocal = false;

  #Some field names are created by functions and should never be touched again.
  #The keys are field names, the values are the functions to be run only once,
  #if the $key/field name doesn't already exist.
  #mig
  public static $onetimeMigrationFuncs = [
      'updated_at' => 'timestamps()'
  ];

  /** Actual derived classes will define the $table_fields array with keys that
   * correspond to table field names, and values that represent their definition.
   * @var array - keys are table field names, values are table field defs
   */
  public static $table_field_defs = [ 'id' => 'increments',];



//Display Value Fields
  public static $displayValueSuffix = "_DV"; // Let's try that...


  /* Fields of this model that can display more meaningful info with
   * $this->displayValue($field_name);
   * The individual models must implement this, but then can access the
   * reference value NOT ONLY with $model->displayValue($field_name);, but 
   * also $this->$field_name.'_DV'
   * 
   * The dispvalfield can be a value of the array, or a key of the array
   * with the value the refModel that can display it. That's easiest because
   * it's handled automatically. But to get the actual display_value_fields,
   * you have call static::getDisplayValueFields();
   */

  public static $display_value_fields = [ /*
    'financetype_id',
    'buscredit_id'=>'App\References\BusinessCreditRef',
    'date'=>['PkExtensions\DisplayValue\DateFormat','Y-m-d'], #Argument to DateFormat
   */];

  /** Does two entirely different things - by default, 
   * Just returns an indexed array of field names
   * But if $onlyrefs = true, ONLY RETURNS display value fields mapped to 
   * PkRef classes
   */
  public static function getDisplayValueFields($onlyrefs = false) {
    if ($onlyrefs) {
      $refarr = [];
      foreach (static::$display_value_fields as $key => $value) {
        //if (is_string ($key) && is_string($value)) {
        if (is_string ($key) && $value) {
          $refarr[$key] = $value;
        }
      }
      return $refarr;
    }
    if (!static::$display_value_fields ||
        !count(static::$display_value_fields)) {
      return [];
    }
    $normalized = normalizeConfigArray(static::$display_value_fields);
    if (is_array($normalized)) return array_keys($normalized);
    return [];
  }


  /** Since $table_field_defs are inhereted, if a subclass wants to eliminate
   * some of the ancestor keys - like, use a different name for 'id' key, list
   * those key names here.
   * @var array 
   */
  public static $unset_table_field_keys = [];

  public static function getFieldNames() {
    static $fieldNameArr = [];
    $class = static::class;
    if (array_key_exists($class, $fieldNameArr)) return $fieldNameArr[$class];
    $fieldNameArr[$class] = array_keys(static::getTableFieldDefs());
    return $fieldNameArr[$class];
  }

  public static $methodsAsAttributeNames = [];

  public static function getMethodsAsAttributeNames() {
    return static::$methodsAsAttributeNames;
  }

  /** Executes the methods listed in static::getMethodsAsAttributesNames() and
   * returns the results in an array of arrays
   */
  public function getMethodAttributes() {
    $methodAttributes = static::getMethodsAsAttributeNames();
    if (!$methodAttributes || !is_arrayish($methodAttributes) || !count($methodAttributes)) {
      return [];
    }
    $resarr = [];
    foreach ($methodAttributes as $methodAttribute) {
      $resarr[$methodAttribute] = $this->$methodAttribute();
    }
    return $resarr;
  }

  public static $_modelFieldDefs = [];

  /** ONLY CALLED BY static::getTableFieldDefs()
   * So implementing classes can modify defaults methods - adding and 
   * subtracting fields
   * 
   * @staticvar array $modelFieldDefs
   * @return array
   */
  public static function _getTableFieldDefs() {
    $cacheKey = 'tableFieldDefs';
    if (static::$onlyLocal) return static::$table_field_defs;
    if (static::getCached($cacheKey) !== null)
        return static::getCached($cacheKey);
    return static::setCached($cacheKey, static::getAncestorArraysMerged('table_field_defs'));
  }

  public static function _unsetTableFieldDefs($defs = []) {
    foreach (static::$unset_table_field_keys as $unset_key) {
      unset($defs[$unset_key]);
    }
    return $defs;
  }

  public static function getTableFieldDefs() {
    $defs = array_merge(static::_getTableFieldDefs(), static::getExtraTableFieldDefs());
    return static::_unsetTableFieldDefs($defs);
  }

  /** Tries to return a description of the attribute for a label
   * If there is a 'desc' property for the attribute, return it.
   * Else, if there is a 'comment' property for the attribute, return it
   * Else, return ucfirst $attname
  */
  public static function attdesc($attname) {
    $defs = static::getTableFieldDefs();
    if (!in_array($attname,array_keys($defs),1)) {
      return ucfirst($attname);
    }
    $attprops = $defs[$attname];
    $desc = keyVal('desc',$attprops,keyVal('comment',$attprops,ucfirst($attname)));
    return $desc;
  }

  public static function get_field_type($fieldname) {
    $fielddef = KeyVal($fieldname, static::getTableFieldDefs());
    if (!$fielddef) return false;
    if (is_string($fielddef)) return $fielddef;
    if (is_array($fielddef)) return keyval('type', $fielddef);
    return false;
  }

  /** Because the Eloquent Model class needs an instance to get a table name...
   * @var null|string - name of the DB table 
   */

  /**
   * Get the table associated with the model.
   * @return string
   */
  public static function getTableName() {
    static $tableNames = [];
    $class = static::class;
    if (array_key_exists($class, $tableNames)) return $tableNames[$class];
    $instance = new static();
    $tablename = $instance->getTable();
    $tableNames[$class] = $tablename;
    return $tablename;
  }

  /** Super flexible - can define a table field in static $table_field_defs = [
   * 'fld1'=>'integer',
   * 'fld2'=>['integer,'index'],
   * 'fld3'=>['type'=>'integer','methods'=>['nullable','index'=>$column_names,]],
   * 'fld4'=>['methods'=>['index'=>$column_names,],'comments'=>'A comment',]],,
   * 'fld5'=>['string','type_args'=>2000, 'methods'=>['index'=>$column_names,'default'=>'Hello',]]],,
   * #type_args are arguments to the column create method, like string size, etc
   * 
   * @param type $fielddefs
   * @param type $change
   * @return string
   */
  public static function buildMigrationFieldDefs($fielddefs = [], $change = false) {
    $eloquentMigrationColumnDefs = [
      'bigIncrements', 'bigInteger', 'binary', 'boolean', 'char', 'date', 'dateTime', 
      'dateTimeTz', 'decimal', 'double', 'enum', 'float', 'increments', 'integer', 
      'ipAddress', 'json', 'jsonb', 'longText', 'macAddress', 'mediumIncrements', 
      'mediumInteger', 'mediumText', 'morphs', 'nullableTimestamps', 'rememberToken', 
      'smallIncrements', 'smallInteger', 'softDeletes', 'string', 'string', 'text', 
      'time', 'timeTz', 'tinyInteger', 'timestamp', 'timestampTz', 'timestamps', 
      'timestampsTz', 'unsignedBigInteger', 'unsignedInteger', 'unsignedMediumInteger', 
      'unsignedSmallInteger', 'unsignedTinyInteger', 'uuid', ];


    $changestr = '';
    $indices = ['index', 'unique', 'primary'];
    if ($change) $changestr = '->change()';
    $out = "\n";
    $spaces = "    ";
    //foreach (static::$table_field_defs as $fieldName => $def) {
    foreach ($fielddefs as $fieldName => $def) {
      $methodChain = '';
      if (is_string($def))
          $out.="$spaces\$table->$def('$fieldName')$changestr;\n";
      else if (is_array($def)) {
        $defvals = array_values($def);
        if (array_key_exists('type',$def)) {
          $type = keyVal('type', $def, 'integer');
        } else { #Column def could be just a value in the def array
          foreach ($eloquentMigrationColumnDefs as $emd) {
            if (in_array($emd,$defvals,1)) {
              $type = $emd;
              break;
            }
          }
          if (!isset($type)) $type='integer';
        }



        $type = keyVal('type', $def, 'integer');
        $type_args = keyVal('type_args', $def) ? ', ' . keyVal('type_args', $def) : '';
        $comment = keyval('comment', $def);
        $default = keyval('default', $def);
        $methods = keyval('methods', $def, []);
        $fielddef = "$spaces\$table->$type('$fieldName'$type_args)";
        if ($comment) $fielddef .= "->comment(\"$comment\")";
        if ($default) $fielddef .= "->default($default)";
        if (is_string($methods)) {
          if ($change && (in_array($methods, $indices)))
              $fielddef .= $changestr . ";\n";
          else $fielddef .="->$methods()$changestr;\n";
          $out .= $fielddef;
          continue;
        }
        if (is_array($methods)) {
          foreach ($methods as $method => $args) {
            if (is_int($method)) {
              $method = $args;
              $args = null;
            }
            if (!$change || !in_array($method, $indices))
                $methodChain .= "->$method($args)";
          }
        }
        #Look for $def VALUES, for attributes that don't require args
        #index, for example, could be in "methods", with args, or just a value
        #in the def array
        $standaloneModifiers = ['primary','index','unique','nullable','unsigned',];
        foreach($standaloneModifiers as $sam) {
          if (in_array($sam,$defvals,true)) {
            $methodChain.=  "->$sam()";
          }
        }

        $out .= $fielddef . $methodChain . "$changestr;\n";
      }
    }
    return $out;
  }

  public static function getOnetimeMigrationFunctions() {
    return static::getAncestorArraysMerged('onetimeMigrationFuncs');
  }

  /** So not close to ready - finish in spare time .... */
  //public static function buildCreateMigrationDefinition() {

  /** Either new, or update if table exists */
  public static function buildMigrationDefinition() {
    $tablename = static::getTableName();
    $basename = getBaseName(static::class);
    $pbasename = Str::plural($basename);
    $is_timestamped = false;
    $tableaction = ['table', 'create'];
    $create = 1;
    $spaces = '    ';
    $allFieldDefs = static::getTableFieldDefs();
    $currenttablefields = [];

    //$tableSchema = Schema::getConnection()->getDatabaseName();
    if (Schema::hasTable($tablename)) { #This is an update
      $create = 0;
      $createorupdate = 'update';
      #We will only change and add fields here.
      $currenttablefields = static::getStaticAttributeNames();
      $is_timestamped = in_array('updated_at', $currenttablefields);
      $currentmodelfields = static::getFieldNames();
      if (!$currentmodelfields)
          die("No table field names defined for Model [$basename]\n");
      $newfields = array_diff($currentmodelfields, $currenttablefields);
      $newfielddefs = array_subset($newfields, $allFieldDefs);
      $newfieldstr = static::buildMigrationFieldDefs($newfielddefs);
      $changedfields = array_intersect($currenttablefields, $currentmodelfields);
      $droppedfields = array_diff($currenttablefields, $currentmodelfields);
      $droppedfieldstr = '';
      foreach ($droppedfields as $droppedfield) {
        if (!in_array($droppedfield, ['created_at', 'updated_at', 'deleted_at']))
            $droppedfieldstr .= "$spaces\$table->dropColumn('$droppedfield');\n";
      }
      #Possibly changed fields:
      $changedFieldDefs = array_subset($changedfields, $allFieldDefs);
      $changedfieldstr = static::buildMigrationFieldDefs($changedFieldDefs, true);
      $fieldDefStr = $newfieldstr . $changedfieldstr . $droppedfieldstr;
      $createclassname = "PkUpdate" . $pbasename . "Table";
    } else {
      $createorupdate = 'create';
      $fieldDefStr = static::buildMigrationFieldDefs($allFieldDefs);
      $createclassname = "PkCreate" . $pbasename . "Table";
    }
    //$tabledefs = static::getStaticAttributeDefs();
    /** Check if the table exists in the DB */
    //$tableSchema = Schema::getConnection()->getDatabaseName();
    $timestamp = date('Y_m_d_His', time());
    //$createclassname = "Create" . $tablename. "Table";
    //$migrationfile = database_path() . "/migrations/{$timestamp}_create_{$tablename}_table.php";
    $migrationfile = database_path() . "/migrations/{$timestamp}_Pk{$createorupdate}_{$tablename}_table.php";
    $migrationheader = "
<?php
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
class $createclassname extends Migration {
  public function up() {
    Schema::{$tableaction[$create]}('$tablename', function (Blueprint \$table) {
";
    $migrationFunctions = '';
    foreach (static::getOnetimeMigrationFunctions() as $key => $func) {
      if (!in_array($key, $currenttablefields))
          $migrationFunctions .= "$spaces\$table->$func;\n";
    }
    $close = "
    });
  }";
    $down = "
  /**   * Reverse the migrations.  * */
  public function down() {
    Schema::drop('$tablename');
  }
      ";
    $closeclass = "\n}\n";
    $migrationtablecontent = $migrationheader .
        $fieldDefStr . $migrationFunctions . $close . $down . $closeclass;
    //return "migrationcontent: [\n\n$migrationtablecontent\n\nPath:\n\n$migrationfile";
    $fp = fopen($migrationfile, 'w');
    fwrite($fp, $migrationtablecontent);
    fclose($fp);
    return "Migration Table [$migrationfile] Created\n";
  }

  public static $mySqlIntTypes = ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'];
  public static $mySqlNumericTypes = ['tinyint', 'smallint', 'mediumint', 'int',
      'bigint', 'decimal', 'float', 'double', 'real', 'bit', 'boolean', 'serial'];
  public static $mySqlDateTimeTypes = [
      'date', 'datetime', 'timestamp', 'time', 'year',
      ];

  /** POSTs submit an empty string for "no value". For integer table fields, 
   * should we convert the empty string to null? Default for MySQL is to insert
   * 0 for '', when we will usually want NULL so that's default for 
   * $this->emptyStringToNull, but subclasses can override.
   * @var boolean - for integer table fields, convert empty string ('') to NULL
   * on save? 
   */
  public $emptyStringToNull = true;

  /** Used to store all the underlying DB column/attribute names of the model
   * as keys to the column data type. Further keyed by class/model name
   * @var Array 
   */
  public static $attributeDefinitionArr = [];
  //public static $attributeNameArr = [];
  /**
   * @var array - in extended classes, contains all "fillable" table fields as 
   * key, and either "true" (if the user can enter any value), an array of
   * values=>descriptions if the user may only select one of the values, OR
   * a closure, if the authorization requires some calculation.
   */
  public static $fillOpts = null;

  /** We trust the caller here! Thus, all keys of "$attributes" are used to
   * reset the "$this->fillable" array.
   * @param \App\Models\PkModel $parent
   * @param array $attributes
   * @param string $foreignKey
   * @param User $user
   * @return \static|boolean
   * @throws Exception
   */
  public static function createIn(PkModel $parent = null, Array $attributes = [], $foreignKey = null, User $user = null) {
    if (!static::authCreate($parent, $user)) return false;
    if ($parent) {
      if (!$parent->id) throw new Exception("Parent has no ID");
      if ($foreignKey === null)
          $foreignKey = Str::snake(getBaseName($parent)) . '_id';
      if (!is_string($foreignKey) || !$foreignKey)
          throw new Exception("Invalid Foreign Key");
      $attributes[$foreignKey] = $parent->id;
    }
    $model = new static();
    $origFillable = $model->fillable;
    $model->fillable(array_keys($attributes));
    $model->fill($attributes);
    $model->save();
    $model->fillable = $origFillable;
    $model->buildFillableOptions();
    return $model;
  }

  /** Called by Query Builder - that is, whenever a model is retrieved from the DB
   * 
   * @param array $attributes
   * @param type $sync
   */
  public function setRawAttributes(array $attributes, $sync = false) {
    parent::setRawAttributes($attributes, $sync);
    $this->buildFillableOptions();
  }

  /** Gets only the table field attribute or attributes - eg, ->user_id, but not
   * a User model instance. If $key not defined, just return nulls.
   * @param string|null $key - the table field name, or null for all
   * @return scalar|null|array - if $key given, the value (or null) - if 
   * no $key, an array of the table field names => values. 
   */
  public function getTableFieldAttributes($key = null) {
    $attributeNames = $this->getAttributeNames();
    //if ($this instanceof \App\Models\Borrower) pkdebug("AttributeNames:", $attributeNames);
    if ($key) {
      if (in_array($key, $attributeNames)) return $this->$key;
      else return null;
    }
    $retarr = [];
    foreach ($attributeNames as $attributeName) {
      $retarr[$attributeName] = $this->$attributeName;
    }
    return $retarr;
  }

  public function hasTableField($fieldName) {
    return $this->getTableFieldAttributes($fieldName);
  }

  /** Get all the defined table field names (attributes) from the static
   * class, without needing an instance.
   * @return array of table field names.
   */
  public static function getStaticAttributeNames() {
    return array_keys(static::getStaticAttributeDefs());
    # If problem w. getting attribute defs ...
    /*
      if (array_key_exists(static::class,static::$attributeNameArr)) {
      static::$attributeNameArr[static::class];
      }
      $instance = new Static();
      return $instance->getAttributeNames();
     */
  }

  /** Returns associative array of table column/field/attribute names as keys,
   * and column data types as values
   * @return Array
   */
  public static function getStaticAttributeDefs() {
    if (array_key_exists(static::class, static::$attributeDefinitionArr)) {
      return static::$attributeDefinitionArr[static::class];
    }
    $instance = new Static();
    return $instance->getAttributeDefs();
  }

  /** Returns the object if it is an instance of the Model class, and has
   * been instantiated; else null. So can set <tt>$var = Amodel::instantiated($var)</tt>
   * and it's either a real object or null.
   * @param static $var
   * @return static|null
   */
  public static function instantiated($var = null) {
    if ($var instanceOf static && $var->exists && $var->getKey()) return $var;
    return null;
  }

  public function real() {
    $class = get_class($this);
    return $class::instantiated($this);
    //return get_class($this)::instantiated($this);
  }

  /**
   * Checks to see if the $arg is instantiated and the same instance of this obj.
   * Ridiculous this is not built in...
   *** NOTE! As of Laravel 5.3, it finally is - but we want to return the model instance
   * @param any $var
   * $return boolean|static - false if not instantiated or not the same object, else the object
   */
  public function is(Model $var) {
    if (method_exists(get_parent_class(),'is')) {
      if (!parent::is($var)) return false;
    }
    if (!static::instantiated($var) || !static::instantiated($this))
        return false;
    if (get_class($this) !== get_class($var)) return false;
    if ($this->getKey() !== $var->getKey()) return false;
    return $this;
  }

  public function __debuginfo() {
    return $this->attributes;
  }

  /** The authXXX functions determine if the user is allowed to perform XXX
   * on the current Model instance. TO BE OVERRIDDEN in extended classes, but
   * some safe defaults are provided.
   * @return boolean
   */
  public function authDelete() {
    return true;
    if (isCli()) return true;

    return $this->authUpdate();
  }

  public function authRead() {
    return true;
    if (isCli()) return true;
    return $this->authUpdate();
  }

  public function authUpdate() {
    return true;
    if (isCli()) return true;
    return false;
  }

  public $allFillableOpts = [];

  /** Don't force using buildFillableOptions */
  public $useBuildFillableOptions = false;

  /**
   * Builds the $fillable and $fillableOptions arrays
   * Default method - should be overridden in some subclasses
   * THIS BASE VERSION depends on the PkModel extension
   * defining <tt>$this->allFillableOpts</tt>
   * <p>
   * AGAIN, not all implementations will require / desire 
   */
  public function buildFillableOptions($opts = null) {
    if (!$this->useBuildFillableOptions) return true;
    #authUpdate here can cause page failure
    #Can put it in some subclasses
    //if (!$this->authUpdate()) return false;
    $fillableOpts = $this->allFillableOpts;
    $this->fillable = array_keys($fillableOpts);
    $this->fillableOptions = $fillableOpts;
    return $this->fillableOptions;
  }

  public static $refYesNo = [
      0 => "No",
      1 => "Yes",
  ];

  /**
   *
   * @var assoc array - keyed by 'fillable' field, value is true, false, or array
   * of valid options for the field, depending on user and project state, etc.
   * Initially empty, populated by buildFillableOptions(). Something like:
   *   $allFillableOpts = [
    'hubid' => true,
    'firstname' => true,
    'lastname' => true,
    'company' => true,
    'title' => true,
    'phone' => true,
    'email' => true,
    'address' => true,
    'active' => true,
    //'suspended' => true,
    //'role_id' => self::refRoles,
    'role_id' => $this->assignableRoles(),
    'has_login' => true,
    ];
   */
  public $fillableOptions = [];

  /**
   * Default authCreate, depends on authUpdate permission of "owning" object
   * Model classes that can be created by users should implement this method...
   * Top level objects which have no "owners" (like, "User" or "Project"
   * should override this method without requiring a "owner" or "parent"
   */
  public static function authCreate(PkModel $parent = null, User $user = null) {
    return true;
    if (isCli()) return true;
    if ($parent && ($parent instanceOf PkModel)) {
      return $parent->authUpdate($user);
    }
    return false;
  }

  /**
   * Returns the "description" for a given $refArray and index.
   * @param array $refVar - reference array of int keys to Enum names/labels
   * like, <tt>[1=>"Mixed Construction Debris", 2=>"Source Separated",]</tt>
   * @param mixed $idx - key for the ref array
   * @return string - the name/label, or "Not Set"
   */
  public function showRefItemName(array $refVar, $idx = null) {
    if (!is_array($refVar) || !$idx) return "Not Set";
    return keyValOrDefault($idx, $refVar, "Not Set");
  }

  /**
   * @var array of table field names for the class that must distinct
   * that is, not have other records in the table with the same values
   */
  public $distinctFields = [];

  /** Returns any records of this class that have all the same values
   * as $this for the given field list $fieldArr, or uses the field list
   * in $this->distinctFields if $fieldArr is null.
   * @param array|null $fieldArr - array of table field names to search
   * and see if matching records already exist
   * @param boolean $andthis - default: false - also return this
   * @return EloquentCollection - already existing objects that have the same
   * values as this one for the given fields. Excludes '$this' if already exists
   */
  public function matchingRecords($fieldArr = null, $andthis = false) {
    if (!$fieldArr) $fieldArr = $this->distinctFields;
    if (!is_array($fieldArr) || !$fieldArr)
        throw new Exception("No fields for matchingRecords");
    $matchBuilder = static::query();
    foreach ($fieldArr as $fieldName) {
      $matchBuilder = $matchBuilder->where($fieldName, '=', $this->$fieldName);
    }
    #Exclude $this if already exists
    if (!$andthis && $this->id) {
      $matchBuilder = $matchBuilder->where('id', '!=', $this->id);
    }
    #get collection
    $matches = $matchBuilder->get();
    return $matches;
  }

  /**
   * Returns all the underlying names of the table fields of the model, whether
   * they are initialized or not.
   * @param type $Model
   */
  public function getAttributeNames() {
    return array_keys($this->getAttributeDefs());
    //return Schema::getColumnListing($model->getTable());
  }

  /** Returns associative array of table column names as keys, and the
   * column type as value. Caches the result in a static array.
   * @return array - table column names to DB Column type
   */
  public function getAttributeDefs() {
    if (array_key_exists(static::class, static::$attributeDefinitionArr)) {
      return static::$attributeDefinitionArr[static::class];
    }
    $tableName = $this->getTable();
    $tableSchema = Schema::getConnection()->getDatabaseName();
    $results = Schema::getConnection()->select("SELECT COLUMN_NAME, DATA_TYPE
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE table_name = '$tableName' AND table_schema = '$tableSchema'");
    $assocRes = [];
    foreach ($results as $result) {
      if (in_array($result->COLUMN_NAME, ['USER', 'CURRENT_CONNECTIONS', 'TOTAL_CONNECTIONS']))
          continue;
      $assocRes[$result->COLUMN_NAME] = $result->DATA_TYPE;
    }
    static::$attributeDefinitionArr[static::class] = $assocRes;
    return $assocRes;
  }

  /** For dev/debugging - open EVERYTHING as fillable
   * 
   * @param array $attributes
   */
  public function __construct(array $attributes = []) {
    $this->fillable($this->getAttributeNames());
    parent::__construct($attributes);
    #For some reason, the below inserts an empty 'name' field in SQL inserts
    //$this->transformer = new BaseTransformer($this);
  }

  public $transformers = [];

  public function __get($key) {
    //pkdebug("KEY", $key);
    # This seems pretty obvious - why don't Eloquent do it?
    if (!$key) return null;

    $name = removeEndStr($key, static::$displayValueSuffix);
    if ($name && in_array($name,static::getDisplayValueFields(),1)) {
      return $this->displayValue($name);
    }
    if (in_array($key,static::getMethodsAsAttributeNames(),true)) {
      return $this->$key();
    }
    if ($name) pkdebug("Name: [$name] valfields",static::getDisplayValueFields());
    return parent::__get($key);
  }
    public function getAttribute($key) {
      if (!$key) return null;
      return parent::getAttribute($key);

    }
    public function getAttributeValue($key) {
      if (!$key) return null;
      return parent::getAttributeValue($key);
    }

  public function __call($method, $args = []) {
    $name = removeEndStr($method,  static::$displayValueSuffix);
    if (ne_string($name) &&  in_array($name,static::getDisplayValueFields(),1)) {
      return $this->displayValueMethod($name, $args);
    }
    if (in_array($method,static::getMethodsAsAttributeNames(),true)) {
      return call_user_func_array([$this,$method], $args);
    }
    return parent::__call($method, $args);
  }


  /**
   *
   * @var array - relationshipName as key => 'Full\Model'
   *  MUST be initialized/filled in by each subclass - array of 
   * relations that should be populated in a recursive one-to-many save, OR
   * DELETED if parent deleted.
   * <p>
   * NOTE! ALSO USED FOR CASCADING DELETES!
   */
  public static $load_relations = [ /* 'items' => 'App\Models\Item' */];

  /** Default, just returns the static setting.
   */
  public function getLoadRelations() {
    return static::$load_relations;
  }

  /** For many to many edits. Needless to say, any changes/deletions stop at
   * the pivot table and do NOT modify the other side of the "Many" relationship
   * @var array - key names to relationship definitions 
   */
  public static $load_many_to_many = [
      /* 'items' => //The key here is what we call the relation when we access it
       *    [
       *     'other_model' => 'App\Models\Item' //The class we have many of.
       *     'pivot_table' => 'user_to_items' //The class we have many of.
       *            OR
       *      'pivot_model' => 'UserToItem',
       *      'my_key' => 'user_id' (Optional if it's just this default
       *      'other_key' => 'item_id' (Optional if it's just this default
       *    ],
       * ['roles' => etc
       */
  ];

  /** Checks if user is allowed to delete, and 
   * performs cascading deletes on relations defined in $this->getLoadRelations()
   * 
   */
  ## RE-ENABLE WHEN EVERYTHING ELSE WORKING - 
  ## Need to think through deleting both from here, AND from the "Save Relations"
  ## method below
  public function delete() {
    if (!$this->authDelete())
        throw new Exception("Not authorized to delete this object");
    foreach (array_keys($this->getLoadRelations()) as $relationSet) {
      if (is_array($relationSet) || $relationSet instanceOf BaseCollection) {
        foreach ($this->relationSet as $relationInstance) {
          if ($relationInstance instanceOf Model) {
            $relationInstance->delete();
          }
        }
      }
    }
    #Now deal with the trickier many-to-many deletes. Have to be sure to delete only the relationships, not the related object
    #But the Delete for many-to-many is really way easier than the Update - add or delete...
    foreach (static::$load_many_to_many as $relname => $definition) {
      $relSet = $this->$relname;
      if (!$relSet || !count($relSet)) continue;
      $pivotmodel = keyval('pivot_model', $definition);
      if (!class_exists($pivotmodel) || !is_a($pivotmodel, self::class, true)) {
        $pivotmodel = null;
        $pivottable = keyval('pivot_table', $definition);
        if (!Schema::hasTable($pivottable)) { #Can't do anything
          throw new Exception("Niether a valid pivot class nor tabe was defined for [$relname]");
        }
      }
      $mykey = keyval('my_key', $definition, Str::snake(getBaseName(static::class)) . '_id');
      #We have a pivot class or table. Let's try class first, it's classier
      if ($pivotmodel) {
        $pivotmodel::where($mykey, $this->getKey())->delete();
      } else {# Have to use the table name & Direct DB
        DB::table($pivottable)->where($mykey, $this->getKey())->delete();
      }
    }
    return parent::delete();
  }

  /** We assume $modelCollection is the original model set, and $modelDataArray
   * are changes, if any. $modelDataArray should be an indexed array of 
   * associative arrays of fields to values. Any 'id'/key fields that are null
   * are assumed to be new, and created. Any id's present in $modelCollection 
   * but absent from $modelDataArray are assumed to be deleted, so the model is
   * deleted. When they have IDs in common, the models are updated with the
   * data from the array.
   * @param $modelCollection - EloquentCollection of PkModels
   * @param $modelDataArray array of new model data, probably from a POST
   */
  public static function updateModels($modelCollection, $modelDataArray) {
    $tstInstance = new static();
    $keyName = $tstInstance->getKeyName();
    $arrayKeys = [];
    foreach ($modelDataArray as $modelDataRow) {
      if (empty($modelDataRow[$keyName])) { #It's new, let's try to create it
        try {
          static::create($modelDataRow);
        } catch (Exception $ex) {
          pkdebug("Inserting Posted Data Row", $modelDataRow, 'Failed with Exception:', $ex);
        }
      } else {
        $arrayKeys[$modelDataRow[$keyName]] = $modelDataRow;
      }
    }
    foreach ($modelCollection as $model) {
      if (!in_array($model->$keyName, array_keys($arrayKeys))) $model->delete();
      else $model->saveRelations($arrayKeys[$model->$keyName]);
    }
  }

  /**
   * <B>DOES THIS JUST DUPLICATE MODEL::push()?</B>
   * <p>NO, because takes an array of data and SETS the model values and relations
   * <p>
   * Save direct attributes and 1-many relations; typically from Controller POSTs
   * NOT COMPLETE! Add features to this method as required. Currently, only 
   * works for direct attributes and one-to-many relationships, single level.
   * DEPENDS on defining <tt>$this->getLoadRelations()</tt> using default
   * foreign key name for this model
   * <p>
   * Saves the argument array (typically from a Form input) to the Model/DB,
   * supporting 1-to-many relationships. 
   * <p>
   * NOTE: The $getLoadRelations() member array must be initialized w. relations in
   * the model constructor.
   * 
   * @param array $arr -- array of field names and values - with array keys as
   * attribute names -- when the array key is a one-to-many relationship, 
   * the next level of array indexes are integer array indices, each of which 
   * represents a row in the many side of the relationship.
   * <p>
   * NOT EVERY INDEX OF THE param $arr needs to have a corresponding attribute
   * in the model. BUT: If you have a one-to-many relationship AND YOU WANT TO
   * BE ABLE TO DELETE ALL ON THE MANY SIDE - MAKE SURE YOUR INPUT DATA CONTAINS
   * a Key with the relationship value, and a value of an empty array or null.
   * <p>
   * If a Key doesn't exist, the relationships won't be deleted, they will be 
   * ignored. An easy way to do this in a form that contains an array of 0 or more
   * relationship elements is to create a hidden input with the name of the relationship
   * and value null.
   * <p>
   * @return true|string|Validator - if successful, boolean true, anything not
   * boolean true is an error - error message or Validator containing errors, etc.
   */
  public function saveRelations(Array $arr = []) {
    if (!$this->authUpdate())
        throw new Exception("Not authorized to update this object");
    $relations = $this->getLoadRelations();
    $foreignKey = $this->getForeignKey();
    $this->fillFillables($arr);
    $this->save();
    $modelId = $this->id;
    $this->saveM2MRelations($arr);
    //pkdebug("POST IS: ", $_POST, 'arr', $arr);
    foreach ($relations as $relationName => $relationModel) {
      if (!array_key_exists($relationName, $arr)) continue;
      //pkdebug("Processing Relation: [$relationName], [$relationModel]");
      $tstInstance = new $relationModel();
      $keys = [];
      $keyName = $tstInstance->getKeyName();
      $relarr = $arr[$relationName];
      if (is_array($relarr))
          foreach ($relarr as $relrow) {
          //if (!array_key_exists($keyName,$relrow)) continue; //Not a relationship item
          if (!empty($relrow[$keyName])) { #This should be an update
            $relId = $relrow[$keyName];
            $keys[] = $relId;
            $relInstance = $tstInstance->find($relId);
            if (!$relInstance instanceOf PkModel) { #Report unexpected problem -
              $thisclassname = get_class($this);

              pkdebug("ERROR: In class [$thisclassname], id [{$this->id}]
                For Relationship name [$relationName], of type [$relationModel], 
                Expected to find an instance with ID [$relId] but didn't!
                Skipping silently, but should figure out why!");
              continue;
            }
          } else { # This is a new relationship/member item - or a delete?
            if (!is_array($relrow)) {
              pkdebug("Huh. RelRow is:", $relrow);
              continue;
            }
            //pkdebug("A new relationship? RelRow:", $relrow);
            $relInstance = new $relationModel();
            if (!array_key_exists($foreignKey, $relrow)) {
              $relrow[$foreignKey] = $modelId;
            }
            #Again, compensate for empty string POSTED instead of NULL
            if (!array_key_exists($keyName, $relrow)) {
              pkdebug("Relrow:", $relrow);
            }
            if (!$relrow[$keyName]) $relrow[$keyName]=null;
            //if (!keyVal($keyName,$relrow)) $relrow[$keyName]=null;
          }
          /** Either: */
          //$relInstance->fillFillables($relrow);
          //$relInstance->save();

          /** OR (Better if it works, recursive ...) */
          $relInstance->saveRelations($relrow);

          /** END EITHER */
          $keys[] = $relInstance->getKey();
        }
      #Delete if id not in array AND foreign key = foreign key
      //   pkdebug("Delete [$relationName]s where [$foreignKey]=[$modelId] AND Keys NOT  in: ", $keys);
      if (!sizeof($keys)) $this->$relationName()->delete();
      else $this->$relationName()->whereNotIn($keyName, $keys)->delete();
    }
    return true;
  }

  /** This relies on the m-m definitions in the static $load_many_to_many array
   * Much more complicated than just saving one to many, or deleting many to many....
   * The daya should be an array that contains keys to the relationship matching
   * the relationship names defined in the object.
   * 
   * Like if this class has many-to-many relationships with items and
   * children, it should have those relationships defined in the class, and
   * also defined in the static::$load_many_to_many variable, with the key
   * names of $load_many_to_many the same as the relationship names
   */
  public function saveM2MRelations($data = []) {
    //pkdebug("Saving Here Data:", $data);
    if (empty(static::$load_many_to_many) ||
        !array_intersect(array_keys(static::$load_many_to_many), array_keys($data))) {
      return true; #Nothing to do
    }
    foreach (static::$load_many_to_many as $relName => $definition) {
      if (!in_array($relName, array_keys($data))) continue;#Nothing here, keep looking
      $othermodel = keyval('other_model', $definition);
      if (!class_exists($othermodel) || !is_a($othermodel, self::class, true)) {
        throw new Exception("No found other class was defined for [$relName]");
      }
      #Have the 'other' class - now find Pivot Class or Table
      $pivotmodel = keyval('pivot_model', $definition);
      if (!class_exists($pivotmodel) || !is_a($pivotmodel, self::class, true)) {
        $pivotmodel = null;
        $pivottable = keyval('pivot_table', $definition);
        if (!Schema::hasTable($pivottable)) { #Can't do anything
          throw new Exception("Niether a valid pivot class nor tabe was defined for [$relName]");
        }
      }
      $mykey = keyval('my_key', $definition, Str::snake(getBaseName(static::class)) . '_id');
      $otherkey = keyval('other_key', $definition, Str::snake(getBaseName($othermodel)) . '_id');

      #Here's where the easy part ends. 
      $arr = $data[$relName];
      if (!is_arrayish($arr) || !count($arr)) { #Delete it all!
        $deleteAll = true;
      } else { #We have an array of data
        $otherobjs = $this->$relName;
        if (!is_arrayish($otherobjs)) {
          pkdebug("unexpected for [$relName], other objs are:", $otherobjs);
          continue;
        }
        $mycurrentotherobjkeys = [];
        foreach ($otherobjs as $otherobj) {
          if (!is_a($otherobj, $othermodel, true)) { #Again, something seriously wrong
            pkdebug("For [$relName], other model is [$othermodel], but otherobj:", $otherobj);
            continue;
          }
          $otherobjkey = $otherobj->getKey();
          $mycurrentotherobjkeys[] = "$otherobjkey";
        }
        #Great - we have a list of otherobj keys our model pointed to, we have a new 
        #submitted list of other obj keys - let's go!
        #But gotta clean up the keys in case some are 3 & some are '3'!
        #Just make them all strings?
        //pkdebug("Array is:", $arr);
        $newarr = [];
        foreach ($arr as $el)
          $newarr[] = "$el";
        $addIds = array_diff($newarr, $mycurrentotherobjkeys);
        $idsToDelete = array_diff($mycurrentotherobjkeys, $newarr);
      }
      $thiskeyval = $this->getKey();
      $fresh = [];
      if ($pivotmodel) {
        if (!empty($deleteAll)) {
          $pivotmodel::where($mykey, $thiskeyval)->delete();
        } else {
          $pivotmodel::where($mykey, $thiskeyval)->whereIn($otherkey, $idsToDelete)->delete();
          $fresh [$mykey] = $thiskeyval;
          foreach ($addIds as $addId) {
            $fresh[$otherkey] = $addId;
            //pkdebug("Adding", $fresh);
            $pivotmodel::create($fresh);
          }
        }
      } else if (Schema::hasTable($pivottable)) { #Gotta try it with flat table
        if (!empty($deleteAll)) {
          DB::table($pivottable)->where($mykey, $thiskeyval)->delete();
        } else {
          DB::table($pivottable)->where($mykey, $thiskeyval)->whereIn($otherkey, $idsToDelete)->delete();
          $fresh [$mykey] = $thiskeyval;
          foreach ($addIds as $addId) {
            $fresh[$otherkey] = $addId;
            DB::table($pivottable)->insert($fresh);
          }
        }
      }
    }
  }

  /*
    #We have a pivot class or table. Let's try class first, it's classier
    if ($pivotmodel) {
    $pivotmodel::where('$foreignKey', $this->getKey())->delete();
    } else  {# Have to use the table name & Direct DB
    DB::table($pivottable)->where($mykey, $this->getKey())->delete();
    }









    $pivotclass = null;
    $pivottable = keyval('pivot_table', $definition);
    if (!Schema::hasTable($pivottable)) { #Can't do anything
    throw new Exception("Niether a valid pivot class nor tabe was defined for [$relname]");
    }
    $othermodel = keyval('other_model',$definition);
    $mykey = keyval('my_key', $definition, Str::snake(getBaseName(static::class)) . '_id');
    $otherkey =  keyval('my_key', $definition, Str::snake(getBaseName()) . '_id');

   */

  /*
    public static $load_many_to_many = [
    'publicgroups' =>
    [
    'other_model' => 'App\Models\PublicGroup',
    'pivot_model' => 'App\Models\PublicGroupToQProfile',
    //'my_key' => 'user_id',
    //'other_key' => 'item_id' (Optional if it's just this default
    ],
    ];
   * 
   */

  /*
    public $load_many_to_many = [
    / 'items' => //The key here is what we call the relation when we access it
   *    [
   *     'foreign_class' => 'App\Models\Item' //The class we have many of.
   *     'pivot_table' => 'user_to_items' //The class we have many of.
   *            OR
   *      'pivot_model' => 'UserToItem',
   *      'my_key' => 'user_id' (Optional if it's just this default
   *      'other_key' => 'item_id' (Optional if it's just this default
   *    ],


    }
   * 
   */

  /**
   * Fills this objects fillable fields, if the key exists in the data.
   * @param array $data - Associative array of data to set this model's fillables
   * with. Eventually expand to ArrayAccess, etc
   * @return \App\Models\PkModel $this
   */
  public function fillFillables(Array $data = []) {
    $relFillables = $this->getFillable();
    foreach ($relFillables as $relFillable) {
      if (array_key_exists($relFillable, $data)) {
        $this->$relFillable = $data[$relFillable];
      }
    }
    //$attributes = $this->getAttributes();
    //pkdebug('data',$data, 'modelattributes', $attributes);
    return $this;
  }

  /**
   * Returns an array of attributeNames/Values, but uses the $model->attributeName,
   * to trigger any Accessor methods
   * @param boolean $withAttributes - default- true - also return Model
   * attributes that are not table fields. If false, only attributes that are field names
   * @return array - attributeNames=>values
   */
  public function getAccessorAttributes($withAttributes = true) {
    $attributeNames = $this->getAttributeNames();
    $extraKeys = [];
    if ($withAttributes) $extraKeys = array_keys($this->getAttributes());
    $attributeNames = array_merge($attributeNames, $extraKeys);
    $retArr = [];
    foreach ($attributeNames as $attributeName) {
      $retArr[$attributeName] = $this->$attributeName;
    }
    return $retArr;
  }

  /** Returns false, true, or array of valid options (keyed w. db_value=>display_val)
   * BUT don't want to force this - if $model->useBuildFillableOptions == false,
   * just return the base model "isFillable($fieldName)" 
   * @param string $fieldName - the name of the DB field to check against
   * @return mixed - boolean false (can't set this field), true (set field to any value),
   * or array
   */
  public function canEditThisField($fieldName) {
    if (!$this->useBuildFillableOptions) return $this->isFillable($fieldName);
    $fillableOptions = $this->fillableOptions;
    if (!array_key_exists($fieldName, $fillableOptions)) return false;
    return $fillableOptions[$fieldName];
  }

  /**
   * Returns the friendly value to display for the field.
   * <p>
   * Check if a translation/format class is defined for the field, as in:
   * public static $display_value_fields = [
      'typeoffinancing_id' => 'App\References\TypeOfFinancingRef',
   * ];
   * that implements PkDisplayValueInterface;
   * (like, status_id), return the mapped status display text.
   * @param string $fieldName - the name of the DB field to examine
   * @return string - the user-friendly text to display
   */
  public function displayValue($fieldName, $value = null) {
    if (!ne_string($fieldName)) return null;
    $refmaps = static::getDisplayValueFields(true);
    foreach ($refmaps as $fn => $rc) {
      #$rc can be a String classname that implements PkDisplayValueInterface, OR
      #an array of [$className,$args] where $args= date format, or $ precision, whatever
      $args = null;
      if (is_array($rc)) {
        $args = $rc[1];
        $rc = $rc[0];
      } 
      if (!ne_string($fn) || !ne_string($rc) || !class_exists($rc) || 
          !in_array('PkExtensions\PkDisplayValueInterface', class_implements($rc))) {
        continue;
      }
      if ($fn === $fieldName) {
        $fldVal = $this->$fieldName;
        //$class = get_class($this);
        //pkdebug("In [$class] val: [$fldVal] About to call: [$fn] on [$rc]");
        return $rc::displayValue($this->$fieldName,$args);
      }
    }
    if ($value === null) return $this->$fieldName;
    return $value;
  }

/** 
  If the key of displayvalues is a method, the class it points to must implement
  the PkDisplayValueInterface - then the method is called, with the optional args,
  and the result of the method is processed by the DisplayValueInterface class
  and THAT result is returned. Dollar or Percent Formatting, for example.
 */
  public function displayValueMethod($methodName, $args=[]) {
    if (!ne_string($methodName)) return null;
    //$class=get_class($this);
    $refmaps = static::getDisplayValueFields(true);
    foreach ($refmaps as $fn => $rc) {
      if (!ne_string($fn) || !ne_string($rc) || !class_exists($rc) || 
          !in_array('PkExtensions\PkDisplayValueInterface', class_implements($rc))) {
        continue;
      }
      if ($fn === $methodName) {
        $value = call_user_func_array([$this,$methodName], $args);
        return $rc::displayValue($value);
      }
    }

  }

  /** Should we convert all int attributes with value of '' to null? Let's try
   * 
   * @param array $opts
   * @return type
   * @throws Exception
   */
  public function save(array $opts = []) {
    if (!$this->authUpdate())
        throw new Exception("Not authorized to update this record");
    if ($this->useBuildFillableOptions) {
      foreach ($this->fillableOptions as $field => $value) {
        if (is_array($value)) {
          $allowedVals = array_keys($value);
          if (!in_array($this->$field, $allowedVals)) unset($this->$field);
        }
      }
    }
    if ($this->emptyStringToNull) $this->convertEmptyStringToNullForNumerics();
    $result = parent::save($opts);
    if ($result) $this->postSave($opts);
    return $result;
  }

  /** This is just to enable a postCreate() method subclasses can implement
   * to take particular action after a new instance has been created and saved
   * <P>
   * <b><tt>performInsert</tt></b> is only called from Model::save() - 
   * So every insert will also involve a save, but not every save involves an
   * insert - SO ANYTHING THAT INVOKES postCreate() WILL ALSO CALL postSave()!
   * Put another way, postSave() will aslo be called on create, so you can put it all in there...
   * @param Builder $query
   * @param array $options
   * @return type
   */
  protected function performInsert(Builder $query, array $options = []) {
    $result = parent::performInsert($query, $options);
    if ($result === true) $this->postCreate($options);
    return $result;
  }

  /** To replace a "listener" and localize behavior after a successful save() -
   * Like postCreate() - SO TWO CLASSES shouldn't implement both postSave & postCreate
   * @param array $opts - whatever opts were sent to save
   */
  public function postSave(Array $opts = []) {
    
  }

  /**
   * @param array $options - comes from "save" options, in case we want to add any
   */
  public function postCreate(Array $options = []) {
    
  }

  /** When POSTING empty values, can't POST nulls, get converted to ''
   * No good for int & date types, even if they allow NULL, so convert to NULL
   * @param boolean $anddates: true - and dates?
   */
  public function convertEmptyStringToNullForNumerics($anddates=true) {
    $attributeDefs = $this->getAttributeDefs();
    foreach ($attributeDefs as $name => $type) {
      if ( ($this->$name === '') &&
          (in_array(strtolower($type), static::$mySqlNumericTypes) ||
          ($anddates && in_array(strtolower($type), static::$mySqlDateTimeTypes)))) { 
          $this->$name = null;
      }
    }
  }

  /** Just so I can override in descendent classes - like get calculated 
   * attributes, or one to many relationship objects, etc
   * @return array of model attributes
   */
  public function getCustomAttributes($arg=null) {
    return $this->getAttributes();
  }
#If want to return array of ALL real & virtual attributes
#  public function getCustomAttributes($arg = null) {
#    $myAtts = $this->getAttributes();
#    $relationAtts = $this->getRelationshipAttributes();
#    $methodAtts = $this->getMethodAttributes();
#    $dvAtts = $this->getDisplayValueAttributes();
#    return array_merge($dvAtts, $methodAtts, $relationAtts, $myAtts);
#  }


/** Gets the Display Value attributes of Methods & IDs */
  public function getDisplayValueAttributes($arg = null) {
    $dva = [];
    //pkdebug("The Fields:", static::getDisplayValueFields());
    foreach (static::getDisplayValueFields() as $dvf) {
     // $theval = $this->$dvf;
   //  pkdebug("DVF", $dvf);
      if (method_exists($this, $dvf)) {
    //    pkdebug("And trying call method on DVF", $dvf);
        $dva[$dvf.static::$displayValueSuffix] = $this->displayValueMethod($dvf);
      } else if ($this->hasTableField($dvf)) {
     //   pkdebug("Thinks is table field DVF", $dvf);
        $dva[$dvf.static::$displayValueSuffix] = $this->displayValue($dvf);
      }
    }
    return $dva;
  }

  /**
   * Gets the attributes from the named relation. If $relationship is empty,
   * tries to use $this->getLoadRelations() to get the names and types of
   * all related models, and load them as an array 
   * 
   * @param string|array|null $relation - the name of the relation, and array of relation names
   * (optionally keyed by name with ModelClass as value), or empty to get
   * all the relations this model knows about.
   */
  public function getRelationshipAttributes($relations = null) {
    $resarr = [];
    $loadRelations = $this->getLoadRelations();
    if (is_string($relations)) {
      $relations = [$relations];
    }
    if (!is_array($relations)) {
      $relations = array_keys($loadRelations);
    }
    if (!$relations) return [];
    if (!is_array($relations)) {
      throw new \Exception("Expected an array as an argument");
      return [];
    }
    foreach ($relations as $relation) {
      $atype = typeOf($this->$relation);
      $sz = count($this->$relation);
      $this->load($relation);
      if ($this->$relation instanceOf \PkExtensions\Models\PkModel) {
        $resarr[$relation] = $this->$relation->getCustomAttributes();
      } else if ($this->$relation instanceOf \Illuminate\Database\Eloquent\Model) {
        $resarr[$relation] = $this->$relation->getAttributes();
        //} else if ($this->$relation instanceOf \Illuminate\Database\Eloquent\Collection) {
      } else if (is_arrayish($this->$relation) && count($this->$relation)) {
        $resarr[$relation] = [];
        foreach ($this->$relation as $instance) {
          if ($instance instanceOf \PkExtensions\Models\PkModel) {
            $resarr[$relation][] = $instance->getCustomAttributes();
          } else if ($instance instanceOf \Illuminate\Database\Eloquent\Model) {
            $resarr[$relation][] = $instance->getAttributes();
          //} else if ($instance instanceOf \Illuminate\Database\Eloquent\Collection) {
          } else {
            $toi = typeOf($instance);
            //pkdebug("For relation name: [$relation], instance type: [$toi]");
          }
        }
      }
    }
    return $resarr;
  }

  public function getEncodedCustomAttributes($args=null) {
    $ca = $this->getCustomAttributes($args);
    $jsenc = json_encode($ca, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $htenc = html_encode($jsenc);
    //$htenc = static::encodeData($ca);
    return $htenc;
  }

  /** Probably useless method, but a subclass might want to do something with it.
   * More usefed in the controllers */
  public function shouldProcessPost($opts = null) {
    if (Request::method() === 'POST') return true;
    return false;
  }

  public function processPost($opts = null) {
    return false;
  }

  /** First get all the methods of this class that start with
   * 'getTableFieldDefsExtra' then execute them and return all
   * the extra defined table field defs - for example, the BuildQueryTrait
   * would define getTableFieldsDefsExtraBuildQueryTrait();
   */
  public static function getExtraTableFieldDefs() {
    $fnpre = 'getTableFieldDefsExtra';
    $methods = get_class_methods(static::class);
    $tfmethods = [];
    $tfdefsets = [];
    foreach ($methods as $method) {
      if (startsWith($method, $fnpre, false)) {
        $tfmethods[] = $method;
      }
    }
    if (!$tfmethods || !is_array($tfmethods) || !count($tfmethods)) return [];
    foreach ($tfmethods as $tfmethod) {
      $res = static::$tfmethod();
      if ($res && is_array($res) && count($res)) {
        $tfdefsets[] = $res;
      }
    }
    if (!$tfdefsets || !is_array($tfdefsets) || !count($tfdefsets)) return [];
    if (count($tfdefsets) === 1) return $tfdefsets[0];
    return call_user_func_array('array_merge', $tfdefsets);
  }

  /**
   * Return a random instance, or array (collection) of random instances
   * @param integer $num: If -1 (default), return single instance. 
   *   if ($num >= 0) return array/collection of $num instances
   * @param array $params - Can be used by subclasses to filter
   * @return instance|array instances - 
   */
  public static function getRandomInstances($num = -1, $params = []) {
    if ($num === 0) return [];
    $instances = static::all();
    $numinst = count($instances);
    if (!$numinst) {
      if ($num === -1) return null;
      return [];
    }
    return PkTestGenerator::getRandomData($instances, $num);
  }

  /** Mutators for integer attributes - to change '' to NULL */
  /** If getting data from POST, the empty value is converted to '' in POST array.
   * Inserting '' into an integer field in MySQL is converted to 0 - so if you want
   * to insert null in an integer field from a POST, you have to convert '' to NULL,
   * and use a data mutator to convert it:
   * @param type $value
   */
  /** Example 
    public function setLoanamtAttribute($value) {
    $this->attributes['loanamt'] = intOrNull($value);
    }
   */
}
