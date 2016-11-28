<?php
namespace PkExtensions;
use Illuminate\Support\Facades\Facade;

class PkHtmlRendererFacade extends Facade {
  protected static function getFacadeAccessor() {
    return 'pkrenderer';
  }
}
