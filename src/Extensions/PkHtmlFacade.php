<?php
namespace PkExtensions;
use Illuminate\Support\Facades\Facade;

class PkHtmlFacade extends Facade {
  protected static function getFacadeAccessor() {
    return 'pkhtml';
  }
}
