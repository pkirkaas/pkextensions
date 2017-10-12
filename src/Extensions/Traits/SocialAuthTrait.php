<?php
namespace PkExtensions\Traits;
use App\Models\User;
use Laravel\Socialite\Two\User as Auth2User;
use Socialite;
/**
 * Just handling of FB, GITHUB, successes, failures, 
 * @author pkirkaas
 * The "User" model returned by Socialite has some Auth2 & other data - see:
 * C:\www\Laravels\LaravelSGG\laravel\vendor\laravel\socialite\src\AbstractUser.php
 * C:\www\Laravels\LaravelSGG\laravel\vendor\laravel\socialite\src\Two\User.php
 */
trait SocialAuthTrait {
  //Social Login - FB
    public function redirectToProvider() {
        return Socialite::driver('facebook')->redirect();
    }


    //What facebook returns after attemped login. 
    //Need to check lots here. Can be error, new user, existing user -
    //what about warning them if new - confirm they want to register as bouncer?
    public function handleProviderCallback() {
//      pkdebug("Got Here, data:", request()->all());

      $error = false;
//      try{
        session()->put('state', request()->input('state'));
        $fbuser = Socialite::driver('facebook')->user();
        if (!$fbuser instanceOf Auth2User) {
           $error="Sorry, we didn't get your user information back";
        } else if (!filter_var($fbuser->getEmail(), FILTER_VALIDATE_EMAIL)) {
          $error="We didn't get a valid email. You need an email with Facebook to login/register with Facebook on this site. Then try again.";
        }
        /*
         * This is the raw array:
         *     [token] => EAAcDsDZC6XkABAAJzdhxszlmQWob1HJHZAPuhcKfIt2gLlbIblXVP4lyYYM5v5mGt7Kzxx1HfDkNBaGY5ZCpZApTaad7rGkhMFn3fNKTHTQ8DSVWJnkMul2fKsV5RNZAYoQdHCCKEPUZCwZA7oGCdH7m1mBRyjKyXqyinLARiXQpAZDZD
    [refreshToken] => 
    [expiresIn] => 5183455
    [id] => 10212606807038579
    [nickname] => 
    [name] => Paul Kirkaas
    [email] => pkirkaas@gmail.com
    [avatar] => https://graph.facebook.com/v2.10/10212606807038579/picture?type=normal
    [user] => Array
        (
            [name] => Paul Kirkaas
            [email] => pkirkaas@gmail.com
            [gender] => male
            [verified] => 1
            [link] => https://www.facebook.com/app_scoped_user_id/10212606807038579/
            [id] => 10212606807038579
        )

    [avatar_original] => https://graph.facebook.com/v2.10/10212606807038579/picture?width=1920
    [profileUrl] => https://www.facebook.com/app_scoped_user_id/10212606807038579/
)
         */
 //     } catch (\Exception $e) {
  //      $error = "We had a problem communicating with Facebookx: ".$e->getMessage();
   //   }
      if ($error) {
        //return $this->error($error."\nPlease try registering or logging in manually");
        pkdebug("Got an error?", $error);
        return $this->error("Got an error [$error]");
      }
      $user = User::where('email', '=', $fbuser->getEmail())->first();
      if (!User::instantiated($user) && method_exists($this,"socialRegister")) {
        $user = $this->socialRegister($fbuser);
      }
      if (User::instantiated($user)) {
        $user->login(true);
        if (method_exists($this,"socialLogin")){
          $this->socialLogin($fbuser,$user);
        }
      }
      return redirect('/');
    }
}
