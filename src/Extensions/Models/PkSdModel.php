<?php
namespace Pk\Extensions\Models;
use Illuminate\Database\Eloquent\SoftDeletes;
/**
 * Description of PkSdModel - Extends PkModel to automatically include SoftDeletes
 */
class PkSdModel extends PkModel {
  use SoftDeletes;
  protected $dates = ['deleted_at'];
}
