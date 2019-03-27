<?php /** (C) Copyright 2018 by Paul Kirkaas. All Rights Reserved */
namespace PkExtensions;
/** * Base class for VariantHandlers, for special handling 
 */
class VHandler {
   public static $variant = 0;
   public static function set($variant) {
     static::$variant = $variant;
   }
   public static function get() {
     return static::$variant;
   }
   public static function wrap($string,$connector = '-') {
     if (!static::$variant) {
       return $string;
     } else {
       return $string.$connector.static::$variant;
     }
   }

   /** Returns a view, with just the template name, or hypenated
    * with the variant name. If whitelist not empty, limit to
    * only those variant names
    * @param string $viewName
    * @param array $data
    * @param array $whitelist
    * @return view
    */
   public static function view($viewName, $data = [],$whitelist=[]) {
     if (is_string($whitelist)) {
       $whitelist = [$whitelist];
     }
     if ($whitelist && is_array($whitelist) &&
       !in_array(static::$variant, $whitelist,1)) {
         return view($viewName, $data);
     }
     return view(static::wrap($viewName), $data);
   }
}
