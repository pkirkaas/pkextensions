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
use Illuminate\Notifications\Notifiable;
use \Hash;
/*
 * Set up some reasonable defaults. 
 */

/**
 * Description of PkUser
 * 
 * @author Paul
 */

use Request;
class PkUser extends PkModel  
    implements AuthenticatableContract, AuthorizableContract,
        CanResetPasswordContract {
    use Authenticatable, Authorizable, CanResetPassword, Notifiable;
    public static $onetimeMigrationFuncs = [
      'remember_token' => 'rememberToken()',
      ];
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
  public static $table_field_defs = [
      'name' => ['type'=>'string', 'methods'=>'nullable'],
      'email' => ['type' => 'string', 'methods' => 'unique'],
      'password' =>  ['type'=>'string', 'methods'=>'nullable'],
      'active' =>   ['type'=>'integer', 'methods'=>'nullable'],
       'admin'=>['type'=>'boolean', 'methods'=>['default'=>'false']],
      'socialreg' =>  ['type'=>'string', 'methods'=>'nullable'],
      'logins' =>  ['type'=>'integer', 'methods'=>'nullable'],
      'lastlogin' =>  ['type'=>'datetime', 'methods'=>'nullable'],
      'provider' =>  ['type'=>'string', 'methods'=>'nullable'],
      'provider_id' =>  ['type'=>'string', 'methods'=>'nullable'],
      'access_token' =>  ['type'=>'string', 'methods'=>'nullable'],

    ];

  public static $allowUpdate = 0; #To allow user registration/update 

  /*
  public function setPasswordAttribute($password) { 
    return $this->attributes['password'] = Hash::make($password);
  }
   * 
   */

  /** Can be overridden - but basic try here */
  public function getName() {
    if ($this->name) return $this->name;
    return $this->email;
  }

  /** They keep messing with the process, so don't do anything by default.
   * Just make it available & try to keep up & use when necessary.
   * @param mixed $mixed - either a string or indexed array. 
   * @return string or array - if $mixed is a string, returns the default hash
   * of it as an encrypted password. If $mixed is an array, only if the array
   * has a key/value of password, will this method hash ONLY the value of the
   * password & return a copy of the array, with the password value hashed.
   */
  public static function hashPassword($mixed) {
    if (is_string($mixed)) {
      return Hash::make($mixed);
    } else if (is_array($mixed) && array_key_exists('password', $mixed)) {
      $mixed['password'] = Hash::make($mixed['password']);
    }
    return $mixed;
  }

    
  public function isLoggedIn() {
    return $this->is(Auth::user());
  }
  /** Have to overrisde for polymormphic types */
  public function delete($cascade = true) {
    pkdebug("About to try to delete myself & my polys");
    $loggedin = $this->isLoggedIn();
    $res = parent::delete($cascade);
    pkdebug("Wow, the general result of user delete wasl ",$res);
    if ($loggedin) {
      Auth::logout();
    }
    return $res;
  }
             //Auth::logout();

  public function authDelete() {
    return $this->authUpdate();
  }
  public function authUpdate() {
    if (isCli() || static::$allowUpdate) return true;
    if (!static::instantiated($this)) return true;
    $me = Auth::user();
    if (!$me instanceOf static) return false;
    if ($this->is($me) || $me->isAdmin()) return true;
    
    return false;
  }

  /** Profiles are for others viewing - but user accounts are just for users and Admins */
  public function authRead() {
    return $this->authUpdate();
  }
    /** 
     * Subclasses should implement this
     * @return boolean
     */

  public function isAdmin() {
    if ($this->admin) return true;
    return false;
  }

    /** Special handling to reset passwords in a form, then calls parent method
     */
  /*
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
      }
      return parent::saveRelations($arr);
    }
   * 
   */

    /** Makes sure only the logged in user OR Admin can use the $user object
     * 
     * @param \PkExtensions\Models\PkUser $user
     * @return boolean
     */
    public static function auth(PkUser $user = null) {
      pkdebug("User Type:".typeOf($user).'; ID: '. $user->id);
      if (!static::instantiated($user)) {
        $user = Auth::user();
        pkdebug("Not Instantiated? Get Auth: User Type:".typeOf($user).'; ID: '. $user->id);
      }
      $me = Auth::user();
      pkdebug("Me Type:".typeOf($me).'; ID: '. $me->id);
      if ($me != $user && !$me->isAdmin()) return  false;
      pkdebug("About to return User");
      return $user;
    }

    /** Log in the user - remember if remeber - true */
    public function login($remember = false) {
      Auth::login($this,$remember);
    }

    /** Verifies $user exists, or gets the logged in user, or throws
     * exception
     * @param User $user
     */
    public static function getUser($user) {
      if (!static::instantiated($user)) {
        $user = Auth::user();
      }
      if (!static::instantiated($user)) {
        throwerr("No valid user");
      }
      return $user;
    }

    /** Gets the logged in user, if none, returns
     *  false or throws exception
     * @param boolean $throw - Throw if not logged in? Default true
     * @return User|boolean
     * @throws PkException
     */
    public static function me($throw = true) {
      $me = Auth::user();
      if (!static::instantiated($me)) {
        if ($throw) {
          throw new PkException("Not Logged In");
        } else {
          return false;
        }
      }
      return $me;
    }




}
