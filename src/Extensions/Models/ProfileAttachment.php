<?php
namespace App\Models;
use App\User;
use PkExtensions\Models\PkModel;
use PkExtensions\PkAttachmentModel;
class ProfileAttachment extends PkAttachmentModel {
  public static $table_field_defs = [
      'id' => 'increments',
      'user_id' => ['type'=>'integer','methods'=>'index'],
      'q_profile_id' => ['type'=>'integer','methods'=>'index'],
      'attachment' => ['type'=>'string','methods'=>'nullable'],
      'description' =>  ['type'=>'string','methods'=>'nullable'],
      'searchable' => ['type'=>'boolean', 'methods'=>['default'=>false]],
      ];
  public static $validUploadTypes =[
    'application/pdf',
    'image/gif',
    'image/png',
    'image/jpeg',
    'image/pjpeg',
    'text/plain',
  ];

  public static function authCreate(PkModel $parent = null, User $user = null) {
    if (isCli()) return true;
    if (!$user instanceOf User) $user = Auth::user();
    return true;
  }
  public function authUpdate(User $user = null) {
    if (isCli()) return true;
    if (!$user instanceOf User) $user = Auth::user();
    //if ($user->isAdm() || $user->isEpm() || $user->isPm()) return true;
    return true;
  }
  public function __construct($args = []) {
    $this->hasAttachedFile('attachment');
    parent::__construct($args);
  }
  protected $fillable = ['profile_id', 'document', 'description'];
  
  public function profile() {
    return $this->belongsTo('\App\Models\QProfile');
  }
}
