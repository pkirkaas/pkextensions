<?php
Namespace PkExtensions\Models;
use Auth;
use Exception;
use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;

#Comment

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of PkUser
 * 
 * @author Paul
 */

use Request;
class PkUser extends PkModel  implements AuthenticatableContract, AuthorizableContract, CanResetPasswordContract {
    use Authenticatable, Authorizable, CanResetPassword;
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /** 
     * Subclasses should implement this
     * @return boolean
     */
    public function isAdmin() {
      return false;
    }

    /** Special handling to reset passwords in a form, then calls parent method
     */
    public function saveRelations(Array $arr = []) {
      if (!$this->authUpdate()) throw new Exception("Not authorized to update this object");
      ## Check for password reset 
      if (isset($arr['new_password'])) {
        $new_password = $arr['new_password'];
        $confirm_password = keyVal('confirm_password', $arr);
        if ($new_password !== $confirm_password) {
          $redirback = redirect()->back()->withInput()->with('error_dialog',"Passwords didn't match");
          pkdebug("Type of redirback: " , typeOf($redirback));
          return $redirback;
        }
        $arr['password'] = $new_password;
      }
      return parent::saveRelations($arr);
    }

}
