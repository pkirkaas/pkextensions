<?php
namespace PkExtensions;
use Illuminate\Support\Facades\Facade;

class PkValidatorFacade extends Facade {
  protected static function getFacadeAccessor() {
    return 'pkvalidator';
  }
}
