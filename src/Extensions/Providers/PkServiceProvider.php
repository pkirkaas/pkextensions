<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */

namespace PkExtensions\Providers;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\ServiceProvider;
use PkExtensions\Traits\VariantConfigTrait;

class PkServiceProvider extends ServiceProvider {
    public function register() {
      config(['app.url'=>request()->root()]);
      if($var = getenv("VARIANT")) {
        config(['variant.variant'=>$var]);
        $this->variantConfig($var);
      }


      pkdebug("VAR: [$var]");
      //pkdebug("ReqRoot:",request()->root(),"baseURL:",getBaseUrl() , "app",config('app'));
      // Over-rides generated configs with custom settings from VHOST, like:
      // SetEnv APP_NAME "My VHOST App Name"
      // See lib/pkhelpers for convenience functions like 
      //    "apptype('finance1')"
      //    "apptype(['finance1','finance2'])"
      //    which return True or False
      // Sometimes the env def (DB_DATABASE) doesn't match the config param
      // 'database.database' - so then cat is an array as below
      /*
      $apacheConfigArgs = [
          ['cat'=>'app','param'=>'name'],
          ['cat'=>'app','param'=>'type'],
          ['cat'=>'app','param'=>'group'],
          ['cat'=>['conf'=>'database','env'=>'DB'],'param'=>'database'],
          ];
      foreach ($apacheConfigArgs as $confArr) {
        $cat = $confArr['cat'];
        if (is_array($cat)) {
          $confcat = $cat['conf'];
          $envcat = $cat['env'];
        } else {
          $confcat = $envcat = $cat;
        }
        $param = $confArr['param'];
        $envName = strtoupper($envcat).'_'.strtoupper($param); 
        if($envVal = getenv($envName)) { #Exists, overwrite default config
          config(["$confcat.$param"=>$envVal]);
        }
      }
       * 
       */
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot() {
      Relation::macro('getModel',function() {
        return $this->getQuery()->getModel();
      });

    }

}
