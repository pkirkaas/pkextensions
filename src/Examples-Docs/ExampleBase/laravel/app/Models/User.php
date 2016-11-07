<?php

namespace App\Models;
use PkExtensions\Models\PkUser;

use \DB;
use App\Extensions\Models\PkModel;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use \Auth;

class User extends PkUser {

}
