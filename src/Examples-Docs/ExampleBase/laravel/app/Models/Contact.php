<?php
namespace App\Models;
use PkExtensions\Models\PkModel;
class Contact extends PkModel {

 public static $table_field_defs = [
  'user_id'  =>['type'=>'integer','methods'=>'nullable'],
  'subject'=>['type'=>'string','methods'=>'nullable'],
  'email'=>['type'=>'string','methods'=>'nullable'],
  'name'=>['type'=>'string','methods'=>'nullable'],
  'telno'=>['type'=>'string','methods'=>'nullable'],
  'companyname'=>['type'=>'string','methods'=>'nullable'],
  'msg'=>'text',
   ];


  public function user() {
    return $this->hasOne('App\Models\User');
  }
}
