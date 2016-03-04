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

    /** Does a lot - returns the current logged in user if they can edit the
     * given user - but if given user is null, also sets to the current logged 
     * in user, and if the current logged in user isAdmin, keeps user but returns
     * the current logged in - else if not logged in, or different from user, returns false.
     * @param User $user|null - set to current logged in user if emptyl
     * @return - the current logged in user, or false if not logged in or can't edit user
     * 
     */
    public static function canEdit(&$user) {
      $me = Auth::user();
      if (!static::instantiated($me)) return false;
      if (!static::instantiated($user)) $user = $me;
      if (!$me->isAdmin() || !$me->is($user)) return false;
      return $me;
    }


}
