<?php
namespace PkExtensions;
use Illuminate\Validation\ValidationServiceProvider;
use Illuminate\Support\ServiceProvider;
use PkValidator;
use \DB;
use \Schema;
class PkValidatorServiceProvider extends ValidationServiceProvider {
  protected function registerValidationFactory() {
    $this->app->singleton('pkvalidator', function ($app) {
      $validator = new PkValidatorFactory($app['translator'], $app);
      // The validation presence verifier is responsible for determining the existence
      // of values in a given data collection, typically a relational database or
      // other persistent data stores. And it is used to check for uniqueness.
      if (isset($app['validation.presence'])) {
        $validator->setPresenceVerifier($app['validation.presence']);
      }
      return $validator;
    });
  }
}
