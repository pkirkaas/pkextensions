<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions;
use Illuminate\Support\Facades\Facade;

class PkHtmlFacade extends Facade {
  protected static function getFacadeAccessor() {
    return 'pkhtml';
  }
}
