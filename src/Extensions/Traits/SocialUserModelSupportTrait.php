<?php
/*
 * This add data field & processing to user implementations you want to support
 * with social media interacton. Start with FB, but then might want to add Linked
 * In for professional user. Add thing like links to the user's FB or Linked In
 * profiles, optionally their Profile Photos, allow users to register & sign in
 * with the providers, and even put a FB like button on.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace PkExtensions\Traits;

/**
 *
 * @author pkirkaas
 */
trait SocialUserModelSupportTrait {
  public function extractFBData($fbuser) {
     $fbdata = [
        'name' => $name,
        'fname' => keyVal(0,$namearr),
        'lname' =>  keyVal(1,$namearr),
        'email'=> $fbuser->getEmail(),
        'socialreg' => 'facebook',
        'provider' => 'facebook',
        'facebook_url' =>  keyVal('profileUrl',$fbuarr,$fbuser->profileUrl),
        'provider_id' =>   $fbuser->getId(),
        'avatar_url' =>keyVal('avatar_original',$fbuarr,$fbuser->avatar_original), 
      ];
  }
}
