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
      if (isset($app['validation.presence'])) {
        $validator->setPresenceVerifier($app['validation.presence']);
      }
      return $validator;
    });
  }

  public function provides() {
    return ['pkvalidator'];
  }
}
