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

use PkExtensions\BaseTransformer;
use Illuminate\Database\Eloquent\Builder;
use Schema;
use App\User;
use \Auth;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection as BaseCollection;
use \Request;
use \Exception;

abstract class PkModel extends Model {

  public $transformer;
  public static $timestamp = true;
  public static $onetimeMigrationFuncs = [
      'updated_at' => 'timestamps()'
  ];

  /** Actual derived classes will define the $table_fields array with keys that
   * correspond to table field names, and values that represent their definition.
   * @var array - keys are table field names, values are table field defs
   */
  public static $table_field_defs = null;

  public static function getFieldNames() {
    return array_keys(static::getTableFieldDefs());
  }

  public static function getTableFieldDefs() {
    return static::$table_field_defs;
  }

  public static function get_field_type($fieldname) {
    $fielddef = KeyVal($fieldname, static::getTableFieldDefs());
    if (!$fielddef) return false;
    if (is_string($fielddef)) return $fielddef;
    if (is_array($fielddef)) return keyval('type', $fielddef);
    return false;
  }

  /** Because the Eloquent Model class needs an instance to get a table name...
   *
   * @var null|string - name of the DB table 
   */

  /**
   * Get the table associated with the model.
   *
   * @return string
   */
  public static function getTableName() {
    $instance = new static();
    $tablename = $instance->getTable();
    return $tablename;
  }

  public static function buildMigrationFieldDefs($fielddefs = [], $change = false) {
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
        $type = $def['type'];
        $methods = keyval('methods', $def, []);
        $fielddef = "$spaces\$table->$type('$fieldName')";
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
        $out .= $fielddef . $methodChain . "$changestr;\n";
      }
    }
    return $out;
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
    foreach (static::$onetimeMigrationFuncs as $key => $func) {
      if (!in_array($key, $currenttablefields))
          $migrationFunctions .= "$spaces\$table->$func;\n";
    }
    /*
      foreach (static::$tableMigrations as $tableMigration) {
      $migrationFunctions .= "$spaces\$table->$tableMigration;\n";
      }
     */
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
  }

  public static $mySqlIntTypes = ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'];
  public static $mySqlNumericTypes = ['tinyint', 'smallint', 'mediumint', 'int',
      'bigint', 'decimal', 'float', 'double', 'real', 'bit', 'boolean', 'serial'];

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

  /**
   * Checks to see if the $arg is instantiated and the same instance of this obj.
   * Ridiculous this is not built in...
   * @param any $var
   * $return boolean|static - false if not instantiated or not the same object, else the object
   */
  public function is($var) {
    if (!static::instantiated($var) || !static::instantiated($this))
        return false;
    if (get_class($this) !== get_class($var)) return false;
    if ($this->getKey() !== $var->getKey()) return false;
    return $this;
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
    $name = removeEndStr($key, 'Tfrm');
    if ($name) return $this->transformer->$key;
    return parent::__get($key);
  }

  public function __call($method, $args = []) {
    $name = removeEndStr($method, 'Tfrm');
    if (!$name) return parent::__call($method, $args);
    return $this->transformer->__call($method, $args);
  }

  public function getTransformer($name = null) {
    if (!is_string($name)) return $this->transformer;
    else return keyval($name, $this->transformers);
  }

  public function setTransformerFromArray($info) {
    if (!$this->transformer instanceOf BaseTransformer) {
      $this->transformer = new BaseTransformer($this);
    }
    $this->transformer->setItem($this);
    pkdebug('info', $info);
    $this->transformer->addTransforms($info);
  }

  public function setTransformer($transinfo, $name = null) {
    if ($transinfo instanceOf BaseTransformer) {
      $transformer = $transinfo;
      $transformer->setItem($this);

      if (is_string($name)) $this->transformers[$name] = $transformer;
      else $this->transformer = $transformer;
    } else if (is_array($transinfo)) {
      #Could be an array of transformer instances, or actionsets for a new transformer
      reset($transinfo);
      $first = current($transinfo);
      pkdebug('FIRST', $first);
      if (is_array($first)) { #Array of actionset methods
        if (is_string($name)) {
          $transformer = keyval($name, $this->transformers, new BaseTransformer($this));
          $this->transformers[$name] = $transformer;
        } else {
          $transformer = $this->getTransformer();
        }
        $transformer->addTransforms($transinfo);
        if (!is_string($name)) $this->transformer = $transformer;
        else $this->transformers[$name] = $transformer;
      } else if ($first instanceOf BaseTransformer) { #assume an array of transformers
        foreach ($transinfo as $key => $value) {
          $value->setItem($this);
          if (is_string($key)) $this->transformers[$key] = $value;
        }
      }
    }
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
   * 
   * @return type
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
    pkdebug("In UpdateMod, DA:", $modelDataArray);
    $tstInstance = new static();
    $keyName = $tstInstance->getKeyName();
    pkdebug("In UpdateMod,kn: [$keyName] DA:", $modelDataArray);
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
    pkdebug("arrayKeys", $arrayKeys);
    foreach ($modelCollection as $model) {

      pkdebug("Model Atts:", $model->get());
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
    pkdebug("Saving Here Data:",$data);
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
          $otherobjkey =  $otherobj->getKey();
          $mycurrentotherobjkeys[] = "$otherobjkey";
        }
        #Great - we have a list of otherobj keys our model pointed to, we have a new 
        #submitted list of other obj keys - let's go!
        #But gotta clean up the keys in case some are 3 & some are '3'!
        #Just make them all strings?
        pkdebug("Array is:", $arr);
        $newarr = [];
        foreach ($arr as $el) $newarr[] = "$el";
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
            pkdebug("Adding",$fresh);
            $pivotmodel::create($fresh);
          }
        }
       
      } else if ( Schema::hasTable($pivottable)) { #Gotta try it with flat table
        if (!empty($deleteAll)) {
          DB::table($pivottable)->where($mykey, $thiskeyval)->delete();
        } else {
          DB::table($pivottable)->where($mykey, $thiskeyval)->whereIn($otherkey,$idsToDelete)->delete();
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
   * Should be overwritten in 
   * sublcasses. The default is to just display the value for the field, but
   * in subclasses, examine the field name, and if it represents a reference
   * (like, status_id), return the mapped status display text.
   * @param string $fieldName - the name of the DB field to examine
   * @return string - the user-friendly text to display
   */
  public function displayValue($fieldName, $value = null) {
    if (!$this->authRead()) return "Can't view this info";
    if ($value === null) return $this->$fieldName;
    return $value;
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

  public function convertEmptyStringToNullForNumerics() {
    $attributeDefs = $this->getAttributeDefs();
    foreach ($attributeDefs as $name => $type) {
      if (($this->$name === '') && in_array(strtolower($type), static::$mySqlNumericTypes)) {
        $this->$name = null;
      }
    }
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
