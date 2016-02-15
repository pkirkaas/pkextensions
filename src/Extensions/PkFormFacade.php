<?php
namespace App\Extensions;
use Illuminate\Html\FormBuilder;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\ServiceProvider;

class PkFormFacade extends Facade {
  protected static function getFacadeAccessor() {
    return 'pkform';
  }
}
