<?php
Namespace PkExtensions\Traits;
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
use PkExtensions\PkException;
use Request;
use Hash;

Trait PkUserTrait {
    use Authenticatable, Authorizable, CanResetPassword, Notifiable;
    public static $onetimeMigrationFuncs = [
      'remember_token' => 'rememberToken()',
      ];
    protected $fillable = [
        'name', 'email', 'password',
    ];
    public $isResetPassword = false;

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

  /**
   * Fields that if empty ('' or 0) should be converted to NULL before saving.
   * Mainly for unique indices, which accept multiple nulls but not multiple ''
   * @var array of field names
   */
  public static $emptyToNull=['name','email'];
  public static $table_field_defs = [
      'name' => ['type'=>'string', 'methods'=>['nullable','unique']],
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

  public static $attstofuncs=[
      'full_name',
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

  public function full_name() {
    return $this->getName();
  }

  /** Same with full_name */

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

  public function pwdMatch($pwd) {
    $pwd = trim($pwd);
    if ($this->password === $pwd) {
      return true;
    }
    return $this->password === static::hashPassword(trim($pwd));
  }

  /** Checks password then hashes & sets. If $password2 === false, it's a
   * system reset of password. Else, user reset, & password must === password2
   * and exist and meet any validation reqs.
   * @param type $password
   * @param type $password2
   */
  public function resetPassword($password, $password2=false) {
    $password = trim($password);
    if (!$password || !ne_string($password)) {
      return false;
    }
    if ($password2 !== false) {
      $password2 = trim ($password2);
      if ($password2 !== $password) {
        return false;
      }
    }
    if ($this->passwordMeetsRequirements($password)) {
      $this->password = static::hashPassword($password);
      $this->isResetPassword = true;
      return true;
    } else {
      return false;
    }
  }

  /** Overridden in implementing classes - this just makes sure it's a
   * non-empty string
   * @param string $password
   */
  public function passwordMeetsRequirements($password) {
    return ne_string($password);
  }

  public function save(array $args = []) {
    $iniatts = $this->getAttributes();
    /*
    if (!$this->isResetPassword) {
      unset($this->password);
    }
     * 
     */
    if (!$this->email) {
      unset($this->email);
    }
    if (!$this->name) {
      unset($this->name);
    }
    $lastatts = $this->getAttributes();
    //pkdebug("Saving user - start w. ",$iniatts,"end with ",$lastatts);
    return parent::save($args);
  }

    
  public function isLoggedIn() {
    return $this->is(Auth::user());
  }
  /** Have to overrisde for polymormphic types */
  public function delete($cascade = true) {
    //pkdebug("About to try to delete myself & my polys");
    $loggedin = $this->isLoggedIn();
    $res = parent::delete($cascade);
    //pkdebug("Wow, the general result of user delete wasl ",$res);
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
      //pkdebug("User Type:".typeOf($user).'; ID: '. $user->id);
      if (!static::instantiated($user)) {
        $user = Auth::user();
        pkdebug("Not Instantiated? Get Auth: User Type:".typeOf($user).'; ID: '. $user->id);
      }
      $me = Auth::user();
      //pkdebug("Me Type:".typeOf($me).'; ID: '. $me->id);
      if ($me != $user && !$me->isAdmin()) return  false;
      //pkdebug("About to return User");
      return $user;
    }

    /** Allow a user to log in, with either name or email, & 
     * $password
     * @param string|array $ident - if string, look for users with
     * either that name or that email. If array, assoc,
     * ['ident'=>$ident,'password'=>$password]
     * @param string|null $password
     * @return - either logged in user, or false
     * TODO: Disallow emails as user names, require emails
     * to be valid - so we don't risk dups
     */

    /** Default identifying fields, subclasses can change -
     *  like phone number
     * @var array 
     */
    public static $idents = ['name','email'];
    public static function tryLogin($ident=null, $password=null, $remember=null) {
      if (!$ident) {
        $ident = Request::all();
      }
      //pkdebug("Ident:", $ident);
      if (is_array($ident)) {
        $password = keyVal('password',$ident);
        $remember = keyVal('remember',$ident);
        $ident = keyVal('ident',$ident);
      }
      if (! (ne_string($ident) && ne_string($password))) {
        return false;
      }
      foreach (static::$idents as $field) {
        $try = static::where($field,$ident)->first();
        if (!$try instanceOf static) {
          continue;
        }
        if ($try->pwdMatch($password)) {
          return $try->login($remember); 
        } else {
          return false;
        }
      }
      return false;
    }

    /** Log in the user - remember if remember - true */
    public function login($remember = false) {
      //pkdebug("Logging in...");
      Auth::login($this,$remember);
      $me = Auth::user();
      if (!$me->logins) {
        $me->logins = 1;
      } else {
        $me->logins++;
      }
      $me->lastlogin = unixtimeToSql();
      $me->save();
      return $this;
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

    /** Returns the ID of the authenticated user, else false */
    public static function uid() {
      $user = Auth::user();
      if ($user instanceof static) {
        return $user->id;
      }
      return false;
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

