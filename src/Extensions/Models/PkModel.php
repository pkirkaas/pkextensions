<?php
/** Adds functionality to base Eloquent\Model
 * Highlights:
 * If your model defines the static variable $table_field_defs, ex:
 * <pre>
 *   public static $table_field_defs = [
      'institution_id'=>['type'=>'integer','methods'=>'nullable'],
      'name'=>['type'=>'string','methods'=>'nullable'],
      'looking_b'=>['type'=>'boolean','methods'=>['nullable','default'=>1]],
      'address' => ['type' => 'string', 'methods' => 'nullable'],
 ]; 
 * </pre>
 * your model (both static & instance) knows its attributes, AND can generate
 * Migration create & update files for the DB. Extended PkModel classes inherit
 * their ancestor $table_field_defs; example: 
 * <pre>
 * PkModel::$table_field_defs = [ 'id' => 'increments',];
 * </pre>
 * ... so any model that extends PkModel will already have default ID defined.
 * 
 * If your model defines the static variable: $load_relations, ex:
 * <pre>
 *   public static $load_relations = [
      'appointments' => 'App\\Models\\Appointment',
      'diagnoses' => 'App\\Models\\Diagnosis',
  ];
 * </pre>
 * the method <tt>->saveRelations()</tt> will save the model attributes AND
 * create/update/delete the one-to-many relationships; like a Cart with Items.
 * Use in conjunction with PkController->processPost, & pklib.js templates.
 * 
 * If your model defines the static array: $display_value_fields, ex:
 * <pre>
 *   public static $display_value_fields = [
 *       'location_id' => 'App\References\LocationRef',
 * ];
 * </pre>
 * <tt>$model->location_id</tt> will return the location ID; but 
 * <tt>$model->location_id_DV</tt> will return the look-up "DisplayValue" from
 * <tt>'App\References\LocationRef'</tt>
 * 
 * In the default, all auths are true - if you want to default to false, sublcass
 * this and set them to false. Individual models will do a test on user permissions.
 */

namespace PkExtensions\Models;

use PkExtensions\Traits\UtilityMethodsTrait;
use PkExtensions\PkCollection;
use Illuminate\Database\Eloquent\Builder;
use Closure;
use Schema;
use ReflectionClass;
//use App\Models\User;
//use \Auth;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use \Request;
use \Exception;
use \DB;
use \PkExtensions\PkTestGenerator;
use PkExtensions\PkException;
use PkHtml;

abstract class PkModel extends Model {

  use UtilityMethodsTrait;

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

  /** Fields to clean of dangerous HTML tags before saving
   *
   * @var array - example: ['notes']; 
   */
  public static $escape_fields = [];


//Display Value Fields
  public static $displayValueSuffix = "_DV"; #Can override in extended models


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
   * But if $onlyrefs = true, ONLY RETURNS an ASSOC ARRAY of display value fields
   * as array keys mapped to PkRef classes - or actually, classes that implement
   * the PkDisplayValueInterface
   */
  public static function getDisplayValueFields($onlyrefs = false) {
    $display_value_fields = static::getAncestorArraysMerged('display_value_fields');
    if ($onlyrefs) {
      $refarr = [];
      foreach ($display_value_fields as $key => $value) {
        if (is_string ($key) && $value) {
          $refarr[$key] = $value;
        }
      }
      return $refarr;
    }
    if (!$display_value_fields ||
        !count($display_value_fields)) {
      return [];
    }
    $normalized = normalizeConfigArray($display_value_fields);
    if (is_array($normalized)) return array_keys($normalized);
    return [];
  }


  /** Since $table_field_defs are inherited, if a subclass wants to eliminate
   * some of the ancestor keys - like, use a different name for 'id' key, list
   * those key names here.
   * @var array 
   */
  public static $unset_table_field_keys = [];

  public static $requiredArgs = [];


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
  public function getMethodAttributes($arg=null) {
    $myclass = get_class($this);
    $methodAttributes = static::getMethodsAsAttributeNames();
    if (!$methodAttributes || !is_arrayish($methodAttributes) || !count($methodAttributes)) {
      return [];
    }
    $resarr = [];
    foreach ($methodAttributes as $methodAttribute) {
      $methatt =  $this->$methodAttribute();
      //$tma = typeOf($methatt);
      //if ($methodAttribute == 'friends')
     // pkdebug("MthAtt in Cls: [$myclass], metat: [$methodAttribute], tma: [$tma]");
      if ($methatt instanceOf PkModel) {
        $resarr[$methodAttribute] =  $methatt->getCustomAttributes($arg);
      } else if ($methatt instanceOf EloquentCollection) {
        $resarr[$methodAttribute] =  [];
      //if ($methodAttribute == 'friends') pkdebug("Num Friends: ".count($methatt));
        foreach($methatt as $pkinst) {
      //if ($methodAttribute == 'friends') pkdebug("Incrementing...PKINST: ".$pkinst->which());
          $resarr[$methodAttribute][] = $pkinst->getCustomAttributes($arg);
        }
      } else {
        $resarr[$methodAttribute] =  $methatt;
      }
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
      return false; //ucfirst($attname);
    }
    $attprops = $defs[$attname];
    $desc = keyVal('desc',$attprops,keyVal('comment',$attprops,labify($attname)));
    return $desc;
  }

  public static function get_field_type($fieldname) {
    $fielddef = KeyVal($fieldname, static::getTableFieldDefs());
    if (!$fielddef) return false;
    if (is_string($fielddef)) return $fielddef;
    if (is_array($fielddef)) return keyval('type', $fielddef);
    return false;
  }


  /**
   * Get the table associated with the model.
   * Because the Eloquent Model class needs an instance to get a table name...
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
    /*
    if (!defined('DEBUG')) define('DEBUG', true);
    \PkLibConfig::setSuppressPkDebug(false);
    appLogPath(__DIR__ . "/buildmigration.log");
     * (This is going to be in vendors/pkirkaas/PkEx..../Models dir
     */

