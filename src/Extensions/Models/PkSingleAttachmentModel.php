<?php
/**
 * August, 2015, Paul Kirkaas, paul.kirkaas@disney.com - initially for the DLR Project.
 * This is the special case of a single file attached to this model, called 'attachment'.
 * This makes a lot of things more generalizable, including table generation.
 */

#####
namespace PkExtensions\Models;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
abstract class PkSingleAttachmentModel extends PkAttachmentModel {
  public static $attachment_fields = [
       'attachment_file_name' => ['type'=>'string', 'methods'=>'nullable'],
        'attachment_file_size' => ['type'=>'integer', 'methods'=>'nullable'],
        'attachment_content_type' => ['type'=>'string', 'methods'=>'nullable'],
        'attachment_updated_at' => ['type'=>'timestamp', 'methods'=>'nullable'],
  ];

  public static function getAttachmentFields() {
    return static::$attachment_fields;
  }

  public static function getTableFieldDefs() {
    return array_merge(static::getAttachmentFields(), static::$table_field_defs());
  }

  public function __construct($args = []) {
    $this->hasAttachedFile('attachment');
    parent::__construct($args);
  }
  public function isValid() {
    if ($this->valid instanceOf static) {
      return $this;
    }
    $attachment = $this->attachment;
    if (is_object($attachment) &&
       $attachment->url() &&
        $attachment->path() &&
        $attachment->size() ) {
      $this->valid = $this;
      return $this;
    } 
    return null;
  }

  public $valid = null;

  public function __call($method, $args = []) {
    if ($this->isValid() && method_exists($this->attachment,$method)) {
      return call_user_func_array([$this->attachment,$method], $args);
    }
    pkdebug("Tried call an an invalid method [$method], or the att is invalid?");
    return call_user_func_array(['parent', $method], $args);

  }

  public static function deleteEmpty() {
    //return static::whereNull('attachment_file_size')->delete();

  }

  public static function create(Array $attributes =[]) {
    static::deleteEmpty();
    return parent::create($attributes);
  }






















}
