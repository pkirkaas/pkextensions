<?php
namespace PkExtensions;
use PkExtensions\Models\PkModel;
use PkForm;
/** A base class to generate HTML pages & Forms.  */
#Just playing while I figure out what I want to do with it...
class PkHtmlPainter {
  public $csrf_token;
  public function __construct($args=[]) {
  }

  public function wrapForm($args=[]) {
    
  }

  public function mkCreateBtn($args=[]) {
    $hr=new PkHtmlRenderer();
    return $hr->div('Create','js btn mf-btn pkmvc-button create-new-data-set');
  }

  public function mkDelBtn($args=[]) {
   $hr = new PkHtmlRenderer();
   return $hr->div("Delete",'js btn mf-btn pkmvc-button data-set-delete');
  }

  /** These methods can insert the content now, or return a keyed template for the
   * content to be inserted or replaced repeatedly
   * @param type $content
   * @return string
   */
  public function mkJsTemplate($content=null) {
    $ps = new PartialSet();
      $ps['template-open']="<fieldset disabled style='display:none;' class='template-container'>\n";
      $ps['content'] = $content;
      $ps['template-close']="</fieldset>\n";
      return $ps;
  }

  public function mkSubformTemplate($content=null) {
    $mh = new PkHtmlRenderer();
    $mh['template-open']="<div class='templatable-data-sets'>\n";
    $mh['content'] = $content;
    $mh['create-button'] = $this->mkCreateBtn();
    $mh['template-close'] = "</div>\n";
    return $mh;
  }

  public function mkMultiSubform($content) {
    $mh = new PkHtmlRenderer();
    
  }
}



