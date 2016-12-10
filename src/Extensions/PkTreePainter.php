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

/** Initializes/resets any member attributes that have a key in $args,
 * then calls parent construct with NO args.
 * @param type $args
 */
  public function __construct($args = []) {
    foreach ($args as $key => $val) {
      if (property_exists($this,$key)) {
        $this->$key = $val;
      }
    }
    parent::__construct();
  }
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

  public $labelAttributes = 'block tpm-label';
  public $valueAttributes = 'block tpm-value';
  public $wrapperAttributes = 'tpm-wrapper';

  /**
   * Wraps two elements, each in a containing element, the wraps the pair in
   * a third, containing element. The optional attributes are MERGED with the
   * default attributes, but the default attributes can be set on construct.
   * @param html $val
   * @param html $lbl
   * @param array $args - optional attributes for each of the 3 elements, keyed:
        'labelAttributes' => []
        'valueAttributes' => []
        'wrapperAttributes' => []
   *   
   */
  public function pair($val,$lbl=null,$args=[]) {
    $defaults = [
        'labelAttributes' => $this->labelAttributes,
        'valueAttributes' => $this->valueAttributes,
        'wrapperAttributes' => $this->wrapperAttributes,
    ];
    if (ne_string($args)) { #If just a string, it's the wrapper class
      $args=['wrapperAttributes' => ['class'=>$args]];
    }
    $labelAttributes = merge_att_arrs('labelAttributes',$defaults,$args);
    $valueAttributes = merge_att_arrs('valueAttributes',$defaults,$args);
    $wrapperAttributes = merge_att_arrs('wrapperAttributes',$defaults,$args);
    $labelTag = keyVal('tag', $labelAttributes,'div');
    unset($labelAttributes['tag']);
    $wrapTag = keyVal('tag', $wrapperAttributes,'div');
    unset($wrapperAttributes['tag']);
    $valueTag = keyVal('tag', $valueAttributes,'div');
    unset($valueAttributes['tag']);
    $pkt = new self();
    $pkt->$labelTag($lbl, $labelAttributes);
    $pkt->$valueTag($val, $valueAttributes);
    return $this::$wrapTag($pkt, $wrapperAttributes);
  }
}
