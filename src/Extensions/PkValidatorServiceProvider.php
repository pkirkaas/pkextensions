<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Support\ServiceProvider;
use PkValidator;
use \DB;
use \Schema;
class PkValidatorServiceProvider extends ValidationServiceProvider {
  protected function registerValidationFactory() {
    #If using both mine & default: $this->app->singleton('pkvalidator', function ($app) {
    $this->app->singleton('validator', function ($app) {
      $validator = new PkValidatorFactory($app['translator'], $app);
      if (isset($app['validation.presence'])) {
        $validator->setPresenceVerifier($app['validation.presence']);
      }
      return $validator;
    });
  }

  /*
  public function provides() {
    return ['pkvalidator', 'validator', 'validation.presence'];
  }
   * 
   */
}
