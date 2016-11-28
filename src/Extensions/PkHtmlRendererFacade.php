<?php
namespace PkExtensions;
use Illuminate\Support\Facades\Facade;

class PkHtmlRendererFacade extends Facade {
  protected static function getFacadeAccessor() {
    return 'pkrenderer';
  }
  protected static function resolveFacadeInstance($name) {
    if (is_object($name)) {
      return $name;
    }
      return static::$resolvedInstance[$name] = static::$app[$name];
    }
}
