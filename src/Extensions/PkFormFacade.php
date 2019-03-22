<?php
/**Copyight (C) 2016 by Paul Kirkaas - All Rights Reserved */
namespace PkExtensions;
use Illuminate\Html\FormBuilder;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

class PkFormFacade extends Facade {
  protected static function getFacadeAccessor() {
    return 'pkform';
  }
}