    pkdebug("Incoming field def:", $fielddefs);
    $changestr = '';
    $indices = ['index', 'unique', 'primary'];
    if ($change) $changestr = '->change()';
    $out = "\n";
    $spaces = "    ";
    //foreach (static::$table_field_defs as $fieldName => $def) {
    foreach ($fielddefs as $fieldName => $def) {
      $type = null;
      pkdebug("Defs for '$fieldName',", $def);
      $methodChain = '';
      if (is_string($def))
          $out.="$spaces\$table->$def('$fieldName')$changestr;\n";
      else if (is_array($def)) {
        $defvals = array_values($def);
        if (array_key_exists('type',$def)) {
          $type = keyVal('type', $def, 'integer');
        } else { #Column def could be just a value in the def array
          pkdebug("FieldName: $fieldName;  dfvvals", $defvals);
          foreach ($eloquentMigrationColumnDefs as $emd) {
            if (in_array($emd,$defvals,1)) {
              $type = $emd;
              pkdebug("Broke on match for $emd");
              break;
            }
          }
          if (!isset($type)) $type='integer';
        }
        //$type = keyVal('type', $def, 'integer');
        $type_args = keyVal('type_args', $def) ? ', ' . keyVal('type_args', $def) : '';
        $comment = keyval('comment', $def);
        $default = keyval('default', $def);
        $methods = keyval('methods', $def, []);
        $fielddef = "$spaces\$table->$type('$fieldName'$type_args)";
        if ($comment) $fielddef .= "->comment(\"$comment\")";
        if ($default!==null) $fielddef .= "->default($default)";
        if (is_string($methods)) {
          //Try this instead to normalize - 4 May 2017
          $methods=[$methods];
          /*
          if ($change && (in_array($methods, $indices)))
              $fielddef .= $changestr . ";\n";
          else $fielddef .="->$methods()$changestr;\n";
          $out .= $fielddef;
          continue;
           */
        }
        $usedMethods=[];
        if (is_array($methods)) {
          foreach ($methods as $method => $args) {
            if (is_int($method)) {
              $method = $args;
              $args = null;
              $usedMethods[]=$method;
            }
            if (!$change || !in_array($method, $indices))
                $methodChain .= "->$method($args)";
          }
        }
        #Look for $def VALUES, for attributes that don't require args
        #index, for example, could be in "methods", with args, or just a value
        #in the def array
        $standaloneModifiers = ['primary','index','unique','nullable','unsigned','useCurrent',];
        pkdebug("DefVals for $fieldName:",$defvals);
        foreach($standaloneModifiers as $sam) {
          if (in_array($sam,$defvals,true) && !in_array($sam,$usedMethods,1)) {
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

  /** Either create new, or update if table already exists */
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
        if (!in_array($droppedfield, ['created_at', 'updated_at', 'deleted_at','remember_token']))
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
    $fp = fopen($migrationfile, 'w');
    fwrite($fp, $migrationtablecontent);
    fclose($fp);
    return "Migration Table [$migrationfile] Created\n";
  }


  /** Drop the table from the DB. If the table is named, drop that, if null,
   * the table for this model
   * @param string|null $tablename
   */
  public static function dropTable($tablename = null) {
    if (!$tablename) $tablename = static::getTableName();
    if (Schema::hasTable($tablename)) {
      Schema::drop($tablename);
    }
  }

  /** Delete the migration files auto generated by this Model */
  public static function deleteMigrationFiles() {
    $tablename = static::getTableName();
    $migrationpattern = database_path() . "/migrations/*_Pk*_{$tablename}_table.php";
    foreach( glob($migrationpattern) as $file) {
      unlink($file);
    }
  }

  public static $mySqlStrTypes = ['char','varchar','tinytext', 'text', 'mediumtext', 'longtext',];
  public static $mySqlIntTypes = ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'];
  public static $mySqlNumericTypes = ['tinyint', 'smallint', 'mediumint', 'int',
      'bigint', 'decimal', 'float', 'double', 'real', 'bit', 'boolean', 'serial'];
  public static $mySqlDateTimeTypes = [
      'date', 'datetime', 'timestamp', 'time', 'year',
      ];

  public $cleanAllText = true; #Default - runs hpure/escapes every text field
  public $trustedTextFields = ['password']; #Keep cleanAllText true by default, only allow specific fields by.

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
   * @param Model $user - Should be a 'User' instance, but no default 'User' model
   * @return \static|boolean
   * @throws Exception
   */
  //public static function createIn(PkModel $parent = null, Array $attributes = [], $foreignKey = null, Model $user = null) {
  public static function createIn(PkModel $parent = null, Array $attributes = [], $foreignKey = null) {
    //if (!static::authCreate($parent, $user)) return false;
    if (!static::authCreate($parent)) return false;
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

  /** Can be called from construct. The model sets any attributes   * to the value of the $att value if it exists, unless the
   * att name is in the indexed array of excludes.
   *
   * that have an attribute in the model
   * @param assoc_array $arr - initializing attributes
   * @param indexed array of atts to  $exclude
   */
  public function initializeFromArr($arr,$exclude=[]) {
    $myattnames = static::getStaticAttributeNames();
    $arrkeys = array_keys($arr);
    foreach ($myattnames as $myattname) {
      if (in_array($myattname, $arrkeys, 1) &&
          !in_array($myattname, $exclude,1) &&
          !$this->$myattname) {
        $this->$myattname = $arr[$myattname];
      }
    }
    return $this;
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
   * and it's either a real PkModel object or null.
   * But it could be an instance of a subclass of Amodel, not necessarily 
   * @param $var - the value to test
   * @param boolean $exact - default false - if true, returns false if $var is
   * not a specific instance of this class (& not subclass)
   * @return static|null
   */
  public static function instantiated($var = null, $exact = false) {
    if (!($var instanceOf static) || !$var->exists || !$var->getKey()) return null;
    //Debugging
    $varcl = get_class($var);
    $statcl = static::class;
    //pkdebug("var class: [$varcl]; static class: [$statcl]");
    if ($exact && ($varcl !== $statcl)) {
      return null;
    }
    return $var;
  }

  public function real() {
    return static::instantiated($this);
  }

  /**
   * Checks to see if the $arg is instantiated and the same instance of this obj.
   * Ridiculous this is not built in...
   *** NOTE! As of Laravel 5.3, it finally is - but we want to return the model instance
   * @param any $var
   * $return boolean|static - false if not instantiated or not the same object, else the object
   */
  public function is($var) {
    if (!$var || !($var instanceOf Model)) return false;
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
    return $this->attributes + ['MODEL CLASS'=>static::class];
  }

  public function jsonDebug($args=[]) {
    return json_encode($this->__debuginfo(), static::$jsonopts);
  }

  /** The authXXX functions determine if the user is allowed to perform XXX
   * on the current Model instance. TO BE OVERRIDDEN in extended classes, but
   * some safe defaults are provided.
   * @return boolean
   */
  //public function authDelete(Model $user = null) {
  public function authDelete() {
    if (isCli()) return true;
    //return $this->authUpdate($user);
    return $this->authUpdate();
  }

  //public function authRead(Model $user = null) {
  public function authRead() {
    if (isCli()) return true;
    //return $this->authUpdate($user);
    return $this->authUpdate();
  }

  /** The extended model should over-ride this */
  //public function authUpdate(Model $user = null) {
  public function authUpdate() {
    if (isCli()) return true;
    return true;
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
  //public static function authCreate(PkModel $parent = null, Model $user = null) {
  public static function authCreate(PkModel $parent = null) {
    if (isCli()) return true;
    if ($parent && ($parent instanceOf PkModel)) {
      //return $parent->authUpdate($user);
      return $parent->authUpdate();
    }
    return true;
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
   * @param boolean $andPropertyNames: false - if true, also return property names
   *   defined on this class, NOT part of persisted / DB Model attributes
   * @param type $Model
   */
  public function getAttributeNames($andPropertyNames = false) {
    $attNames = array_keys($this->getAttributeDefs());
    if ($andPropertyNames) {
      $attNames = array_merge($attNames, array_keys(get_class_vars(static::class)));
    }
    return $attNames;
  }

  /** For generating backup SQL files for the model */
  public static function sqlToBuildRelationTables($self = true) {
    $relClasses = static::getRelationClasses();
    if ($self) {
      $relClasses[]=static::class;
    }
    $createSqlArr = [];
    foreach ($relClasses as $relClass) {
      $createSqlArr[] = $relClass::sqlToBuildTable();
    }
    $sqlStr = implode("\n",$createSqlArr);
    return $sqlStr;
  }

  public static function getRelationClasses($relClasses = []) {
    //static $relationClasses = [];
    foreach (static::getLoadRelations() as $load_relation) {
      if (!in_array($load_relation, $relClasses)) {
        $relClasses = array_merge($relClasses,[$load_relation],$load_relation::getRelationClasses($relClasses));
      }
    }
    $uv = array_unique($relClasses);
    return $uv;
  }

  public static function getAttributeCollectionNames() {
    return array_keys(static::getLoadRelations());
  }

  public static function getRelationalClass($relclass) {
    if (in_array($relclass, static::getRelationalClasses(),1)) {
      return $relclass;
    }
    return null;
  }
  /**
   * For generating backup SQL files of the current instance.
   *  Gets the inserts for the current instance, then recursively for all its
   * one-to-many relationship inserts. Have to be clever to avoid cycles
   */
  public function getSqlInserts() {
    static $tablesAndKeys = []; #['table1'=>[$key1,$key2,$key3],'table2'=>[...]
    $tableName = $this->getTable();
    $keys = keyVal($tableName,$tablesAndKeys,[]);
    $key=$this->getKey();
    if (in_array($key, $keys)) {
      pkdebug("Leaving: Table: [$tableName], Key: [$key], tablesAndKeys:", $tablesAndKeys);
      return; #Hope that takes care of cycles
    }

    $sqlInsStr = $this->sqlInsertAttributes();
    $relations = array_keys(static::getLoadRelations());
    foreach ($relations as $relation) {
      foreach ($this->$relation as $item) {
        $sqlInsStr.=$item->getSqlInserts();
      }
    }
    $tablesAndKeys[$tableName][]=$key;
    return $sqlInsStr;
  }

  public function newCollection(array $models =[]) {
    return new PkCollection($models);
  }

  /** 
   * Return all the tables w. Primary Key name & values, 'owned' by this Model
   * @staticvar array $tablesAndKeys
   * @return array: ['table'=>['key_name'=>'id','keys'=>[3,4,6,12]],...
   */
  public function getTablesAndKeys($tablesAndKeys=[]) {
    //static $tablesAndKeys = []; #['table1'=>[$key1,$key2,$key3],'table2'=>[...]
    $tableName = $this->getTable();
    $keydata = keyVal($tableName,$tablesAndKeys,[]);
    $keys=keyVal('keys',$keydata,[]);
    $keyName = $this->getKeyName();
    $key=$this->getKey();
    if (in_array($key, $keys)) {
      pkdebug("Leaving: Table: [$tableName], Key: [$key], tablesAndKeys:", $tablesAndKeys);
      return $tablesAndKeys; #Hope that takes care of cycles
    }
    $relations = array_keys(static::getLoadRelations());
    foreach ($relations as $relation) {
      foreach ($this->$relation as $item) {
        $tablesAndKeys = $item->getTablesAndKeys($tablesAndKeys);
        //pkdebug("TKS:", $tks);
      }
    }
    $tablesAndKeys[$tableName]['keys'][]=$key;
    $tablesAndKeys[$tableName]['key_name']=$keyName;
    //pkdebug("Leaving TBL: [$tableName]; keyName: [$keyName], key: [$key], keys:",$keys," tablesAndKeys: ",$tablesAndKeys);
    return $tablesAndKeys;

  }

  /** Generates SQL string to back up object data & its relations. Reasonable
   * general method, but to be accurate subclasses have to customize it.
   */
  public function sqlObjectData() {
    $sqlCreateTbls = $this->sqlToBuildRelationTables();
    $sqlInsAtts = $this->getSqlInserts();
    return "$sqlCreateTbls\n\n$sqlInsAtts";
  }

  /** For generating backup SQL files */
  public function sqlInsertAttributes() {
    $tableName = $this->getTable();
    $myAttributes = $this->getAttributes();
    $setArr = [];
    foreach ($myAttributes as $fieldName => $value) {
      $escvalue = esc_sql($value);
      //$setArr[] = "`$fieldName`=$escvalue  RAWVAL: [$value]  TYPE:  $vtype\n" ;
      $setArr[] = "`$fieldName`=$escvalue" ;
    }
    $setStr = implode(", ",$setArr);
    return "INSERT INTO `$tableName` SET $setStr;\n\n";
    

  }
  

  /** Minimal, to build backup SQL files
   * 
   */
  public static function sqlToBuildTable() {
    $tableName = static::getTableName();
    $rawcols = DB::select(DB::raw("SHOW COLUMNS FROM `$tableName`"));
    $colDefArr = [];
    foreach ($rawcols as $rawcol) {
      $colDefArr[] = static::sqlToBuildCol($rawcol);
    }
    $colDefStr = implode(",\n",$colDefArr);
    $createTable=" CREATE TABLE IF NOT EXISTS `$tableName` (\n$colDefStr\n); ";
    return $createTable;
  }

  /** Uses output of the Raw SHOW COLUMNS FROM query above. 
   * to build 
   * @param stdClass $colDef:
   *   ->Field: field name
   *   ->Type
   *   ->Null: NO/YES
   *   ->Key: null/UNI/PRI
   *   ->Default
   *   ->Extra: null/autoincrement/
   *   
   */
  public static function sqlToBuildCol($colDef) {
    if ($colDef->Null === 'NO') $nullable = ' NOT NULL ';
    else $nullable = ' NULL ';
    //return "      `{$colDef->Field}` {$colDef->Type} $nullable {$colDef->Extra} "; 
    return "      `{$colDef->Field}` {$colDef->Type} $nullable "; 
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
    unset ($attributes['id']);
    parent::__construct($attributes);
    $this->RunExtraConstructors($attributes);
  }

  /** Calls all special trait constructor methods, which start with
   * ExtraConstructor[TraitName]
   * @param type $attributes
   */
  public function RunExtraConstructors($attributes) {
    #Too many methods, most models don't use traits. so,
    #Assume only from Traits
    $traitmethods = static::getTraitMethods();
    $fnpre = 'ExtraConstructor';
    //$methods = get_class_methods(static::class);
    $constructors = [];
    foreach ($traitmethods as $traitmethod) {
      if (startsWith($traitmethod, $fnpre, false)) {
        $constructors[] = $traitmethod;
      }
    }
    pkdebug("Constructors? ", $constructors, "Methods:", $traitmethods, "This Class:", get_class($this), "Traits? ", static::getAllTraits());
    foreach ($constructors as $constructor) {
      $this->$constructor($attributes);
    }
    return $attributes;
  }




  /**
   * For attributes that have a "display value" _DV extension, to transform the
   * raw field value into a display value. 
   * @param string $key - the attribute
   * @return string - the transformed value to display
   */
  public function __get($key) {
    # This seems pretty obvious - why doesn't Eloquent do it?
    if (!$key) return null;

    $name = removeEndStr($key, static::$displayValueSuffix);
    if ($name && in_array($name,static::getDisplayValueFields(),1)) {
      return $this->displayValue($name);
    }
    if (in_array($key,static::getMethodsAsAttributeNames(),true)) {
      return $this->$key();
    }
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
    #First see if it's a dynamic method in this class...

    if (static::getDynamicMethod($method)) {
      return $this->callDynamicMethod($method, $args);
    }
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

  /** Default, just returns the static setting.  */
  public static function getLoadRelations() {
    $classLoadRelations = static::getAncestorArraysMerged('load_relations');
    $traitLoadRelations = static::getAncestorArraysMerged('_load_relations');
    return array_merge($classLoadRelations, $traitLoadRelations);
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
   * @param boolean $cascade Default: true - does cascading deletes based on Load Relations,
   * 
   */
  ## RE-ENABLE WHEN EVERYTHING ELSE WORKING - 
  ## Need to think through deleting both from here, AND from the "Save Relations"
  ## method below
  public function delete($cascade = true) {
    if (!$this->authDelete()) {
      pkdebug("Can't delete:", $this);
      throw new Exception("Not authorized to delete this object");
    }
    pkdebug("In PkModel, trying to delete an instance of ".get_class($this));
    if (!$cascade) return parent::delete();
    foreach (array_keys($this->getLoadRelations()) as $relationSet) {
      if (is_array($this->$relationSet) || $this->$relationSet instanceOf BaseCollection) {
        foreach ($this->$relationSet as $relationInstance) {
          if ($relationInstance instanceOf Model) {
            $relationInstance->delete($cascade);
          }
        }
      }
    }
    pkdebug("Got this far deleting load relations. Really  risk many to many?");
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
          pkdebug("Couldn't find rel [$relname], Can't delete:", $this);
          throw new Exception("Neither a valid pivot class nor tabe was defined for [$relname]");
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
    $myClass = static::class;
    $myId = $this->id;
    pkdebug("Finally about to delete myself - the tricky part. Im a instance of $myClass, ID $myId");
    $res =  parent::delete();
    pkdebug("Result was:", $res, "but do I really exist? Let's try to find me;");
    $me = $myClass::find($myId);
    if (!$myClass::instantiated($me)) pkdebug("No, I seem not be instantiated anymore");
    else pkdebug("Whoops, I have an instance - what's my ID? {$me->id}");
    return $res;
    //return parent::delete();
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
   * Save direct attributes and 1-many relations; typically from Controller POSTs
   * Creates/Updates/Deletes on the "many" side.
   * works for direct attributes and one-to-many relationships, single level.
   * DEPENDS on defining <tt>$this->getLoadRelations()</tt> using default
   * foreign key name for this model
   * <p>
   * Saves the argument array (typically from a Form input) to the Model/DB,
   * supporting 1-to-many relationships. 
   * <p>
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
    $relations = $this->getLoadRelations();
    $foreignKey = $this->getForeignKey();
    $this->fillFillables($arr);
    $this->save();
    $modelId = $this->id;
    $this->saveM2MRelations($arr);
    foreach ($relations as $relationName => $relationModel) {
      if (!array_key_exists($relationName, $arr)) continue;
      $tstInstance = new $relationModel();
      $keys = [];
      $keyName = $tstInstance->getKeyName();
      $relarr = $arr[$relationName];
      if (is_array($relarr)) {
          foreach ($relarr as $relrow) {
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
            //pkdebug("A new relationship: RelRow:", $relrow);
            $relInstance = new $relationModel();
            if (!array_key_exists($foreignKey, $relrow)) {
              $relrow[$foreignKey] = $modelId;
            }
            #Again, compensate for empty string POSTED instead of NULL
            if (!array_key_exists($keyName, $relrow)) {
              pkdebug("keyName [$keyName]; Relrow:", $relrow);
            }
            if (!$relrow[$keyName]) $relrow[$keyName]=null;
            //if (!keyVal($keyName,$relrow)) $relrow[$keyName]=null;
          }
          $relInstance->saveRelations($relrow);
          $keys[] = $relInstance->getKey();
        }
    }
      #Delete if id not in array AND foreign key = foreign key
      if (!sizeof($keys)) $this->$relationName()->delete();
      else $this->$relationName()->whereNotIn($keyName, $keys)->delete();
    }
    return true;
  }

  /** Very approximate - extended models should override this */
  public function owns($item) {
    if (!$item instanceOf PkModel) return false;
    if (!$this->id || !$item->id) return false;
    $ownerID = Str::snake(static::basename()).'_id';
    if ($this->id != $item->$ownerID) return false;
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
        $newarr = [];
        foreach ($arr as $el) {
          $newarr[] = "$el";
        }
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
   * // Example:
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
  public function displayValue($fieldName) {
    if (!ne_string($fieldName)) return null;
    $refmaps = static::getDisplayValueFields(true);
    #Example: 'claim_submitted' => ['\\PkExtensions\\DisplayValue\\DateFormat', 'M j, Y', 'No'],
    $rc = $refmaps[$fieldName];
    #$rc can be a String classname that implements PkDisplayValueInterface or a closure/runnable, OR
    #an array of [$className,$arg1, $arg2,..] where $args = date format, or $ precision,
    # default value, whatever
    $args = [];
    if (is_array($rc)) { #First el is method, remainder are optional args
      $dv_method = array_shift($rc);
      $args = $rc;
    } else {
      $dv_method = $rc;
    }
    $fldVal = $this->$fieldName;
    if (is_callable($dv_method)) {
        array_unshift($args, $fldVal);
        return call_user_func_array($dv_method,$args);
    } else if ( ne_string($dv_method) && class_exists($dv_method) &&
        method_exists($dv_method,'displayValue')) {
        array_unshift($args, $fldVal);
        return call_user_func_array([$dv_method,'displayValue'],$args);
    }
    return $fldVal;
  }

  /*** Delete if the above works */
  public function displayValue_old($fieldName, $value = null) {
    if (!ne_string($fieldName)) return null;
    $refmaps = static::getDisplayValueFields(true);
    #Example: 'claim_submitted' => ['\\PkExtensions\\DisplayValue\\DateFormat', 'M j, Y', 'No'],
    foreach ($refmaps as $fn => $rc) {
      #$rc can be a String classname that implements PkDisplayValueInterface, OR
      #an array of [$className,$arg1, $arg2,..] where $args = date format, or $ precision,
      # default value, whatever
      $args = [];
      if (is_array($rc)) { #First el is method, remainder are optional args
        $dv_method = array_shift($rc);
        $args = $rc;
        /*
        if (is_array($rc) && count($rc)) {
          $args = $rc;
        }
        $arg1 = $rc[1];
        $rc = $rc[0];
         * 
         */
      } else {
        $dv_method = $rc;
      }
      if (!ne_string($fn) || !ne_string($dv_method) || !class_exists($dv_method) || 
          !in_array('PkExtensions\PkDisplayValueInterface', class_implements($dv_method))) {
        continue;
      }
      if ($fn === $fieldName) {
        $fldVal = $this->$fieldName;
        array_unshift($args, $this->$fieldName);
        return call_user_func_array([$dv_method,'displayValue'],$args);
        //return $rc::displayValue($this->$fieldName,$args);
        //return $rc::displayValue($this->$fieldName,$args);
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
 * 
 * 8/2017 Enhancement - As originally designed, this would allow Model methods
 * to execute, return a value, then have the the result formatted by the DV class.
 * 
 * BUT Now I want to enhance it by allowing model ATTRIBUTES to take "_DV(arg)"
 * to send the arg to the DV function.... - so $possessionName could be an attriubute
 * name that passes it's value & optional arguments to a callable....
 */
  public function displayValueMethod($possessionName, $args=[]) {
    if (!ne_string($possessionName)) return null;
    //$class=get_class($this);
    if (in_array($possessionName, $this->getAttributeNames(1), 1)) {
      $fldVal = $this->$possessionName;
    } else {
      $fldVal = call_user_func_array([$this,$possessionName],$args);
      $args = [];
    }
    $rc = static::getDisplayValueFields(true)[$possessionName];
    if (is_array($rc)) { #First el is callable, remainder are optional args
      $dv_method = array_shift($rc);
    } else {
      $dv_method = $rc;
      $rc = [];
    }
    $mergeargs = array_merge($args, $rc);
    array_unshift($mergeargs, $fldVal);
    if (is_callable($dv_method)) {
        return call_user_func_array($dv_method,$mergeargs);
    } else if ( ne_string($dv_method) && class_exists($dv_method) &&
        method_exists($dv_method,'displayValue')) {
        return call_user_func_array([$dv_method,'displayValue'],$mergeargs);
    }
    return $fldVal;
  }



  /*** Delete if the above works */
  public function displayValueMethod_old($methodName, $args=[]) {
    if (!ne_string($methodName)) return null;
    //$class=get_class($this);
    $attNms = $this->getAttributeNames(1); #Could call w. True to allow object properties as well?
    $refmaps = static::getDisplayValueFields(true);
    foreach ($refmaps as $fn => $rc) {
      if (!ne_string($fn) || !ne_string($rc) || !class_exists($rc) || 
          !in_array('PkExtensions\PkDisplayValueInterface', class_implements($rc))) {
        continue;
      }
      if ($fn === $methodName) {
        if (in_array($fn, $attNms, 1)) {#args are for the DisplayValue method!
          if (!is_array($args)) {
            throw new PkException(["Thought args HAD to be an array, but are:", $args]);
          }
          array_unshift($args, $this->$fn);
          return call_user_func_array([$rc,'displayValue'], $args);
        } else {
          $value = call_user_func_array([$this,$methodName], $args);
          return $rc::displayValue($value);
        }
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
    if ($this->useBuildFillableOptions) {
      foreach ($this->fillableOptions as $field => $value) {
        if (is_array($value)) {
          $allowedVals = array_keys($value);
          if (!in_array($this->$field, $allowedVals)) unset($this->$field);
        }
      }
    }
    if ($this->emptyStringToNull) $this->convertEmptyStringToNullForNumerics();
    if ($this->cleanAllText) $this->hpureAllText();

    #Clean dangerous HTML from specified fields
    foreach (static::$escape_fields as $field) {
      if ($this->$field) $this->$field = hpure($this->$field);
    }
    if (!$this->authUpdate())
        throw new Exception("Not authorized to update this record");
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
   * Put another way, postSave() will also be called on create, so you can put it all in there...
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

  /** Accepts a closure, binds to it, executes & returns it with the (optional) args
   * Typically used for custom HTML rendering, but whatever
   * @param Closure $closure
   * @param mixed $args
   * @return mixed
   */
  public function callClosure(Closure $closure, $args=[]) {
    return $closure->call($this, $args);
  }

  /**
   * @param array $options - comes from "save" options, in case we want to add any
   */
  public function postCreate(Array $options = []) {
  }

  public function hpureAllText() {
    $attributeDefs = $this->getAttributeDefs();
    foreach ($attributeDefs as $name => $type) {
      if (in_array(strtolower($type), static::$mySqlStrTypes)) {
        if (in_array($name,$this->trustedTextFields)) {
          continue;
        }
        $this->$name = hpure($this->$name);
      }
    }
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

  /** To allow override in descendent classes - like get calculated 
   * attributes, or one to many relationship objects, etc
   * @param array $arg - array of full model classes - if this class is in 
   * this array, then just return regular getAttributes, otherwise relationship
   * atts, etc. 
   * @return array of model attributes
   */
  public function getCustomAttributes($arg=null) {
    /*
    if (is_string($arg)) {
      $thisclass = get_class($this);
      pkdebug("Arg: [$arg]; this class: [$thisclass]");
    }
     * 
     */
    if ( (is_string($arg) && is_a($this,$arg))
        ||(is_array($arg) && in_array(static::class, $arg))) {
      //pkdebug("MATCHED! this class: [$thisclass], ARG:", $arg);
      return $this->getAttributes();
    }
    $myAtts = $this->getAttributes();
    $relationAtts = $this->getRelationshipAttributes();
    $methodAtts = $this->getMethodAttributes($arg);
    $dvAtts = $this->getDisplayValueAttributes();
    $extraAtts = $this->getExtraAtts();
    return array_merge(['model'=>get_class($this)],$dvAtts, $methodAtts, $relationAtts, $myAtts, $extraAtts);
  }

  /** Does nothing, but derived classes might want to add extra details, like
   * a real user name from a user_id
   * @return array
   */
  public function getExtraAtts() {
    return [];
  }

  /**
   * Return array of customAttributes for all matching instances
   * @param array $params - typically an array of id's or instances
   * @param array $arg - to be passed to instance getCustomAttributes($arg)
   */
  public static function getCustomAttributesIn( $params=[], $arg=null) {
    if (!$params || (is_arrayish($params) && !count($params))) {
      return [];
    }
    $resAtts = [];
    if (is_arrayish_indexed($params)) { #Then an array of instances or ID's
      if (is_intish($params[0])) { #Assume IDs of instances
        $resInsts = Static::find($params);
      } else if ($params[0] instanceOf static) {
        $resInsts = $params;
      } else {
        throw new PkException(["Illegal params to getCustomAttributesIn:",$params]);
      }
      foreach ($resInsts as $inst) {
        $resAtts[]= $inst->getCustomAttributes($arg);
      }
      return $resAtts;
    }
    throw new PkException(["Unhandled params to getCustomAttributesIn:",$params]);
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

  /**
   * HTML & JSON Encode this model attributes for inclusion in an HTML data-XXX
   * attribute, so we can use JS to display on hover or whatever.
   * @param type $args
   * @return type
   */
  public function getEncodedCustomAttributes($args=null) {
    $ca = $this->getCustomAttributes($args);
    $jsenc = json_encode($ca, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    $htenc = html_encode($jsenc);
    //$htenc = static::encodeData($ca);
    return $htenc;
  }

  /** Probably useless method, but a subclass might want to do something with it.
   * More useful in the controllers */
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
    $instances = static::all()->all();
    $numinst = count($instances);
    //pkdebug("For Class: ".static::class."; NumInst: $numinst");
    if (!class_exists('PkExtensions\PkTestGenerator',1)) {
      throw new PkException("Huh? PkTestGenerator can't be found?");
    }

    if (!$numinst || !class_exists('PkExtensions\PkTestGenerator',1)) {
      if ($num === -1) return null;
      return [];
    }
    return \PkExtensions\PkTestGenerator::randData($instances, $num);
  }


  #################  Testing PkTypedUploadModel - so a PkModel can have many
  ###########  individual or collections of various types of file upload objects
  ############ but all of a single class, in a single table

  /****  Change to using Typed as trait  **/
  /*
  public function hasManyTyped($attname, $typedClass=null) {
    if (!$typedClass) {
      $typedClass = PkTypedUploadModel::class;
    }
    return $this->hasMany($typedClass,'owner_id')
      ->where('owner_model',static::class)
      ->where('att_name',$attname);
    }

  public function hasOneTyped($attname, $typedClass=null) {
    if (!$typedClass) {
      $typedClass = PkTypedUploadModel::class;
    }
    return $this->hasOne($typedClass,'owner_id')
      ->where('owner_model',static::class)
      ->where('att_name',$attname);
    }
   * 
   */
  /**
   * 
   * @param string $typedClass - full typed class name
   * @param array $filters - optional like ['att_name'=>$attname,....
   * @return builder
   */
  public function hasManyTyped($typedClass, $params=[]) {
    $builder =  $this->hasMany($typedClass);
    foreach ($params as $propname =>  $propval) {
      $builder->where($propname,$propval);
    }
    return $builder;
  }

  /** Don't think this will work, as hasOne will only return one, without
   * checking the params
   * @param string $typedClass
   * @param array $typeFilters
   * @return $builder
   */
  public function hasOneTyped($typedClass, $typeFilters=[]) {
    $builder = $this->hasMany($typedClass);
    foreach ($params as $propname =>  $propval) {
      $builder->where($propname,$propval);
    }
    return $builder->first();
  }


    /**
     * Creates & adds a typed (Owned) Instance from the data
     * @param array $filedata - Data to build the Typed instance - like,
     * @param array $typeFilters - Data to make the object a specific type
     *   ['att_name'=>$att_name,...
     * @param boolean $hasone - if true, delete existing instances
     * @param string $typedClass - the class to build
     */
  public function addTyped($typedClass, Array $typeFilters = [], Array $filedata=[],  $hasone=false) {
    $typeFilters[$this->getForeignKey()]=$this->getKey();
    if ($hasone) { #Delete all existing matches
      $builder = $typedClass::query();
      foreach ($typeFilters as $key=>$val) {
        $builder->where($key, $val);
      } #Now delete them all
      $builder->delete();
    }
    #Create a new instance

    $instance = new $typedClass(array_merge($typeFilters, $filedata));
    return $instance;
    //$filedata[$this->getForeignKey()]=$this->getKey();
    #Validate we have the $fileData we need before deleting or creating.
    //$current = $this->$attname;
    //$prefd = $filedata;
    //$filedata = $typedClass::canCreate($filedata);
    //if ($filedata === false) {
     // pkdebug("Data validation to create [$typedClass] failed for data:", $pref);
     // return false;
    //}
    /*
    if ($hasone) {
      $typedClass::where('att_name',$attname)->where('owner_id',$this->id)
          ->where('owner_model',static::class)->get()->delete();
    }
     */
    /*
    $prop = $typedClass::createUpload($filedata);
    if (! $typedClass::instantiated($prop)) {
      return false;
    }
    return $prop;
     * 
     */
  }

  public function setTyped($typedClass, Array $typeFilters = [], Array $filedata) {
    return $this->addTyped($typedClass, $typeFilters, $filedata,  1);
  }
  /*
  public function addTyped($attname, Array $filedata, $typedClass=null, $hasone=false) {
    if (!$typedClass) {
      $typedClass = PkTypedUploadModel::class;
    }
    #Validate we have the $fileData we need before deleting or creating.
    $filedata += [
        'owner'=>$this,
        //'owner_model'=>static::class,
        //'owner_id'=>$this->id,
        'att_name' => $attname,
        ];
    //pkdebug("Creating typed, fdata:",$filedata);
    //$current = $this->$attname;
    $prefd = $filedata;
    $filedata = $typedClass::canCreate($filedata);
    if ($filedata === false) {
     // pkdebug("Data validation to create [$typedClass] failed for data:", $pref);
      return false;
    }
    if ($hasone) {
      $typedClass::where('att_name',$attname)->where('owner_id',$this->id)
          ->where('owner_model',static::class)->get()->delete();
    }
    $prop = $typedClass::createUpload($filedata);
    if (! $typedClass::instantiated($prop)) {
      return false;
    }
    return $prop;
  }

  public function setTyped($attname, Array $filedata, $typedClass=null) {
    return $this->addTyped($attname, $filedata, $typedClass, 1);
  }
   * 
   */

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

  /** Allow models to determine who can delete them */
   function deletableBy($deleter) {
     if (($deleter instanceOf PkUser) && $deleter->isAdmin()) {
       return true;
     }
     if (($deleter instanceOf PkModel) && $deleter->owns($this)) {
       return true;
     }
     return false;
   }
  /** Return a route to delete this PkModel instance. 
   * 
   * @param assoc_array $args. Keys:
   *    deleteroute : the route name. Default: admin_deletemodel
   *    label - if present, return entire <a href...>$label</a>,
   *    just_href: if true, just the href/url w/o <a>
   *    class: CSS Classes: default: 'pkmvc-button inline'
   *    additional: passed to linkroute as attributes
   * else just the URL to delete
   */
  public function deleteRoute($args=[]) { // = 'admin_deletemodel', $link='Delete',$attributes=[]) {
    $defaultargs = ['deleteroute'=>'admin_deletemodel',
        'label'=>'Delete',
        'class'=>'pkmvc-button inline'];
    if (ne_string($args)) {
      $args = ['label'=>$args];
    } else if (!$args) {
      $args=[];
    }
    $args = array_merge($defaultargs, $args);
    $params = ['model'=>get_class($this), 'id'=>$this->id];
    $deleteroute = unsetret($args,'deleteroute');
    if (keyVal($args,'just_href')) {
      return route($deleteroute,$params);
    }
    $label = unsetret($args,'label');
     
  //public function deleteRoute($baseroute = 'admin_deletemodel', $link='Delete',$attributes=[]) {
    return PKHtml::linkRoute($deleteroute,$label,$params, $args);
  }

  /** Provides the properties to include in any HTML element that will triger
   * an AJAX delete - with supporting code in laravel-support.js
   * @param boolean $cascade - should the delete cascade to the many possesions
   */
  public function ajaxDeleteProps($cascade = true, $delroute = "ajax_delete") {
    $model = static::class;
    $id = $this->id;
    $url = route($delroute);
    $atts = " class='ajax-delete' data-model='$model' data-id='$id'
      data-url='$url' data-cascade='$cascade' ";
    return $atts;
  }

  /** When you want to use either a PkModel instance or just it's ID as
   * an argument to a method
   * @param \PkExtensions\Models\PkModel $var
   * @return type
   */
  public static function asid($var) {
    if(!$var) return false;
    if ($var instanceOf PkModel) {
      return $var->id;
    }
    return to_int($var);
  }
  
  /** The reverse of the above - takes an instance OR id, & returns the instance */
  public static function asmodel($var) {
    if (!$var) return false;
    if ($var instanceOf static) return $var;
    return static::find($var);
  }


  public static $whichfields = ['id'];
  /** For debugging - Simple string to identify the model/instance
   * 
   * @param array $fields - optional array of additional fields,
   * merged with class whichfields
   */
  public function which($fields=[]) {
    $myf = array_merge(static::getAncestorArraysMerged('whichfields',true),$fields);
    $out = "Class: ".get_class($this);
    //$out .= "; ID: ". $this->id;
    foreach ($myf as $field) {
      $out.="; $field: ".$this->$field;
    }
    return $out;
  }

  public static function getAllPkModelsTables() {
    $modeltotable=[];
    foreach ( config('app.buildmodels') as $model) {
      $modeltotable[$model]=$model::getTableName();
    }
    return $modeltotable;
  }

  public static function getAllChildModels() {
    $childModels = [];
    foreach (config('app.buildmodels') as $model) {
      if ($model::isChildModel()) {
        $childModels[] = $model;
      }
    }
    return $childModels;
  }

  /** Get all orphaned objects. 
   * 
   * @param boolean $instances - if true, return the instances as well,
   * otherwise just the model types & IDs
   * @return array - orphaned objects
   */
  public static function getAllOrphans($instances = false) {
    $orphans =[];
    foreach (static::getAllChildModels() as $model) {
      if ($morphans = $model::getOrphans($instances)) {
        $orphans[$model] = $morphans;
      }
    }
    return $orphans;
  }

  /** The generic 'getOrphans' - but override in special PkModels */
  public static function getOrphans($instances = false) {
     $parentStruct = static::parentStructure();
     //$pkModelTables = static::getAllPkModelsTables();
     $morphans = [];
     foreach (static::all() as $me) {
       $missings = [];
       foreach ($parentStruct as $parentRow) {
         $pmodel = $parentRow['model'];
         $key = $parentRow['key'];
         if (!$me->$key) {
           $missings[$key] = ['parentmodel'=>$pmodel, 'parentkey'=>'NULL'];
         } else if (!$pmodel::find($me->$key)) {
           $missings[$key] = ['parentmodel'=>$pmodel, 'parentkey' => $me->$key];
         }
       }
      if (!empty($missings)) {
        if ($instances) {
          $morphans[] = ['id'=>$me->id, 'orphan'=>$me, 'missings' => $missings];
        } else {
          $morphans[] = ['id'=>$me->id, 'missings' => $missings];
        }
      }
     }
     return $morphans;
  }


  /** The generic 'isChildModel' - but override in special PkModels */
  public static function isChildModel() {
    return static::parentStructure();
  }

  /** Default: Guesses parent / "owner" models by looking for all fields in 
   * this model ending in "XXX_id", and looking for parent tables called XXXs
   * returns false if none, or else parent struct array of ['model', 'table', 'key']
   */
  public static function parentStructure() {
    $pkModelTables = static::getAllPkModelsTables();
    $fields = static::getFieldNames();
    $parentStruct = [];
    foreach ($fields as $field) {
      $tst = removeEndStr($field, '_id').'s';
      foreach($pkModelTables as $pmodel => $ptable) {
        if ($tst === $ptable) {
          $parentStruct[] = ['model'=> $pmodel, 'table'=> $ptable, 'key'=>$field];
          continue;
        }
      }
    }
    return $parentStruct;
  }


  /** Gets instance (if ID), & checks if user is authed, & returns */
  public static function authInstance($instance) {
    if (is_scalar($instance)) {
      $instance = static::find($instance);
    }
    if (!$instance instanceOf PkModel) {
      throw new Exception("Bad arg for authIntance");
    }
    if (!$instance->authUpdate()) {
      throw new Exception ("Not authorized");
    }
    return $instance;
  }

//Represent this object as Javascript, for inclusion in templates!

  public function attsAsJson() {
    return json_encode($this->attributesToArray(),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ); 
  }



  //  This section for diagonosing the current DB
  /*
  public static function getAllPkModelsTablesFields() {
    $mtfs = []; $tables =[];
    //foreach (  Config::get('app.buildmodels') as $model) {
    foreach (  config('app.buildmodels') as $model) {
      $mtrow = ['model'=>$model, 'table'=>$model::getTableName(), 'fields' => $model::getFieldNames()];
      $mtfs[] = $mtrow;
      $tables[]=$mtrow['table'];
    }
    foreach ($mtfs as &$mtf) {
      $parents = [];
      foreach ($mtf['fields'] as $field) {
        $tst = removeEndStr($field, '_id').'s';
        if (in_array($tst,$tables,1)) {
          $row = $mtfs[array_search($tst,$tables)];
          $parents[]=['table'=>$tst,'model'=>$row['model']];
        }
      $mtf['parents']=$parents;
      }
    }
    pkdebug("Quick survey says:", $mtfs);
    return $mtfs;
  }

  public static function getAllOwnedModel() {
    $omdls = [];
    foreach (static::getAllPkModelsTablesFields() as $row) {
      if (count($row['parents'])) {
        $omdls[] = $row;
      }
    }
    return $omdls;
  }

  public static function getAllOrphaned() {
    $related = static::getAllOwned();
    $orphanModels = [];
    foreach ($related as $childType) {
      $childModel = $childType['model'];
      $parentModels = [];
      foreach ( $childModel::all() as $achild) {
        foreach ($childType['parents'] as $pr) {
          foreach ($childType['parents'] as $parentSet) {
            $pmodel = $parentSet['model'];
            $parentAtt = removeEndStr($parentSet['table'],'s').'_id';
            if (!$child->$parentAtt) $missing = [$pmodel=>'NULL'];
            else if (!$pmodel::find($child->$parentAtt)) $missing [$pmodel => $child->$parentAtt];
            if (!empty($missing)) 

            $parentModels[]
      $orphans = [];
      foreach ($childType[$parents] as $tm) {
        

*/


  /*
   * 
  public static function getTableName() {
      $currenttablefields = static::getStaticAttributeNames();
   */
  //Config::get('app.buildmodels');
}
