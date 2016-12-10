<?php
namespace PkExtensions;
/**
 * Re-implementation of PkHtmlPainter, based on PkTree, rather than PkHtmlRenderer 
 * ... But maybe this will be AjaxFormPainter or something...
 */

/** This is actually a class to allow JS/AJAX population of data for HTML
 * elements (divs) & inputs.
 */
class PkTreePainter extends PkTree {
  /*
  public $js_pop = 'js';


  public function __call($method,$args) {
     if(!($jsmethod = removeStartStr($method, $this->js_pop ))) {
       #It's not a JS Populated element - default to parent
       return parent::__call($method,$args);
     }

  }
   * 
   */
}
