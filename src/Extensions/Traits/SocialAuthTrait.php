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
        pkdebug("Returned FB User:", $fbuser);
        if (!$fbuser instanceOf Auth2User) {
           $error="Sorry, we didn't get your user information back";
        } else if (!filter_var($fbuser->getEmail(), FILTER_VALIDATE_EMAIL)) {
          $error="We didn't get a valid email.";
        }
 //     } catch (\Exception $e) {
  //      $error = "We had a problem communicating with Facebookx: ".$e->getMessage();
   //   }
      if ($error) {
        //return $this->error($error."\nPlease try registering or logging in manually");
        pkdebug("Got an error?", $error);
        return "Got an error [$error]";
      }
      $user = User::where('email', '=', $fbuser->getEmail())->first();
      if (User::instantiated($user)) {
        $user->login(true);
        #and other stuff
      } else { #We don't know this user - try to register & log him
        $name = $fbuser->getName();
        $namearr = explode(' ',$name,2);
        $data = [
            'name' => $name,
            'fname' => keyVal(0,$namearr),
            'lname' =>  keyVal(1,$namearr),
            'email'=> $fbuser->getEmail(),
            'socialreg' => 'facebook',
            'provider' => 'facebook',
            'provider_id' =>   $fbuser->getId(),
            ];
        $user = $this->registerBouncerData($data);
      }
      $avatarUrl = $fbuser->getAvatar();
      pkdebug("The avatar url?", $avatarUrl);
      return redirect('/');

//      return $user;
    }


    // End social

}
